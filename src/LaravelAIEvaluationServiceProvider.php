<?php

namespace LaravelAIEvaluation\LaravelAIEvaluation;

use Illuminate\Support\ServiceProvider;
use LaravelAIEvaluation\LaravelAIEvaluation\Console\PestProcessRunner;
use LaravelAIEvaluation\LaravelAIEvaluation\Console\RunAgentEvalsCommand;

class LaravelAIEvaluationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-ai-evaluation');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-ai-evaluation');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-ai-evaluation.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-ai-evaluation'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-ai-evaluation'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-ai-evaluation'),
            ], 'lang');*/

            // Registering package commands.
            $this->commands([
                RunAgentEvalsCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-ai-evaluation');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-ai-evaluation', function () {
            return new LaravelAIEvaluation;
        });

        $this->app->singleton(PestProcessRunner::class, function () {
            return new PestProcessRunner;
        });
    }
}
