<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Libraries\Versioning\VersionControlInterface;
use App\Libraries\Versioning\VersionControl;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register App\Libraries\Versioning\VersionControlInterface::class
        $this->app->bind(VersionControlInterface::class, function ($app) {
            $request        = $app->make('request');
            $versionControl = new VersionControl($request);
            $config         = $app->make('config');

            // set properties
            $pattern = $config->get('api.accept_header_pattern');
            $versionControl->setAcceptHeaderPattern($pattern);
            $fallback = $config->get('api.fallback_version');
            $versionControl->setFallbackVersion($fallback);

            return $versionControl;
        });
    }
}
