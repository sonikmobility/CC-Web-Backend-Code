<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CentralExport implements FromCollection, WithHeadings
{
    protected $main_data;
    protected $header;
    public function __construct($main_data,$header)
    {
        $this->main_data = $main_data;
        $this->header = $header;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->main_data;
    }

    public function headings(): array
    {
        return $this->header;
    }
}