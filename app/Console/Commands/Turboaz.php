<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Services\TurboazScrap;
use Illuminate\Console\Command;

class Turboaz extends Command
{
    protected $signature = 'scrap:turboaz';

    protected $description = 'Command description';

    public function handle()
    {
        $brands = TurboazScrap::getBrands();
        foreach ($brands as $brand) {
            Brand::create([
                'name' => $brand['name'],
                'logo' => $brand['logo'],
                'slug' => \Str::slug($brand['name']),
                'meta_title' => $brand['name'],
                'top' => $brand['is_popular']
            ]);
        }
        return Command::SUCCESS;
    }
}
