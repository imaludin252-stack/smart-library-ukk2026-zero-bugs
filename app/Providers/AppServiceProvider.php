<?php

namespace App\Providers;

use App\Models\Loan;
use App\Policies\LoanPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // S3-01: kebijakan kepemilikan transaksi (anti-IDOR).
        Gate::policy(Loan::class, LoanPolicy::class);

        // S3-06: batasi percobaan login untuk mencegah brute force.
        RateLimiter::for('login', function (Request $request) {
            $kunci = Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());

            return Limit::perMinute(5)->by($kunci)->response(
                fn () => response()->view('errors.429', [], 429)
            );
        });
    }
}
