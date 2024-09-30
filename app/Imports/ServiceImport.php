<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ServiceImport implements ToCollection, WithHeadingRow, WithValidation
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $key => $row) {

            $serviceCategory = ServiceCategory::where(DB::raw('title->"$.en" LIKE "%"'. $row['service_category'].'"%"'))->orWhere(DB::raw('title->"$.ar" LIKE "%"'. $row['service_category'].'"%"'))->first();
            if(is_null($serviceCategory)){
                $serviceCategory = ServiceCategory::create(['title' => $row['service_category'], 'service_status' => 1]);
            }
            $data       = [
                'name'                  => $row['name'],
                'service_category_id'   => $serviceCategory->id,
            ];
            Service::create($data);
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
            'name'              => ['required', 'string', 'max:255'],
            'service_category'  => ['required'],
        ];

    }

    /**
     * @return array
    */
    public function customValidationAttributes()
    {
        return [
            'name'              => __('admin.name'),
            'service_category'  => __('main.serviceCategory'),
        ];
    }
}
