<?php

namespace dimvic;

use Error;
use RuntimeException;
use function array_filter;
use function file_put_contents;
use function getenv;
use function http_build_query;
use function is_string;
use function mkdir;

class HCaptcha
{
    protected const URL_SCRIPT = 'https://hcaptcha.com/1/api.js';
    protected const URL_VERIFY = 'https://hcaptcha.com/siteverify';

    public static function getScriptUrl(): string
    {
        return self::URL_SCRIPT;
    }

    public static function getScript(): string
    {
        return '<script src="' . static::URL_SCRIPT . '" async defer></script>';
    }

    public static function getDiv(?string $siteKey = null): string
    {
        $siteKey = $siteKey ?? static::getSiteKey();

        return '<div class="h-captcha" data-sitekey="' . $siteKey . '"></div>';
    }

    public static function getHiddenInput(?string $siteKey = null): string
    {
        $siteKey = $siteKey ?? static::getSiteKey();

        return '<input type="hidden" class="h-captcha" data-sitekey="' . $siteKey . '">';
    }

    public static function validate(?string $token = null, ?string $remoteIp = null, ?string $secret = null): bool
    {
        $secret = $secret ?? static::getSecret();
        if ($token === null && is_string($tmp = ($_POST['h-captcha-response'] ?? null))) {
            $token = $tmp;
        }
        if ($remoteIp === null && is_string($tmp = ($_SERVER['REMOTE_ADDR'] ?? null))) {
            $remoteIp = $tmp;
        }

        $valid = false;

        if ($token) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query(array_filter([
                        'secret' => $secret,
                        'response' => $token,
                        'remoteip' => $remoteIp,
                    ])),
                ],
            ]);
            $response = file_get_contents(static::URL_VERIFY, false, $context);

            if ($logPath = static::getLogPath()) {
                if (!mkdir($logPath, 0755) && !is_dir($logPath)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $logPath));
                }

                file_put_contents("{$logPath}/" . date('Ymd_His'), $response);
            }

            $responseData = json_decode($response, true);
            $valid = $responseData['success'] ?? false;
        }

        return $valid;
    }

    public static function getSiteKey(): string
    {
        return static::getEnv('hcaptcha_sitekey');
    }

    public static function getSecret(): string
    {
        return static::getEnv('hcaptcha_secret');
    }

    public static function getLogPath(): string
    {
        return static::getEnv('hcaptcha_log_path', false);
    }

    protected static function getEnv(string $key, bool $required = true): ?string
    {
        static $vals = [];

        if (($vals[$key] ?? false) === false) {
            $vals[$key] = getenv($key, true) ?: getenv($key);
            if ($vals[$key] === false) {
                $vals[$key] = $_ENV[$key] ?? $_SERVER[$key] ?? null;
            }
            if ($required && $vals[$key] === null) {
                throw new Error("{$key} not found in environment");
            }
        }

        return $vals[$key];
    }
}
