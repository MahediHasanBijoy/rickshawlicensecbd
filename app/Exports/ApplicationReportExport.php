<?php

namespace App\Exports;

use App\Models\Applicant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ApplicationReportExport implements
    FromCollection,
    WithHeadings,
    WithDrawings,
    WithMapping,
    ShouldAutoSize,
    WithEvents
{
    protected array $rows = [];

    public function __construct(
        public ?int $area_id,
        public ?int $category_id,
        public ?string $application_status,
        public ?string $year
    ) {}

    /**
     * Raw data (NOT directly exported)
     */
    public function collection()
    {
        $applicants = Applicant::query()
            ->when($this->area_id, fn ($q) => $q->where('area_id', $this->area_id))
            ->when($this->category_id, fn ($q) => $q->where('category_id', $this->category_id))
            ->when($this->application_status, fn ($q) => $q->where('status', $this->application_status))
            ->when($this->year, fn ($q) => $q->whereYear('created_at', $this->year))
            ->with(['area', 'category'])
            ->get();

        foreach ($applicants as $applicant) {
            $this->rows[] = [
                'image_path' => $applicant->applicant_image, // internal only
                'application_number' => $applicant->application_number,
                'license_number' => $applicant->license_number,
                'name' => $applicant->applicant_name,
                'nid_no' => $applicant->nid_no,
                'phone' => $applicant->phone,
                'area' => $applicant->area?->area_name,
                'category' => $applicant->category?->category_name,
                'status' => $applicant->status,
            ];
        }

        return collect($this->rows);
    }

    /**
     * WHAT gets exported as columns
     */
    public function map($row): array
    {
        return [
            '', // image cell stays EMPTY (no path text)
            $row['application_number'],
            $row['license_number'],
            $row['name'],
            $row['nid_no'],
            $row['phone'],
            $row['area'],
            $row['category'],
            $row['status'],
        ];
    }

    public function headings(): array
    {
        return [
            'আবেদনকারীর ছবি',
            'আবেদন নং',
            'লাইসেন্স নাম্বার',
            'নাম',
            'এনআইডি নং',
            'ফোন নাম্বার',
            'এলাকা',
            'ক্যাটাগরি',
            'অবস্থা',
        ];
    }

    /**
     * Embed images
     */
    public function drawings()
    {
        $drawings = [];

        foreach ($this->rows as $index => $row) {

            if (empty($row['image_path'])) {
                continue;
            }

            $path = storage_path('app/public/' . $row['image_path']);

            if (!file_exists($path) || !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                continue;
            }
            if (!file_exists($path)) {
                 continue;
            }

            $drawing = new Drawing();
            $drawing->setPath($path);
            $drawing->setHeight(70); 
            $drawing->setCoordinates('A' . ($index + 2));
            $drawing->setOffsetX(10);
            $drawing->setOffsetY(5);

            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->rows) + 1;
                $lastColumn = 'I';
                $sheet->getColumnDimension('A')->setWidth(80);
                // Center text
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Header bold
                $sheet->getStyle("A1:{$lastColumn}1")
                    ->getFont()
                    ->setBold(true);

                // Row height for images
                foreach (range(2, $lastRow) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(80);
                }

                // Freeze header
                $sheet->freezePane('A2');
            },
        ];
    }
}
