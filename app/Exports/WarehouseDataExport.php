<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class WarehouseDataExport implements FromCollection, WithHeadings, WithTitle
{
    protected $data;
    protected $headings;
    protected $title;

    public function __construct(Collection $data, array $headings, string $title)
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->title = $title;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
