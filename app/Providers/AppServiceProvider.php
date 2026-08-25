<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // Gunakan style pagination Bootstrap 5 agar konsisten dengan UI baru
        Paginator::useBootstrapFive();

        // Global Blade Directive untuk format Rupiah standar Indonesia: Rp 876.900
        \Illuminate\Support\Facades\Blade::directive('rupiah', function ($expression) {
            return "<?php echo 'Rp ' . number_format((float) ($expression ?? 0), 0, ',', '.'); ?>";
        });
    }
}
