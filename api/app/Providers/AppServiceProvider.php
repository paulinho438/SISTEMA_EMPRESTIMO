<?php

namespace App\Providers;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Webhooks: não alterar JSON via TrimStrings/ConvertEmptyStringsToNull (assinatura HMAC = corpo bruto exato).
        TrimStrings::skipWhen(fn ($request) => $request->is('api/webhook/*'));
        ConvertEmptyStringsToNull::skipWhen(fn ($request) => $request->is('api/webhook/*'));
    }
}
