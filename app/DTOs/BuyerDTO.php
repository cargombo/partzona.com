<?php

namespace App\DTOs;

class BuyerDTO
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email_address,
        public string $phone_number,
        public string $zip_code,
        public string $city,
        public string $country,
        public string $shipping_address
    ) {}
}
