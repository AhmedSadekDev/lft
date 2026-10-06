<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\PrivateCompany;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use PHPUnit\Framework\TestCase;

class ListingProfilesTest extends TestCase
{
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = Model::getConnectionResolver();
        Model::setConnectionResolver(new ConnectionResolver(['' => new MySqlConnection(null)]));
    }

    protected function tearDown(): void
    {
        if ($this->resolver) {
            Model::setConnectionResolver($this->resolver);
        } else {
            Model::unsetConnectionResolver();
        }
        parent::tearDown();
    }

    public function test_resource_search_profiles_preserve_contains_bindings_and_grouping(): void
    {
        $profiles = [Company::class => ['name', 'email', 'phone', 'tax_no'],
            PrivateCompany::class => ['name', 'tax_no', 'commercial_register'],
            Car::class => ['car_number'], Driver::class => ['name', 'phone']];
        foreach ($profiles as $model => $fields) {
            foreach (['0', 'شركة', 'a%b_c', "O'Reilly", '   ', ''] as $term) {
                $actual = (new $model)->newQueryWithoutScopes()->where('id', '>', 10)->searchListing($term);
                $expected = (new $model)->newQueryWithoutScopes()->where('id', '>', 10)->where(function ($q) use ($fields, $term) {
                    foreach ($fields as $i => $field) {
                        $q->{$i === 0 ? 'where' : 'orWhere'}($field, 'like', "%{$term}%");
                    }
                });
                $this->assertSame($expected->toSql(), $actual->toSql());
                $this->assertSame($expected->getBindings(), $actual->getBindings());
            }
        }
    }

    public function test_invalid_sort_inputs_fall_back_as_a_pair(): void
    {
        foreach ([Company::class, PrivateCompany::class] as $model) {
            foreach ([['missing', 'asc'], ['name', 'invalid'], [[], 'asc'], ['id', []], [null, null], ['id desc', 'asc']] as [$column, $direction]) {
                $q = (new $model)->newQueryWithoutScopes()->sortListing($column, $direction);
                $this->assertStringEndsWith('order by `id` desc', $q->toSql());
            }
            foreach (['id', 'name', 'tax_no', 'created_at'] as $column) {
                $q = (new $model)->newQueryWithoutScopes()->sortListing($column, 'ASC');
                $this->assertStringEndsWith('order by `'.$column.'` asc', $q->toSql());
            }
        }
    }

    public function test_one_sided_iso_dates_use_ranges_and_preserve_edge_cases(): void
    {
        $model = new Booking;
        $q = $model->newQueryWithoutScopes()->filterDateRange('2024-02-29', null);
        $this->assertStringContainsString('`created_at` >= ?', $q->toSql());
        $this->assertSame(['2024-02-29 00:00:00'], $q->getBindings());
        $q = $model->newQueryWithoutScopes()->filterDateRange(null, '2024-02-29');
        $this->assertStringContainsString('`created_at` < ?', $q->toSql());
        $this->assertSame(['2024-03-01 00:00:00'], $q->getBindings());
        foreach (['2024-02-30', '2024-1-1', '2024-01-01 12:00:00', 'invalid', '9999-12-31'] as $date) {
            $q = $model->newQueryWithoutScopes()->filterDateRange(null, $date);
            $this->assertStringContainsString('date(`created_at`) <= ?', $q->toSql());
            $this->assertSame([$date], $q->getBindings());
        }
        $q = $model->newQueryWithoutScopes()->filterDateRange('2024-02-29', '2024-03-01');
        $this->assertStringContainsString('`created_at` between ? and ?', $q->toSql());
        $this->assertSame(['2024-02-29 00:00:00', '2024-03-01 23:59:59'], $q->getBindings());
    }
}
