<?php

namespace Khodja\Upload;

use Illuminate\Support\ServiceProvider;

class UploadServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        
        $this->publishes([
            __DIR__.'/config/upload.php' => config_path('upload.php')
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // merge default config
        $this->mergeConfigFrom(
            __DIR__.'/config/upload.php',
            'upload'
        );

        $this->app->singleton('upload', function($app) {
            return new Upload;
        });
    }
}
