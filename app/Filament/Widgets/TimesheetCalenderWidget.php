<?php

namespace App\Filament\Widgets;

use App\Models\Timesheet;

use Guava\Calendar\Widgets\CalendarWidget;
use Illuminate\Support\Collection;
use App\Models\Timesheets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class TimesheetCalenderWidget extends CalendarWidget
{
    use InteractsWithForms;
    public ?string $search = null;
    // protected static string $view = 'filament.widgets.timesheet-calender-widget';
    public  static function canView(): bool
    {
        
        $employee = Auth::user()->employee;
        return $employee->employee_type !== 'mitra';
    }

    public function mount(): void
    {
        $this->form->fill([
            'search' => '',
        ]);
    }

    protected function getHeader(): ?string
    {
        // render komponen search secara manual
        return view('filament.widgets.timesheet-calender-widget', [
            'search' => $this->search,
        ])->render();
    }
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('search')
                ->label('Cari Nama / Pekerjaan')
                ->placeholder('Ketik nama karyawan atau pekerjaan...')
                ->reactive(), // langsung update kalender
        ];
    }
    public function getEvents(array $fetchInfo = []): Collection | array
    {
       $user = auth()->user();
       $employeeId = $user?->employee?->employee_id;

       return Timesheet::query()
            ->join('Attendances', 'Attendances.id', '=', 'Timesheets.attendance_id')
            ->select('Timesheets.*', 'Attendances.employee_id')
            ->when($this->search, function ($query) {
                // Jika ada pencarian
                $query->where('Attendances.employee_id', 'like', "%{$this->search}%")
                      ->orWhere('Timesheets.job_description', 'like', "%{$this->search}%");
            }, function ($query) use ($employeeId) {
                // Jika tidak ada pencarian
                if ($employeeId) {
                    $query->where('Attendances.employee_id', $employeeId);
                }
            })
            ->whereNotNull('Timesheets.created_at')
            ->whereNotNull('Timesheets.updated_at')
            ->get()
            ->map(function ($timesheet) {
                // Warna event berdasarkan status
                $color = match ((string) $timesheet->status) {
                    '2' => '#16a34a', // Done
                    '1' => '#f97316', // On Progress
                    '0' => '#dc2626', // Pending
                    default => '#6b7280', // Abu-abu
                };

                return [
                    'id'          => $timesheet->id,
                    'title'       => $timesheet->job_description ?? 'No Description',
                    'start'       => $timesheet->created_at,
                    'end'         => $timesheet->updated_at,
                    'color'       => $color,
                    'resourceId'  => 'default', // Tambahkan ini agar tampil di timeline
                ];
            });
    
    }

    public function getOptions(): array
    {
        return [
            // Tambahkan semua tombol view di toolbar
            'headerToolbar' => [
                'start'  => 'title',
                'center' => '',
                'end'    => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek,timelineWeek today prev,next',
            ],

            // View default
            'initialView' => 'dayGridMonth',

            // Aktifkan indikator waktu saat ini
            'nowIndicator' => true,

            // Teks tombol custom (bisa disesuaikan bahasa Indonesia)
            'buttonText' => [
                'today'         => 'Hari Ini',
                'dayGridMonth'  => 'Bulan',
                'timeGridWeek'  => 'Minggu',
                'timeGridDay'   => 'Hari',
                'listWeek'      => 'List',
                'timelineWeek'  => 'Timeline',
            ],

            // Aktifkan plugin tambahan (penting untuk List dan Timeline)
            'plugins' => [
                'dayGrid',
                'timeGrid',
                'list',
                'interaction',
                'resourceTimeline', // jika ingin tampilan timeline
            ],

            'resources' => [
                ['id' => 'default', 'title' => 'Task'],
            ],

            'eventResourceField' => 'resourceId',

            // Opsi tambahan untuk list/timeline
            'slotDuration' => '00:30:00',
            'expandRows' => true,
        ];
    }
}
