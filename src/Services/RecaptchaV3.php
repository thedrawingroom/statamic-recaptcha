<?php

namespace Anakadote\StatamicRecaptcha\Services;

use Illuminate\Support\Facades\Log;

class RecaptchaV3
{
    /**
     * Verify reCAPTCHA v3.
     *
     * @param  string  $token      reCAPTCHA token
     * @param  string  $action     reCAPTCHA action
     * @param  float   $threshold  Minimum reCAPTCHA score
     * @return bool
     */
    public static function verify($token, $action, $threshold = .5)
    {
        $threshold = $threshold ?? .5; // In case null is provided for the threshold.

        $remoteip = match (true) {
            ! empty($_SERVER['X_FORWARDED_FOR']) => trim(explode(',', $_SERVER['X_FORWARDED_FOR'])[0]),
            ! empty($_SERVER['REMOTE_ADDR']) => $_SERVER['REMOTE_ADDR'],
            default => null
        };

        $args = [
            'secret'   => config('recaptcha.recaptcha_v3.secret_key'),
            'response' => $token,
            'remoteip' => $remoteip,
        ];

        $url = 'https://www.google.com/recaptcha/api/siteverify?' . http_build_query($args);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($output);

        if (
            ! $result ||
            ! $result->success ||
            $result->score < $threshold ||
            $result->action !== $action
        ) {
            if (config('recaptcha.log_failures', true)) {
                Log::info('reCAPTCHA v3 verification failure.', ['response' => json_decode($output, true)]);
            }

            return false;
        }

        return true;
    }
}
