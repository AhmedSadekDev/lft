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

    public function test_usd_line_stores_foreign_amount_and_egp_book_price(): void
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

    public function test_usd_line_requires_a_positive_exchange_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InvoiceCurrency::normalizeLine(['currency' => 'USD', 'price' => 10, 'exchange_rate' => 0]);
    }

    public function test_hybrid_egp_invoice_uses_converted_book_amount(): void
    {
        $line = (object) [
            'currency' => 'USD',
            'foreign_amount' => 100,
            'exchange_rate' => 50,
            'price' => 5000,
        ];

        $this->assertSame(5000.0, InvoiceCurrency::invoiceLineAmount($line, 'EGP'));
        $this->assertSame(100.0, InvoiceCurrency::invoiceLineAmount($line, 'USD'));
        $this->assertStringContainsString('100.00 $', InvoiceCurrency::formatLine($line, 'EGP'));
        $this->assertStringContainsString('ج.م', InvoiceCurrency::formatLine($line, 'EGP'));
        $this->assertSame('100.00 $', InvoiceCurrency::formatLine($line, 'USD'));
    }
}
