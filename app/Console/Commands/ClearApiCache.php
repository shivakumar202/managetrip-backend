<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearApiCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-api {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear API caches (service locations, hotels, prices, or all)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type') ?? 'all';

        switch ($type) {
            case 'locations':
                Cache::forget('service_locations_all');
                $this->info('✓ Cleared service locations cache');
                break;

            case 'services':
                // Clear all service caches using pattern
                $pattern = 'services_location_*';
                $this->clearCacheByPattern($pattern);
                $this->info('✓ Cleared services cache');
                break;

            case 'hotels':
                // Clear all hotel caches
                $pattern = 'hotels_search_*';
                $this->clearCacheByPattern($pattern);
                $pattern = 'hotel_details_*';
                $this->clearCacheByPattern($pattern);
                $this->info('✓ Cleared hotels cache');
                break;

            case 'prices':
                // Clear all price caches
                $pattern = 'hotel_price_*';
                $this->clearCacheByPattern($pattern);
                $this->info('✓ Cleared hotel prices cache');
                break;

            case 'all':
                Cache::flush();
                $this->info('✓ Cleared all caches');
                break;

            default:
                $this->error("Unknown cache type: {$type}");
                $this->line('Available types: locations, services, hotels, prices, all');
                return 1;
        }

        return 0;
    }

    /**
     * Clear cache by pattern (works best with Redis)
     */
    private function clearCacheByPattern($pattern)
    {
        $keys = Cache::getStore()->getRedis()->keys($pattern);
        if (count($keys) > 0) {
            Cache::getStore()->getRedis()->del(...$keys);
        }
    }
}
