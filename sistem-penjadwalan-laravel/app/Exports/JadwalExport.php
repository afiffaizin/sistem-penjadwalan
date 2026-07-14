<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JadwalExport implements FromView, WithStyles, WithEvents
{
    protected $request;
    protected $matrixJadwal;
    protected $matkulMandiri;
    protected $totalSesi;
    protected $hariKerja;

    public function __construct($request, $matrixJadwal, $matkulMandiri, $totalSesi, $hariKerja)
    {
        $this->request = $request;
        $this->matrixJadwal = $matrixJadwal;
        $this->matkulMandiri = $matkulMandiri;
        $this->totalSesi = $totalSesi;
        $this->hariKerja = $hariKerja;
    }

    public function view(): View
    {
        return view('exports.jadwal-excel', [
            'matrixJadwal' => $this->matrixJadwal,
            'matkulMandiri' => $this->matkulMandiri,
            'totalSesi' => $this->totalSesi,
            'hariKerja' => $this->hariKerja,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle($sheet->calculateWorksheetDimension())
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. Kunci lebar kolom secara proporsional agar teks wrap tertata rapi
                $sheet->getColumnDimension('A')->setWidth(10);  
                $sheet->getColumnDimension('B')->setWidth(32);  
                $sheet->getColumnDimension('C')->setWidth(32);  
                $sheet->getColumnDimension('D')->setWidth(32);  
                $sheet->getColumnDimension('E')->setWidth(32);  
                $sheet->getColumnDimension('F')->setWidth(32);  

                // 2. Paksa semua baris menghitung tinggi otomatis berdasarkan panjang teks (<br>)
                foreach ($sheet->getRowDimensions() as $rowDimension) {
                    $rowDimension->setRowHeight(-1);
                }
            },
        ];
    }
}
