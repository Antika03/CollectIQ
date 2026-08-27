<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gunakan Bootstrap 5 untuk pagination
        Paginator::useBootstrapFive();

        // Daftarkan Blade directive setelah aplikasi selesai booting
        $this->app->booted(function () {
            Blade::directive('rupiah', function ($expression) {
                return "<?php echo 'Rp ' . number_format((float) ($expression ?? 0), 0, ',', '.'); ?>";
            });
        });
    }
}