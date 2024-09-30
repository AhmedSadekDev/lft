<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompanyServiceImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $company;

    public function __construct(Company $company){
        $this->company = $company;
    }
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $key => $row) {
            $service = Service::where('name', 'LIKE', $row['service_name'])->first();

            if(is_null($service)){
                $service = Service::create(['name' => $row['service_name']]);
            }

            $this->company->services()->attach($service->id, [
                'cost'  => $row['cost'],
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }


    /**
    * @return array
    */
    public function rules(): array
    {
        return [
            'service_name'      => ['required', 'string', 'max:255'],
            'cost'              => ['required', 'numeric', 'min:1'],
        ];

    }

    /**
     * @return array
    */
    public function customValidationAttributes()
    {
        return [
            'service'   => __('main.service'),
            'cost'      => __('admin.cost'),
        ];
    }
}
