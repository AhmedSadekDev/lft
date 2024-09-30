<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransportationsImport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        dd('test');
    }

    /**
    * @return array
    */

    public function rules(): array
    {
        return [
            '1' => Rule::in(['patrick@maatwebsite.nl']),
        ];
    }

    /**
     * @return array
     */
    public function customValidationAttributes()
    {
        return ['1' => 'email'];
    }
}
