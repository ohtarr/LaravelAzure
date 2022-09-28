<?php

namespace Ohtarr\LaravelAzure;

use Illuminate\Support\Facades\Auth;
use Ohtarr\LaravelAzure\OauthTokenGuard;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {

        // Install our API auth guard middleware
        $this->installOauthTokenGuardMiddleware();

    }

    protected function checkMandatoryConfigsAreSet()
    {
        // On first run this will be false, after config file is installed it will be true
        if (config('enterpriseauth')) {
            // Go through all the credential config and make sure they are set in the .env or config file
            foreach (config('enterpriseauth.credentials') as $config => $env) {
                // If one isnt set, throw a red flat until the person fixes it
                if (! config('enterpriseauth.credentials.'.$config)) {
                    throw new \Exception('enterpriseauth setup error: missing mandatory config value for enterpriseauth.credentials.'.$config.' check your .env file!');
                }
            }
        }
    }

    protected function installOauthTokenGuardMiddleware()
    {
        config(['auth.guards.api.driver' => 'oauthtoken']);
        Auth::extend('oauthtoken', function ($app, $name, array $config) {
            $userProvider = Auth::createUserProvider($config['provider']);

            return new OauthTokenGuard($userProvider, $app->make('request'));
        });
    }

}