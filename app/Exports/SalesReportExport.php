<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesReportExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return array_map(fn ($row) => [
            $row['product'],
            $row['variant'],
            $row['category'],
            $row['qty'],
            $row['revenue'],
            $row['cogs'],
            $row['margin'],
        ], $this->rows);
    }

    public function headings(): array
    {
        return ['Produk', 'Varian', 'Kategori', 'Qty Terjual', 'Omset', 'HPP', 'Margin'];
    }
}
