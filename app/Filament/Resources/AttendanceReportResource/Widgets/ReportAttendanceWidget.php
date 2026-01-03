<?php

namespace App\Filament\Resources\AttendanceReportResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Employee;
use App\Services\HariKerjaService;
use Carbon\Carbon;
use Filament\Tables\Filters\Filter;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class ReportAttendanceWidget extends BaseWidget
{
    protected static ?string $heading = 'Report Attendance';
    protected int | string | array $columnSpan = 'full';

    public  static function canView(): bool
    {
        
        $user = Auth::user()->setConnection('mysql');
        $tipeEmployee = $user->employee?->employee_type;
        return $tipeEmployee !== 'mitra';
    }

    public ?int $bulan = null;
    public ?int $tahun = null;
    public array $hariKerjaCache = [];

    protected $updatesQueryString = ['bulan', 'tahun'];

    public function mount(): void
    {
        $this->bulan = $this->bulan ?? now('Asia/Jakarta')->month;
        $this->tahun = $this->tahun ?? now('Asia/Jakarta')->year;
    }

    protected function getTableQuery(): Builder
    {
        return Employee::query()
            ->from('Employees')
            ->select([
                'Employees.employee_id',
                'Employees.job_title',
                'Organizations.divisi_name',
                'Organizations.unit_name',
                DB::raw("CONCAT(TRIM(CONCAT_WS(' ', Employees.first_name, Employees.middle_name, Employees.last_name))) as full_name"),
            ])
            ->join('Organizations', 'Employees.org_id', '=', 'Organizations.id')
            ->orderBy('Organizations.divisi_name')
            ->orderBy('Organizations.unit_name')
            ->orderBy('Employees.employee_id');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('employee_id')->label('NIK')->sortable(),
            Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Nama')
                    ->getStateUsing(fn($record) => $record->employee?->full_name ?? '-')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('employee', function ($q) use ($search) {
                            $q->whereRaw("CONCAT(first_name, ' ', middle_name,' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(function (Builder $query) {
                        $direction = request()->input('tableSortDirection', 'asc');
                        return $query->orderBy(
                            Employee::selectRaw("CONCAT(first_name,' ', middle_name, ' ', last_name)")
                                ->whereColumn('employees.employee_id', 'attendances.employee_id')
                                ->limit(1),
                            $direction
                        );
                    }),
            Tables\Columns\TextColumn::make('divisi_name')->label('Divisi')->sortable(),
            Tables\Columns\TextColumn::make('unit_name')->label('Unit')->sortable(),
            Tables\Columns\TextColumn::make('jumlah_hari_kerja')
                ->label('Hari Kerja')
                ->getStateUsing(fn($record) => $this->getHariKerjaData($record)['jumlah_hari_kerja']),
            Tables\Columns\TextColumn::make('jml_absensi')
                ->label('Hadir')
                ->getStateUsing(fn($record) => $this->getHariKerjaData($record)['jml_absensi']),
            Tables\Columns\TextColumn::make('jml_alpha')
                ->label('Tidak Hadir')
                ->getStateUsing(fn($record) => $this->getHariKerjaData($record)['jml_alpha']),
            Tables\Columns\TextColumn::make('periode')
                ->label('Periode')
                ->getStateUsing(fn() => $this->getPeriodeLabel()),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
           Filter::make('filter_organisasi')
                ->form([
                    Forms\Components\Select::make('divisi_name')
                        ->label('Divisi')
                        ->options(\App\Models\Organization::pluck('divisi_name','divisi_name')->toArray())
                        ->reactive(),

                    Forms\Components\Select::make('unit_name')
                        ->label('Unit')
                        ->options(function (callable $get) {
                            $divisi = $get('divisi_name');
                            return \App\Models\Organization::when($divisi, fn($q) => $q->where('divisi_name', $divisi))
                                ->pluck('unit_name','unit_name')->toArray();
                        }),
                ])
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['divisi_name'])) {
                        $query->whereHas('organization', fn($q) => $q->where('divisi_name', $data['divisi_name']));
                    }
                    if (!empty($data['unit_name'])) {
                        $query->whereHas('organization', fn($q) => $q->where('unit_name', $data['unit_name']));
                    }
                }),

            Filter::make('periode')
                ->form([
                    Forms\Components\Select::make('periode')
                        ->label('Pilih Periode')
                        ->options($this->getPeriodeOptions())
                        ->default($this->getDefaultPeriode())
                        ->required(),
                ])
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['periode'])) {
                        [$tahun, $bulan] = explode('-', $data['periode']);
                        $this->tahun = (int) $tahun;
                        $this->bulan = (int) $bulan;
                        $this->hariKerjaCache = [];
                    }
                    return $query;
                }),
        ];
    }

    private function getPeriodeOptions(): array
    {
        $now = now('Asia/Jakarta');

        $bulanSekarang = $now->month;
        $tahunSekarang = $now->year;

        $bulanSebelumnya = $bulanSekarang === 1 ? 12 : $bulanSekarang - 1;
        $tahunSebelumnya = $bulanSekarang === 1 ? $tahunSekarang - 1 : $tahunSekarang;

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            "{$tahunSebelumnya}-" . str_pad($bulanSebelumnya, 2, '0', STR_PAD_LEFT)
                => "{$namaBulan[$bulanSebelumnya]} {$tahunSebelumnya}",
            "{$tahunSekarang}-" . str_pad($bulanSekarang, 2, '0', STR_PAD_LEFT)
                => "{$namaBulan[$bulanSekarang]} {$tahunSekarang}",
        ];
    }

    private function getDefaultPeriode(): string
    {
        return now('Asia/Jakarta')->format('Y-m');
    }

    private function getPeriodeLabel(): string
    {
        $start = Carbon::create($this->tahun, $this->bulan, 5)
            ->subMonthNoOverflow()
            ->startOfDay();
        $end = Carbon::create($this->tahun, $this->bulan, 4)
            ->endOfDay();

        return "{$start->format('d M Y')} - {$end->format('d M Y')}";
    }

    private function getHariKerjaData($employee): array
    {
        if (isset($this->hariKerjaCache[$employee->employee_id])) {
            return $this->hariKerjaCache[$employee->employee_id];
        }

        $service = new HariKerjaService();

        $bulan = $this->bulan ?? now('Asia/Jakarta')->month;
        $tahun = $this->tahun ?? now('Asia/Jakarta')->year;

        $start = Carbon::create($tahun, $bulan, 1, 0, 0, 0, 'Asia/Jakarta')
            ->subMonthNoOverflow()
            ->day(5)
            ->startOfDay()
            ->toDateString();

        $end = Carbon::create($tahun, $bulan, 4, 0, 0, 0, 'Asia/Jakarta')
            ->endOfDay()
            ->toDateString();

        $hariKerja = $service->hitungHariKerja($employee->employee_id, $start, $end);

        $this->hariKerjaCache[$employee->employee_id] = [
            'jumlah_hari_kerja' => $hariKerja['jumlah_hari_kerja'] ?? 0,
            'jml_absensi' => $hariKerja['jml_absensi'] ?? 0,
            'jml_alpha' => $hariKerja['jml_alpha'] ?? 0,
        ];

        return $this->hariKerjaCache[$employee->employee_id];
    }
}
