<?php

namespace App\Traits;

trait BindsJwtToPassword
{
    public function sessionFingerprint(): string
    {
        // Never expose the password hash in a readable JWT payload.
        return hash_hmac('sha256', get_class($this).'|'.$this->getKey().'|'.$this->getAuthPassword(), (string) config('app.key'));
    }

    public function getJWTCustomClaims()
    {
        return ['pwdv' => $this->sessionFingerprint()];
    }
}
