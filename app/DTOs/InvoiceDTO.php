<?php

namespace App\DTOs;

class InvoiceDTO
{
    public function __construct(
        public ?string $invoice_number = null,
        public ?float $invoice_price = null,
    ) {}
}
