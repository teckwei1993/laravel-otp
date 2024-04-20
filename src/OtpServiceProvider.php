<?php

namespace Teckwei1993\Otp;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

class OtpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->alias(Otp::class, 'otp');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (version_compare(Application::VERSION, '9.0.0', '<')) {
            $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'otp');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/otp'),
                __DIR__.'/../config/otp.php' => config_path('otp.php')
            ]);
            return;
        }

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'otp');
 
        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/otp'),
            __DIR__.'/../config/otp.php' => config_path('otp.php')
        ]);
    }
}