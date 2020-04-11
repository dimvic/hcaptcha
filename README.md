# Usage

## 1. Set the environment variables

- `hcaptcha_sitekey`
- `hcaptcha_secret`
- `hcaptcha_log_path` if you want responses to be logged

Using `putenv('key=value')`, `$_ENV['key'] = 'value'` or `$_SERVER['key'] = 'value'`

Alternatively, class methods can be called with the key/secret as parameters.

Logging is possible only by setting the environment variable.

## 2. Include hCaptcha in a form

`dimvic\HCaptcha::getDiv(?string $siteKey = null): string`

or

`dimvic\HCaptcha::getHiddenInput(?string $siteKey = null): string`

## 3. Validate the result

`dimvic\HCaptcha::validate(?string $token = null, ?string $remoteIp = null, ?string $secret = null): bool`
