<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Brand;
use App\Models\AutoModel;
use App\Models\Product;
use App\Services\ScrapeInsertionService;

class ScrapeAutoPartsByBrand extends Command
{
    protected $signature = 'scrape:autoparts-by-brand
                            {--brand-id= : Specific brand ID to scrape}
                            {--limit= : Limit number of parts per model}';

    protected $description = 'Scrape auto parts for each brand->model combination from Taobao';

    private $processedProducts = [];

    public function handle()
    {
        $this->info('Starting auto parts scraping by brand...');

        $brandId = $this->option('brand-id');
        $limit = $this->option('limit');

        // Get brands
        $brands = $brandId
            ? Brand::where('id', $brandId)->get()
            : Brand::all();

        if ($brands->isEmpty()) {
            $this->error('No brands found!');
            return Command::FAILURE;
        }

        $this->info("Found {$brands->count()} brand(s) to process");

        foreach ($brands as $brand) {
            $this->processBrand($brand, $limit);
        }

        $this->info("\n✓ Scraping completed!");
        $this->info("Total unique products processed: " . count($this->processedProducts));

        return Command::SUCCESS;
    }

    private function processBrand($brand, $limit = null)
    {
        $brandName = $brand->name;
        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Processing Brand: {$brandName} (ID: {$brand->id})");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Get models for this brand
        $models = AutoModel::where('brand_id', $brand->id)->get();

        if ($models->isEmpty()) {
            $this->warn("  No models found for brand: {$brandName}");
            return;
        }

        $this->info("  Found {$models->count()} model(s)");

        foreach ($models as $model) {
            $this->processModel($brand, $model, $limit);

            // TEMPORARY: Break after first model for testing
            $this->warn("  [TEST MODE] Breaking after first model");
            break;
        }
    }

    private function processModel($brand, $model, $limit = null)
    {
        $modelName = $model->name;
        $this->newLine();
        $this->line("  → Model: {$modelName} (ID: {$model->id})");

        // Get auto parts from translations (Chinese)
        $autoParts = DB::table('auto_parts_translations')
            ->where('lang', 'zh')
            ->when($limit, function($query, $limit) {
                return $query->limit($limit);
            })
            ->get();

        if ($autoParts->isEmpty()) {
            $this->warn("    No auto parts found in translations!");
            return;
        }

        $this->info("    Processing {$autoParts->count()} auto part(s)...");
        $bar = $this->output->createProgressBar($autoParts->count());
        $bar->start();

        foreach ($autoParts as $part) {
            $this->processAutoPart($brand, $model, $part);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function processAutoPart($brand, $model, $part)
    {
        try {
            // Use Chinese names if available, fallback to original names
            $brandNameZh = $brand->name_zh ?: $brand->name;
            $modelNameZh = $model->name_zh ?: $model->name;

            // Create search keyword: Brand (Chinese) + Model (Chinese) + Part Name (Chinese)
            $keyword = "{$brandNameZh} {$modelNameZh} {$part->name}";

            // Search on Taobao and insert products
            $products = ScrapeInsertionService::searchAndInsertProducts(
                $keyword,
                60, // category_id - Car Electrical Parts
                1 // page
            );

            \Log::info('Taobao search results', [
                'keyword' => $keyword,
                'brand' => $brand->name,
                'brand_zh' => $brandNameZh,
                'model' => $model->name,
                'model_zh' => $modelNameZh,
                'part' => $part->name,
                'products_count' => count($products),
                'products' => array_map(function($p) {
                    return [
                        'id' => $p->id ?? null,
                        'name' => $p->name ?? null,
                        'scraped_item_id' => $p->scraped_item_id ?? null
                    ];
                }, $products)
            ]);

            if (empty($products)) {
                return;
            }

            // Update products with brand, model, and part info
            foreach ($products as $product) {
                // Check if we already processed this product
                if (isset($this->processedProducts[$product->scraped_item_id])) {
                    continue;
                }

                // Update product with brand, model, and auto part info
                Product::where('id', $product->id)->update([
                    'brand_id' => $brand->id,
                    'auto_model_id' => $model->id,
                    'auto_part_id' => $part->part_id,
                ]);

                // Mark as processed
                $this->processedProducts[$product->scraped_item_id] = true;
            }

        } catch (\Exception $e) {
            $this->error("\n    Error processing part '{$part->name}': " . $e->getMessage());
            \Log::error('Scrape auto parts error', [
                'brand' => $brand->name,
                'model' => $model->name,
                'part' => $part->name,
                'error' => $e->getMessage()
            ]);
        }
    }
}
