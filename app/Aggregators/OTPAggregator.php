<?php

namespace App\Aggregators;

class OTPAggregator
{
    static public function generateOTP(
        int $otp_length = 4,
        bool $numbers_except_0 = true,
        bool $include_0 = true,
        bool $include_lc_chars = false,
        bool $include_uc_chars = false
    ) {
        if (
            !$numbers_except_0 &&
            !$include_0 &&
            !$include_lc_chars &&
            !$include_uc_chars
        ) {
            return false;
        }

        $available_chars = "";
        if ($include_0) {
            $available_chars .= "0";
        }
        if ($numbers_except_0) {
            $available_chars .= "123456789";
        }
        if ($include_lc_chars) {
            $available_chars .= "abcdefghijklmnopqrstuvwxyz";
        }
        if ($include_uc_chars) {
            $available_chars .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }

        if (empty($available_chars)) {
            return false;
        }

        $otp = "";
        $max_index = strlen($available_chars) - 1;
        for ($i = 0; $i < $otp_length; $i++) {
            $otp .= $available_chars[random_int(0, $max_index)];
        }

        return $otp;
    }
}
