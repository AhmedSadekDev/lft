<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CustomerPasswordInvitation
{
    public function setPassword(string $model, string $token, string $password)
    {
        return DB::transaction(function () use ($model, $token, $password) {
            $account = $model::where('session_id', $token)->lockForUpdate()->first();
            abort_unless($account, 403);
            $jwt = app('tymon.jwt')->setToken($token);
            $payload = $jwt->check(true);
            abort_unless($payload && $jwt->checkSubjectModel($model), 403);
            $claim = $payload->get('pwdv');
            abort_unless((string) $payload->get('sub') === (string) $account->getKey()
                && is_string($claim) && hash_equals($account->sessionFingerprint(), $claim), 403);
            $account->update(['password' => $password, 'session_id' => null]);
            return $account;
        });
    }
}
