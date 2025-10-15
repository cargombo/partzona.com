<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public string $sku,
        public string $name,
        public int $quantity,
        public ?float $unit_price = null,
        public ?string $currency = null
    ) {}
}
