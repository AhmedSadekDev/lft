<?php

namespace App\Imports;

use App\Models\CitiesAndRegions;
use App\Models\Company;
use App\Models\CompanyTransportation;
use App\Models\Container;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TransportationsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public int $created = 0;

    public int $updated = 0;

    /** @var list<string> */
    public array $skipped = [];

    /**
     * @param  Collection<int, Collection<string, mixed>>  $collection
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $excelRow = (int) $index + 2; // heading row = 1
            $row = $this->normalizeRow($row);

            $containerLabel = trim((string) ($row['container'] ?? ''));
            $departureLabel = trim((string) ($row['departure'] ?? ''));
            $loadingLabel = trim((string) ($row['loading'] ?? ''));
            $agingLabel = trim((string) ($row['aging'] ?? ''));
            $companyLabel = trim((string) ($row['company'] ?? ''));

            if ($containerLabel === '' && $departureLabel === '' && $loadingLabel === '' && $agingLabel === '') {
                continue;
            }

            $container = Container::findByImportLabel($containerLabel);
            $departure = $this->findCity($departureLabel);
            $loading = $this->findCity($loadingLabel);
            $aging = $this->findCity($agingLabel);
            $company = (isset(request()->company_id) && ! is_null(request()->company_id))
                ? Company::find(request()->company_id)
                : $this->findCompany($companyLabel);

            $missing = [];
            if (! $container) {
                $missing[] = 'حاوية: ' . ($containerLabel !== '' ? $containerLabel : 'فارغ');
            }
            if (! $departure) {
                $missing[] = 'خروج: ' . ($departureLabel !== '' ? $departureLabel : 'فارغ');
            }
            if (! $loading) {
                $missing[] = 'تحميل: ' . ($loadingLabel !== '' ? $loadingLabel : 'فارغ');
            }
            if (! $aging) {
                $missing[] = 'تعتيق: ' . ($agingLabel !== '' ? $agingLabel : 'فارغ');
            }
            if (! $company) {
                $missing[] = 'شركة: ' . ($companyLabel !== '' ? $companyLabel : 'فارغ');
            }

            if ($missing !== []) {
                $this->skipped[] = 'صف ' . $excelRow . ' — لم يُطابق: ' . implode(' | ', $missing);

                continue;
            }

            $payload = [
                'container_id' => $container->id,
                'container_data' => $container->full_name,
                'company_id' => $company->id,
                'company_data' => $company->name,
                'departure' => $departureLabel,
                'departure_id' => $departure->id,
                'loading' => $loadingLabel,
                'loading_id' => $loading->id,
                'aging' => $agingLabel,
                'aging_id' => $aging->id,
                'price' => $row['price'] ?? 0,
            ];

            $existing = CompanyTransportation::query()
                ->where('company_id', $company->id)
                ->where('container_id', $container->id)
                ->where('departure_id', $departure->id)
                ->where('loading_id', $loading->id)
                ->where('aging_id', $aging->id)
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                CompanyTransportation::create($payload);
                $this->created++;
            }
        }
    }

    /**
     * Map Arabic / export headings to the English keys the sample file uses.
     *
     * @param  Collection<string, mixed>|array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow($row): array
    {
        $data = $row instanceof Collection ? $row->toArray() : (array) $row;
        $aliases = [
            'company' => ['company', 'company_name', 'alshrk', 'الشركة'],
            'container' => ['container', 'container_type', 'alhaoy', 'الحاوية'],
            'departure' => ['departure', 'khrog', 'خروج'],
            'loading' => ['loading', 'thmyl', 'تحميل'],
            'aging' => ['aging', 'taatyk', 'تعتيق'],
            'price' => ['price', 'alsaar', 'السعر'],
        ];

        $normalized = [];
        foreach ($aliases as $canonical => $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                    $normalized[$canonical] = $data[$key];
                    break;
                }
            }
            if (! array_key_exists($canonical, $normalized)) {
                $normalized[$canonical] = $data[$canonical] ?? null;
            }
        }

        return $normalized;
    }

    private function findCity(string $title): ?CitiesAndRegions
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }

        return CitiesAndRegions::whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower($title, 'UTF-8')])->first()
            ?? CitiesAndRegions::where('title', 'LIKE', '%' . $title . '%')->first();
    }

    private function findCompany(string $name): ?Company
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return Company::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name, 'UTF-8')])->first()
            ?? Company::where('name', 'LIKE', '%' . $name . '%')->first();
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Normalize alias headings before Maatwebsite validation.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForValidation($data, $index)
    {
        return $this->normalizeRow($data);
    }

    public function rules(): array
    {
        return [
            'company' => (isset(request()->company_id) && ! is_null(request()->company_id))
                ? ['sometimes', 'nullable']
                : ['nullable', 'string', 'max:255'],
            'container' => ['required', 'string', 'max:255'],
            'departure' => ['required', 'string', 'max:255'],
            'loading' => ['required', 'string', 'max:255'],
            'aging' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
        ];
    }
}
