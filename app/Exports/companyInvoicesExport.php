<?php

namespace App\Exports;

use App\Models\companyInvoices;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class companyInvoicesExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    private $from, $to, $company_id;
    public function __construct($from, $to, $company_id)
    {
        $this->from = $from;
        $this->to = $to;
        $this->company_id = $company_id;
    }
    public function collection()
    {
        $company_id = $this->company_id;
        $to = $this->to;
        $from = $this->from;
        $invoices = companyInvoices::where(function ($query) use ($from, $to, $company_id) {
            $query->where('company_id', $company_id)->whereBetween('created_at', [$from, $to]);
        })->latest()->get();
        $data = [];
        foreach ($invoices as $key => $invoice) {
            $data[$key]['#'] = $key + 1;
            $data[$key]['company'] = $invoice->company->name;
            $data[$key]['user'] = $invoice->user->name;
            $data[$key]['total'] = $invoice->total;
            $data[$key]['created_at'] = $invoice->created_at;
            $data[$key]['image'] = asset('Admin/images/companyInvoice/') . $invoice->image;
        }
        return collect($data);
    }
    public function headings(): array
    {
        return [
            '#',
            'اسم الشركة',
            'من قام بالاضافة',
            'القيمة',
            'تاريخ الاضافة',
            'الصورة',
        ];
    }
}
