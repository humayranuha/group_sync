<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

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
        // Fix for MySQL older versions (optional)
        Schema::defaultStringLength(191);
        
        // Custom validation rule for weightage total
        Validator::extend('weightage_total', function ($attribute, $value, $parameters, $validator) {
            $total = array_sum($value);
            return $total == 100;
        }, 'The total weightage must be exactly 100%.');
        
        // Custom validation rule for valid role
        Validator::extend('valid_role', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, ['student', 'professor', 'admin']);
        }, 'The :attribute must be a valid role.');
        
        // Force HTTPS in production (optional)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
