<?php

namespace App\Console\Commands;

use App\Models\AutoModel;
use App\Models\AutoModelGroup;
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
            $this->info("Start");
            $brands = TurboazScrap::getBrands();
            $this->info("Total brands fetched: " . count($brands));

            foreach ($brands as $brandData) {
                $uploadId = null;
                if (!empty($brandData['logo'])) {
                    $uploadId = $this->uploadLogo($brandData['logo'], $brandData['name']);
                }

                $brand             = new Brand();
                $brand->id         = $brandData['id'];
                $brand->name       = $brandData['name'];
                $brand->logo       = $uploadId;
                $brand->slug       = \Str::slug($brandData['name']);
                $brand->meta_title = $brandData['name'];
                $brand->top        = $brandData['is_popular'] ? 1 : 0;
                $brand->save();

                // Process all groups for this brand
                if (!empty($brandData['groups'])) {
                    foreach ($brandData['groups'] as $groupData) {
                        $groupId = null;

                        // Create group if it exists
                        if (!empty($groupData['group']['id']) && !empty($groupData['group']['name'])) {
                            try {
                                $autoModelGroup = new AutoModelGroup();
                                $autoModelGroup->id = $groupData['group']['id'];
                                $autoModelGroup->name = $groupData['group']['name'];
                                $autoModelGroup->save();
                                $groupId = $autoModelGroup->id;
                            } catch (\Exception $e) {
                                // Skip duplicate group IDs
                                $groupId = $groupData['group']['id'];
                            }
                        }

                        // Save models for this group
                        if (!empty($groupData['models'])) {
                            foreach ($groupData['models'] as $model) {
                                try {
                                    $autoModel  = new AutoModel();
                                    $autoModel->id       = $model['id'];
                                    $autoModel->brand_id = $brandData['id'];
                                    $autoModel->group_id = $groupId;
                                    $autoModel->name = $model['name'];
                                    $autoModel->save();
                                } catch (\Exception $e) {
                                    // Skip duplicate model IDs
                                    $this->warn("Skipping duplicate model: {$model['name']} (ID: {$model['id']})");
                                }
                            }
                        }
                    }
                }

                $this->info("{$brandData['name']} added to DB");
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
