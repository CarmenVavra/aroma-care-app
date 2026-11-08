<?php

namespace App\Console\Commands;

use App\Models\CatalogEntry;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills the shared code catalogue from all saved products (incl. deleted ones).
 * Idempotent; the most recently changed product wins per code. deploy.sh runs it.
 */
class SyncCatalog extends Command
{
    protected $signature = 'catalog:sync';

    protected $description = 'Gemeinsamen Code-Katalog aus gespeicherten Produkten befüllen';

    public function handle(): int
    {
        $count = 0;

        DB::transaction(function () use (&$count) {
            Product::withTrashed()
                ->whereNotNull('barcode')
                ->where('barcode', '!=', '')
                ->with(['essentialOilDetail', 'carrierOilDetail'])
                ->orderBy('updated_at')
                ->orderBy('id')
                ->chunk(200, function ($products) use (&$count) {
                    foreach ($products as $product) {
                        CatalogEntry::learnFrom($product);
                        $count++;
                    }
                });
        });

        $this->info("Katalog abgeglichen: {$count} Produkte, ".CatalogEntry::count().' Codes.');

        return self::SUCCESS;
    }
}
