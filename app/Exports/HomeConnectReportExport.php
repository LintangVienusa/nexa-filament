<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

use App\Models\HomeConnectReport;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HomeConnectReportExport implements FromQuery, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
    }
    protected static $users;
    protected function getUsers()
    {
        if (! self::$users) {
            self::$users = User::pluck('name', 'email')->toArray();
        }

        return self::$users;
    }

    public function query()
    {
        return HomeConnectReport::query()
            ->with('employee')
            ->where('status_port', 'used');
    }

    public function headings(): array
    {
        return [
            'Tanggal IKR',
            'Nama Petugas',
            'ID Pelanggan',
            'Nama Pelanggan',
            'Merk ONT',
            'SN ONT',
            'Site',
            'ODP Name',
            'Port ODP',
            'Label ODP',
            'SN ONT',
            'Label Pelanggan',
            'QR Pelanggan',
            'Status Port',
            'Progress (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->updated_at,
            $row->employee?->full_name ?? '-',
            strtoupper($row->id_pelanggan),
            strtoupper($row->name_pelanggan),
            $row->merk_ont,
            $row->sn_ont,
            $row->site,
            $row->odp_name,
            $row->port_odp,
            $row->foto_label_odp,
            $row->foto_sn_ont,
            $row->foto_label_id_plg,
            $row->foto_qr,
            $row->status_port,
            $row->progress_percentage,
        ];
    }
}
