<?php

namespace App\DTOs;

class ParcelCreateDTO
{
    public function __construct(
        public string $fm_tracking_number,
        public array $uid,
        public BuyerDTO $buyer,
        public ?SellerDTO $seller = null,
        public ?InvoiceDTO $invoice = null,
        public ?string $warehouse_id = null,
        public ?string $domestic_cargo_company = null,
        public ?string $comment = null,
        public bool $is_door = false,
        public bool $is_micro = false,
        public bool $is_liquid = false,
        public array $products = [],
        public ?float $weight = null,
        public ?array $dimensions = null
    ) {}
}
