<?php

namespace App\Exports\EInvoice;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

abstract class BaseEInvoiceExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    /**
     * @param  array{headers: array<int, string>, rows: array<int, array<int, mixed>>}  $table
     * @param  Collection<int, array<string, mixed>>  $mappedRows
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        protected array $table,
        protected Collection $mappedRows,
        protected array $options = []
    ) {
    }

    public function headings(): array
    {
        return $this->table['headers'] ?? [];
    }

    public function array(): array
    {
        return $this->table['rows'] ?? [];
    }

    abstract public function title(): string;
}
