<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Upload;
use App\Services\TurboazScrap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Turboaz extends Command
{
    protected $signature = 'scrap:turboaz';

    protected $description = 'Command description';

    public function handle()
    {
        try {
            $brands = TurboazScrap::getBrands();
            foreach ($brands as $brand) {
                $uploadId = null;
                if (!empty($brand['logo'])) {
                    $uploadId = $this->uploadLogo($brand['logo'], $brand['name']);
                }

                Brand::create([
                    'name' => $brand['name'],
                    'logo' => $uploadId,
                    'slug' => \Str::slug($brand['name']),
                    'meta_title' => $brand['name'],
                    'top' => $brand['is_popular']
                ]);

                $this->info("{$brand['name']} added to DB");
            }
        } catch (\Exception $e) {
            $this->error("Error occurred: {$e->getMessage()}");
        }
        return Command::SUCCESS;
    }

    private function uploadLogo(string $logoUrl, string $brandName): ?int
    {
        try {
            $response = Http::timeout(30)->get($logoUrl);

            if (!$response->successful()) {
                $this->warn("Failed to download logo for {$brandName}");
                return null;
            }

            $extension = pathinfo(parse_url($logoUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
            $filename = \Str::slug($brandName) . '_' . time() . '.' . $extension;

            $path = 'uploads/' . $filename;
            Storage::disk('public')->put($path, $response->body());

            $upload = Upload::create([
                'original_name' => basename($logoUrl),
                'file_name' => 'storage/uploads/'.$filename,
                'user_id' => 1,
                'file_size' => strlen($response->body()),
                'extension' => $extension,
                'type' => 'image/' . $extension,
                'external_link' => Storage::disk('public')->url($path),
            ]);

            return $upload->id;

        } catch (\Exception $e) {
            $this->warn("Error uploading logo for {$brandName}: {$e->getMessage()}");
            return null;
        }
    }
}
