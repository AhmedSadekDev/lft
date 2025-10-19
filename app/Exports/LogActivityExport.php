<?php
namespace App\Exports;

use App\Models\LogActivity;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LogActivityExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = LogActivity::query();

        if ($this->request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $this->request->from_date);
        }

        if ($this->request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $this->request->to_date);
        }

        $logs = $query->latest()->get();

        return $logs->map(function ($log) {
            return [
                'ID'       => $log->id,
                'المندوب'  => $log?->attacher?->name ?? '',
                'الإجراء'  => $log->action ?? '',
                'التاريخ'  => $log->date ?? '',
                'الوقت'    => $log->time ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return ['#', 'المندوب', 'الإجراء', 'التاريخ', 'الوقت'];
    }
}
