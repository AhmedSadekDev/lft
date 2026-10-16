<?php

namespace Tests\Unit;

use App\Support\InvoiceCurrency;
use PHPUnit\Framework\TestCase;

class InvoiceCurrencyTest extends TestCase
{
    public function test_egp_line_keeps_price_and_clears_foreign_fields(): void
    {
        $line = InvoiceCurrency::normalizeLine([
            'currency' => 'EGP',
            'price' => 1500.5,
            'exchange_rate' => 48,
        ]);

        $this->assertSame(InvoiceCurrency::EGP, $line['currency']);
        $this->assertSame(1500.5, $line['price']);
        $this->assertNull($line['foreign_amount']);
        $this->assertNull($line['exchange_rate']);
    }

    public function test_usd_line_with_rate_keeps_internal_egp_book_only(): void
    {
        $line = InvoiceCurrency::normalizeLine([
            'currency' => 'USD',
            'price' => 120,
            'exchange_rate' => 48.5,
        ]);

        $this->assertSame(InvoiceCurrency::USD, $line['currency']);
        $this->assertSame(120.0, $line['foreign_amount']);
        $this->assertSame(48.5, $line['exchange_rate']);
        $this->assertSame(5820.0, $line['price']);
    }

    public function test_usd_line_without_rate_stores_foreign_amount_only(): void
    {
        $line = InvoiceCurrency::normalizeLine([
            'currency' => 'USD',
            'price' => 500,
            'exchange_rate' => 0,
        ]);

        $this->assertSame(InvoiceCurrency::USD, $line['currency']);
        $this->assertSame(500.0, $line['foreign_amount']);
        $this->assertNull($line['exchange_rate']);
        $this->assertSame(0.0, $line['price']);
    }

    public function test_usd_line_does_not_fold_into_egp_invoice_total(): void
    {
        $line = (object) [
            'currency' => 'USD',
            'foreign_amount' => 100,
            'exchange_rate' => 50,
            'price' => 5000,
        ];

        $this->assertSame(0.0, InvoiceCurrency::invoiceLineAmount($line, 'EGP'));
        $this->assertSame(100.0, InvoiceCurrency::invoiceLineAmount($line, 'USD'));
        $this->assertSame(100.0, InvoiceCurrency::foreignUsdAmount($line));
        $this->assertStringContainsString('100.00 $', InvoiceCurrency::formatLine($line, 'EGP'));
        $this->assertSame('100.00 $', InvoiceCurrency::formatLine($line, 'USD'));
    }
}
