<?php

namespace App\Filament\Resources\AttendanceReportResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Employee;
use App\Services\HariKerjaService;
use Carbon\Carbon;
use Filament\Tables\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms;
use Illuminate\Contracts\View\View;



class ReportAttendanceWidget extends BaseWidget
{
    protected static ?string $heading = "Report Attendance";
    protected int | string | array $columnSpan = 'full'; 

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $bulan = null;
    public ?int $tahun = null;
    public ?int $employee_id = null;
    public ?string $divisi_name = null;
    public ?string $unit_name = null;
    
    protected ?string $selectedPeriod = null;

    public function mount(): void
    {
        $this->bulan = now('Asia/Jakarta')->month;
        $this->tahun = now('Asia/Jakarta')->year;
        $this->updatePeriod();
        $this->preloadHariKerja();
    }

    public function updatedTableFilters(): void
    {
        $filters = $this->getTableFilters();

        if (!empty($filters['periode']['bulan'])) {
            $this->bulan = $filters['periode']['bulan'];
        }

        if (!empty($filters['periode']['tahun'])) {
            $this->tahun = $filters['periode']['tahun'];
        }

        $this->updatePeriod();
        $this->preloadHariKerja();
    }

    protected function updatePeriod(): void
    {
        $start = Carbon::create($this->tahun, $this->bulan, 28)->subMonthNoOverflow()->startOfDay();
        $end = Carbon::create($this->tahun, $this->bulan, 27)->endOfDay();

        $this->selectedPeriod = 'Periode: ' . $start->format('d M Y') . ' - ' . $end->format('d M Y');
    }


    protected function getTableQuery(): Builder|Relation|null
    {
        return Employee::query()
            ->select('Employees.employee_id','Employees.job_title', 'Organizations.divisi_name', 'Organizations.unit_name')
            ->join('Organizations', 'Employees.org_id', '=', 'Organizations.id')
            ->when($this->employee_id, fn($q) => $q->where('Employees.employee_id', $this->employee_id))
            ->when($this->divisi_name, fn($q) => $q->where('Organizations.divisi_name', $this->divisi_name))
            ->when($this->unit_name, fn($q) => $q->where('Organizations.unit_name', $this->unit_name))
            ->orderBy('Organizations.divisi_name')
            ->orderBy('Organizations.unit_name')
            ->orderBy('Employees.employee_id')
            ->orderBy('Employees.job_title');
    }

    protected function preloadHariKerja(): void
    {
        $service = new HariKerjaService();

        $bulan = $this->bulan ?? now('Asia/Jakarta')->month;
        $tahun = $this->tahun ?? now('Asia/Jakarta')->year;

        $start = Carbon::create($tahun, $bulan, 1, 0, 0, 0, 'Asia/Jakarta')
            ->subMonthNoOverflow()
            ->day(28)
            ->startOfDay()
            ->toDateString();

        $end = Carbon::create($tahun, $bulan, 27, 0, 0, 0, 'Asia/Jakarta')
            ->endOfDay()
            ->toDateString();

        // Ambil semua karyawan sekaligus
        $employees = Employee::query()
            ->select('Employees.employee_id','Employees.first_name','Employees.middle_name','Employees.last_name', 'Employees.job_title','Organizations.divisi_name', 'Organizations.unit_name')
            ->join('Organizations', 'Employees.org_id', '=', 'Organizations.id')
            ->when($this->employee_id, fn($q) => $q->where('Employees.employee_id', $this->employee_id))
            ->when($this->divisi_name, fn($q) => $q->where('Organizations.divisi_name', $this->divisi_name))
            ->when($this->unit_name, fn($q) => $q->where('Organizations.unit_name', $this->unit_name))
            ->orderBy('Organizations.divisi_name')
            ->orderBy('Organizations.unit_name')
            ->orderBy('Employees.employee_id')->get();

        $this->hariKerjaCache = [];

        foreach ($employees as $employee) {
            $hariKerja = $service->hitungHariKerja($employee->employee_id, $start, $end);
            if($employee->middle_name != ''){
                $fullname = $employee->first_name." ".$employee->middle_name." ".$employee->last_name;
            }else{
                $fullname = $employee->first_name." ".$employee->last_name;
            }

            // Simpan langsung semua informasi dalam cache
            $this->hariKerjaCache[$employee->employee_id] = [
                'employee_id' => $employee->employee_id,
                'divisi_name' => $employee->divisi_name,
                'unit_name' => $employee->unit_name,
                'name' => $fullname,
                'jumlah_hari_kerja' => $hariKerja['jumlah_hari_kerja'] ?? 0,
                'jml_absensi' => $hariKerja['jml_absensi'] ?? 0,
                'jml_alpha' => $hariKerja['jml_alpha'] ?? 0,
            ];
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            
            ->headerActions([
                Tables\Actions\Action::make('periode_info')
                    ->label(fn() => $this->selectedPeriod)
                    ->disabled()
                    ->color('gray')->extraAttributes(['wire:loading.class' => 'opacity-50']),
            ])
            ->columns([
               Tables\Columns\TextColumn::make('Organization.divisi_name')
                    ->label('Divisi')
                    ->getStateUsing(fn($record) => $this->hariKerjaCache[$record['employee_id']]['divisi_name'] ?? 0),

                Tables\Columns\TextColumn::make('Organization.unit_name')
                    ->label('Unit')
                    ->getStateUsing(fn($record) => $this->hariKerjaCache[$record['employee_id']]['unit_name'] ?? 0),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('Jabatan'),
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('NIK'),
               Tables\Columns\TextColumn::make('fullname')
                    ->label('Nama Karyawan')
                    ->getStateUsing(fn($record) => $this->hariKerjaCache[$record['employee_id']]['name'] ?? 0),

                Tables\Columns\TextColumn::make('jumlah_hari_kerja')
                    ->label('Hari Kerja')
                    ->getStateUsing(fn($record) => $this->hariKerjaCache[$record['employee_id']]['jumlah_hari_kerja'] ?? 0),


                Tables\Columns\TextColumn::make('jml_absensi')
                    ->label('Hadir')
                    ->getStateUsing(fn($record) => $this->hariKerjaCache[$record['employee_id']]['jml_absensi'] ?? 0),


                Tables\Columns\TextColumn::make('jml_alpha')
                    ->label('Tidak Hadir')
                     ->getStateUsing(fn($record) => $this->hariKerjaCache[$record['employee_id']]['jml_alpha'] ?? 0),
                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode')
                    ->getStateUsing(function() {
                        $bulan = $this->bulan ?? now('Asia/Jakarta')->month;
                        $tahun = $this->tahun ?? now('Asia/Jakarta')->year;

                        $start = Carbon::create($tahun, $bulan, 28, 0, 0, 0, 'Asia/Jakarta')
                            ->subMonthNoOverflow()
                            ->startOfDay();
                        $end = Carbon::create($tahun, $bulan, 27, 0, 0, 0, 'Asia/Jakarta')
                            ->endOfDay();

                        return $start->format('d M Y') . ' - ' . $end->format('d M Y');
                    }),
            ])->filters([
                SelectFilter::make('divisi_name')
                    ->label('Divisi')
                    ->options(
                        \App\Models\Organization::query()
                            ->distinct()
                            ->pluck('divisi_name', 'divisi_name')
                            ->filter()
                            ->toArray()
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('organization', function ($q) use ($data) {
                            $q->where('divisi_name', $data['value']);
                        });
                    }),
                SelectFilter::make('unit_name')
                    ->label('Unit')
                    ->options(
                        \App\Models\Organization::query()
                            ->distinct()
                            ->pluck('unit_name', 'unit_name')
                            ->filter()
                            ->toArray()
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('organization', function ($q) use ($data) {
                            $q->where('unit_name', $data['value']);
                        });
                    }),
                Filter::make('periode')
                    ->form([
                        Forms\Components\Select::make('bulan')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(now('Asia/Jakarta')->month)
                            ->required(),

                        Forms\Components\Select::make('tahun')
                            ->label('Tahun')
                            ->options(function () {
                                $years = range(now()->year - 2, now()->year + 1);
                                return collect($years)->mapWithKeys(fn($y) => [$y => $y]);
                            })
                            ->default(now('Asia/Jakarta')->year)
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $this->bulan = $data['bulan'] ?? now('Asia/Jakarta')->month;
                        $this->tahun = $data['tahun'] ?? now('Asia/Jakarta')->year;
                    }),
            ]);
    }

    

    public function updating($name, $value): void
    {
        $this->dispatchBrowserEvent('start-loading');
    }

    public function updated($name, $value): void
    {
        $this->dispatchBrowserEvent('stop-loading');
    }

    // private function getHariKerja($employeeId)
    // {
    //     $service = new HariKerjaService();

    //     $bulan = $this->bulan ?? now('Asia/Jakarta')->month;
    //     $tahun = $this->tahun ?? now('Asia/Jakarta')->year;

    //     $start = Carbon::create($tahun, $bulan, 1, 0, 0, 0, 'Asia/Jakarta')
    //         ->subMonthNoOverflow()
    //         ->day(28)
    //         ->startOfDay()
    //         ->toDateString();

    //     $end = Carbon::create($tahun, $bulan, 27, 0, 0, 0, 'Asia/Jakarta')
    //         ->endOfDay()
    //         ->toDateString();

    //     return $service->hitungHariKerja($employeeId, $start, $end);
    // }
}
