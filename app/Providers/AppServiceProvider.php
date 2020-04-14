<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Illuminate\Routing\RouteCollectionInterface
        $this->app->bind(\Illuminate\Contracts\Routing\RouteCollectionInterface::class, function ($app) {
            return new \Laravel\Lumen\Routing\RouteCollectionInterface($app);
        });
        
        $this->app->singleton('Illuminate\Contracts\Routing\ResponseFactory', function ($app){
            return new \Illuminate\Routing\ResponseFactory(
                $app['Illuminate\Routing\RouteCollectionInterface'],
                $app['Illuminate\Contracts\View\Factory'], 
                $app['Illuminate\Routing\Redirector']
            );
        });


    }
}
