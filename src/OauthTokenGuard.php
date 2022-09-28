<?php

namespace Ohtarr\LaravelAzure;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Ohtarr\Azure\AzureApp;
use App\Models\User;
use Bouncer;

class OauthTokenGuard implements Guard
{
    protected $request;
    protected $provider;
    protected $user;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request)
    {

        $this->request = $request;
        $this->provider = $provider;
        $this->user = null;

        $oauthAccessToken = $request->bearerToken();

        $app = new AzureApp(env('AZURE_AD_TENANT'),env('AZURE_AD_CLIENT_ID'),env('AZURE_AD_CLIENT_SECRET'));
        //print_r($app);
        try{
            $validated = $app->validateUserToken($oauthAccessToken);
        } catch (\Exception $e) {
            return $e;
        }

        if($validated)
        {
            //Add user by oid
            $user = User::where('azure_id', $validated->oid)->first();
            if(!$user)
            {
                //create new user
                $user = new User;
                $user->azure_id = $validated->oid;
                if(isset($validated->idtyp) &&  $validated->idtyp == "app")
                {
                    $user->userPrincipalName = $validated->azp;
                } else {
                    $user->userPrincipalName = $validated->preferred_username;
                }
                $user->save();
            }
            $this->user = $user;
            if($validated->roles)
            {
                //ADD ROLES to bouncer and map user to roles
                foreach($validated->roles as $role)
                {
                    $user->assign($role);
                }
            }
        }

    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }
    }

    /**
     * Get the JSON params from the current request.
     *
     * @return string
     */
    /*
        public function getJsonParams()
        {
            $jsondata = $this->request->query('jsondata');
            return (!empty($jsondata) ? json_decode($jsondata, TRUE) : NULL);
        }
    /**/

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return string|null
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $user->getAuthIdentifier();
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return is_null($this->user);
    }

    /**
     * Set the current user.
     *
     * @param  array $user User info
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }
}