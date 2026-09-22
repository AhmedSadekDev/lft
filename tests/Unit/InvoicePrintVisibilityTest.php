<?php

namespace Tests\Unit;

use App\Mappers\InvoicePrintSectionMapper;
use App\Models\BookingService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\InvoicePrintBuilder;
use PHPUnit\Framework\TestCase;

class InvoicePrintVisibilityTest extends TestCase
{
    /** @dataProvider destinations */
    public function test_only_explicit_print_destinations_are_used($destination, int $status, string $expected): void
    {
        $category = new ServiceCategory();
        $category->invoice_print_section = $destination;
        $category->service_status = $status;
        $service = new Service(['name' => 'receipt taxi expense']);
        $service->setRelation('serviceCategory', $category);
        $item = new BookingService(['price' => 100]);
        $item->setRelation('service', $service);

        $this->assertSame($expected, (new InvoicePrintBuilder())->resolveServiceSection($item));
    }

    public static function destinations(): array
    {
        $cases = [];
        foreach ([0, 1, 2] as $status) {
            foreach ([null, '', 'hidden', 'invalid'] as $destination) {
                $cases[] = [$destination, $status, InvoicePrintSectionMapper::HIDDEN];
            }
            foreach (['tax', 'receipt', 'additional'] as $destination) {
                $cases[] = [$destination, $status, $destination];
            }
        }

        return $cases;
    }
}
