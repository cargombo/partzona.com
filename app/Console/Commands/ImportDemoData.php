<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use ZipArchive;
use Artisan;
use DB;

class ImportDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:demo-data {--force : Force import without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import demo data into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will import demo data. Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        try {
            $upload_path = "uploads/m9hm1aVQEg5LST8gXt1K64EsqdMZoYqzb4npwUJF.zip";
            $sql_path = "uploads/vDEnWcpRMb1eGzqKFLgSRz4TH8NrUHQHNHfvCNuU.zip";

            $zip = new ZipArchive;
            $zip->open(base_path('public/'.$upload_path));
            $zip->extractTo('public/uploads/all');

            $zip1 = new ZipArchive;
            $zip1->open(base_path('public/'.$sql_path));
            $zip1->extractTo('public/uploads');

            Artisan::call('cache:clear');
            $sql_path = base_path('public/uploads/demo_data.sql');
            DB::unprepared(file_get_contents($sql_path));

            $this->info('Demo data imported successfully!');

        } catch (\Exception $e) {
            $this->error('Error importing demo data: ' . $e->getMessage());
            return 1;
        }
    }

}