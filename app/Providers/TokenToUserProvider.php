<?php namespace App\Extensions;

use App\Models\Apikey;
use App\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Str;

class TokenToUserProvider implements UserProvider
{
    private $key;
    private $user;

    public function __construct (User $user, Apikey $key) {
        $this->user = $user;
        $this->key = $key;
    }

    public function retrieveById ($id) {
        return $this->user->find($id);
    }

    public function retrieveByToken ($id, $key) {
        $key = $this->key->with('user')->where($id, $key)->first();

        return $key && $key->user ? $key->user : null;
    }

    public function updateRememberToken (Authenticatable $user, $token) {
        // update via remember token not necessary
    }

    public function retrieveByCredentials (array $credentials) {
        // implementation upto user.
        // how he wants to implement -
        // let's try to assume that the credentials ['username', 'password'] given
        $user = $this->user;
        foreach ($credentials as $credentialKey => $credentialValue) {
            if (!Str::contains($credentialKey, 'password')) {
                $user->where($credentialKey, $credentialValue);
            }
        }

        return $user->first();
    }

    public function validateCredentials (Authenticatable $user, array $credentials) {
        $plain = $credentials['password'];

        return app('hash')->check($plain, $user->getAuthPassword());
    }
}