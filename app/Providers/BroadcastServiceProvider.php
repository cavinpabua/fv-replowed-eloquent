<?php

namespace App\Providers;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ALWAYS validate BEFORE setting up routes
        $this->validateReverbConfiguration();
        
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
    
    /**
     * Validate that Reverb is properly configured.
     * 
     * @throws BroadcastException
     */
    protected function validateReverbConfiguration(): void
    {
        $driver = config('broadcasting.default');
        $envDriver = env('BROADCAST_CONNECTION', 'NOT_SET');
        
        Log::info('Broadcasting configuration check', [
            'config_driver' => $driver,
            'env_driver' => $envDriver,
            'environment' => app()->environment(),
            'config_cached' => app()->configurationIsCached()
        ]);
        
        if ($driver !== 'reverb') {
            $message = "❌ Broadcasting driver MUST be 'reverb'. Current: '{$driver}' (ENV: {$envDriver}). " .
                       "Fix: Set BROADCAST_CONNECTION=reverb in .env, then run: php artisan config:clear && php artisan config:cache";
            
            Log::critical($message);
            
            // ALWAYS throw - even in production, to prevent silent failures
            throw new BroadcastException($message);
        }
        
        Log::info('✅ Broadcasting configured correctly for Reverb');
    }
}
