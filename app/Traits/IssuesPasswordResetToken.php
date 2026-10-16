<?php

namespace App\Traits;

use App\Models\OTP;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait IssuesPasswordResetToken
{
    public const RESET_TOKEN_TTL_MINUTES = 15;

    public const ACCOUNT_COMPANY = 'company';
    public const ACCOUNT_EMPLOYEE = 'employee';
    public const ACCOUNT_AGENT = 'agent';
    public const ACCOUNT_SUPERAGENT = 'superagent';

    protected function issueResetToken(OTP $otp): string
    {
        $this->invalidateSiblingResetTokens($otp);

        $plain = bin2hex(random_bytes(32));

        $otp->update([
            'expire_at' => Carbon::now(),
            'reset_token_hash' => hash('sha256', $plain),
            'reset_token_expires_at' => Carbon::now()->addMinutes(self::RESET_TOKEN_TTL_MINUTES),
            'reset_token_used_at' => null,
        ]);

        return $plain;
    }

    /**
     * @param  class-string<Model>  $accountClass
     */
    protected function consumeResetToken(
        string $plainToken,
        string $email,
        string $password,
        string $accountType,
        string $accountClass
    ): Model {
        return DB::transaction(function () use ($plainToken, $email, $password, $accountType, $accountClass) {
            /** @var Model|null $account */
            $account = $accountClass::where('email', $email)->first();
            if (!$account) {
                abort(404, __('auth.user_not_found'));
            }

            $fk = $this->resetTokenForeignKey($accountType);
            $hash = hash('sha256', $plainToken);

            /** @var OTP|null $otp */
            $otp = OTP::where('reset_token_hash', $hash)
                ->where($fk, $account->id)
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                abort(403, __('auth.invalid_reset_token'));
            }

            if ($otp->reset_token_used_at !== null) {
                abort(403, __('auth.used_reset_token'));
            }

            if (!$otp->reset_token_expires_at || Carbon::now()->isAfter($otp->reset_token_expires_at)) {
                abort(403, __('auth.expired_reset_token'));
            }

            // Mutator on Company/Employee/Agent/Superagent hashes once — do not Hash::make here.
            $account->update(['password' => $password]);

            $otp->update([
                'reset_token_used_at' => Carbon::now(),
            ]);

            $this->invalidateSiblingResetTokens($otp);

            return $account->fresh();
        });
    }

    protected function clearResetTokenFields(): array
    {
        return [
            'reset_token_hash' => null,
            'reset_token_expires_at' => null,
            'reset_token_used_at' => null,
        ];
    }

    protected function invalidateSiblingResetTokens(OTP $otp): void
    {
        $accountType = $this->resolveAccountTypeFromOtp($otp);
        if ($accountType === null) {
            return;
        }

        $fk = $this->resetTokenForeignKey($accountType);
        $accountId = $otp->{$fk};
        if (!$accountId) {
            return;
        }

        OTP::where($fk, $accountId)
            ->where('id', '!=', $otp->id)
            ->whereNotNull('reset_token_hash')
            ->whereNull('reset_token_used_at')
            ->update([
                'reset_token_hash' => null,
                'reset_token_expires_at' => null,
                'reset_token_used_at' => Carbon::now(),
            ]);
    }

    protected function resetTokenForeignKey(string $accountType): string
    {
        return match ($accountType) {
            self::ACCOUNT_COMPANY => 'company_id',
            self::ACCOUNT_EMPLOYEE => 'employee_id',
            self::ACCOUNT_AGENT => 'agent_id',
            self::ACCOUNT_SUPERAGENT => 'superagent_id',
            default => throw new \InvalidArgumentException('Unknown reset account type'),
        };
    }

    protected function resolveAccountTypeFromOtp(OTP $otp): ?string
    {
        if ($otp->company_id) {
            return self::ACCOUNT_COMPANY;
        }
        if ($otp->employee_id) {
            return self::ACCOUNT_EMPLOYEE;
        }
        if ($otp->agent_id) {
            return self::ACCOUNT_AGENT;
        }
        if ($otp->superagent_id) {
            return self::ACCOUNT_SUPERAGENT;
        }

        return null;
    }
}
