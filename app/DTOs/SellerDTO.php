<?php

namespace App\DTOs;

class SellerDTO
{
    public function __construct(
        public ?string $full_name = null
    ) {}
}
