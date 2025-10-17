<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Services\ScrapeInsertionService;

class FetchProductDetails extends Command
{
    protected $signature = 'fetch:product-details
                            {--product-id= : Specific product ID to fetch}
                            {--limit= : Limit number of products to process}
                            {--only-unfetched : Only process products not yet fetched}';

    protected $description = 'Fetch product details from Taobao and save to product_updates table';

    public function handle()
    {
        $this->info('Starting product details fetching...');

        $productId = $this->option('product-id');
        $limit = $this->option('limit');
        $onlyUnfetched = $this->option('only-unfetched');

        // Build query for products
        $query = Product::where('auction_product', 0)
            ->where('approved', 1)
            ->whereNotNull('scraped_item_id');

        // Filter by specific product
        if ($productId) {
            $query->where('id', $productId);
        }

        // Filter only unfetched products
        if ($onlyUnfetched) {
            $query->whereDoesntHave('productUpdate', function($q) {
                $q->where('details_fetched', true);
            });
        }

        // Apply limit
        if ($limit) {
            $query->limit($limit);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->warn('No products found to process!');
            return Command::SUCCESS;
        }

        $this->info("Found {$products->count()} product(s) to process");
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($products as $product) {
            $result = $this->fetchProductDetail($product);

            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✓ Processing completed!");
        $this->info("  Success: {$successCount}");
        $this->error("  Failed: {$errorCount}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return Command::SUCCESS;
    }

    private function fetchProductDetail($product)
    {
        try {
            // Fetch details using existing service
            $result = ScrapeInsertionService::insertProduct($product->slug);

            // Prepare update data
            $updateData = [
                'product_id' => $product->id,
                'scraped_item_id' => $product->scraped_item_id,
                'details_fetched' => isset($result['success']) && $result['success'],
                'error_message' => isset($result['error']) ? $result['error'] : null,
                'fetched_at' => now(),
                'updated_at' => now(),
            ];

            // Insert or update product_updates table
            DB::table('product_updates')->updateOrInsert(
                ['product_id' => $product->id],
                $updateData
            );

            return [
                'success' => isset($result['success']) && $result['success'],
                'message' => $result['message'] ?? ($result['error'] ?? 'Unknown error')
            ];

        } catch (\Exception $e) {
            // Log error to product_updates table
            DB::table('product_updates')->updateOrInsert(
                ['product_id' => $product->id],
                [
                    'product_id' => $product->id,
                    'scraped_item_id' => $product->scraped_item_id,
                    'details_fetched' => false,
                    'error_message' => $e->getMessage(),
                    'fetched_at' => now(),
                    'updated_at' => now(),
                ]
            );

            \Log::error('Fetch product details error', [
                'product_id' => $product->id,
                'slug' => $product->slug,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
