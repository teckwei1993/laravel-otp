# Laravel OTP

## Introduction

A package for Laravel One Time Password (OTP) generator and validation without Eloquent Model, since it done by *Cache*.
The cache connection same as your laravel cache config and it supported: "apc", "array", "database", "file", "memcached", "redis"

## Installation

Install via composer

```bash
composer require teckwei1993/laravel-otp
```

Publish config and language file

```bash
php artisan vendor:publish --provider="Teckwei1993\Otp\OtpServiceProvider"
```

## Usage

### Generate OTP

```php
Otp::generate(string $identifier)
```

* `$identifier`: The identity that will be tied to the OTP.

#### Sample

```php
$password = Otp::generate('reg:name@domain.com');
```

This will generate a OTP that will be valid for 15 minutes.

### Validate OTP

```php
Otp::validate(string $identifier, string $password)
```

* `$identifier`: The identity that is tied to the OTP.
* `$password`: The password tied to the identity.

#### Sample

```php
$result = Otp::validate('reg:name@domain.com', '123456');
```

#### Responses

**On Success**

```object
{
  "status": true
}
```

**Invalid OTP**

```object
{
  "status": false,
  "error": "invalid"
}
```

**Expired**

```object
{
  "status": false,
  "error": "expired"
}
```

**Max attempt**

```object
{
  "status": false,
  "error": "max_attempt"
}
```

* Reached the maximum allowed attempts, default 10 times with each identifier

### Validate OTP by Laravel Validation

```php
// in a `FormRequest`

use Teckwei1993\Otp\Rules\OtpValidate;

public function rules()
{
    return [
        'code' => ['required', new OtpValidate('change-email:name@domain.com')]
    ];
}

// in a controller

$request->validate([
    'code' => ['required', new OtpValidate('change-email:name@domain.com')]
]);
```

### Validate OTP by session id

```php
// Otp class

$result = Otp::validate('123456');

// in a `FormRequest`

use Teckwei1993\Otp\Rules\OtpValidate;

public function rules()
{
    return [
        'code' => ['required', new OtpValidate()]
    ];
}

// in a controller

$request->validate([
    'code' => ['required', new OtpValidate()]
]);
```

* The setting without identifier will automatically use the session ID as the default, and the OTP generation and verification will be completed in same session (browser's cookies).  

## Advanced Usage

### Generate OTP with options

```php
$password = Otp::setLength(8)->setFormat('string')->setExpires(60)->setRepeated(false)->generate('identifier-key-here');

// or array option

$password = Otp::generate('identifier-key-here', [
    'length' => 8,
    'format' => 'string',
    'expires' => 60,
    'repeated' => false
]);
```

* `setLength($length)`: The length of the password. Default: 6
* `setFormat($format)`: The format option allows you to decide which generator implementation to be used when generating new passwords. Options: 'string','numeric','numeric-no-zero','customize'. Default: "numeric"
* `setExpires($minutes)`: The expiry time of the password in minutes. Default: 15
* `setRepeated($boolean)`: The repeated of the password. The previous password is valid when new password generated until either one password used or itself expired. Default: true

### Generate OTP with customize password

```php
$password = Otp::setCustomize('12345678ABC@#$')->generate('identifier-key-here');
```

* `setCustomize($string)`: Random letter from the customize string

### Validate OTP with specific attempt times

```php
$password = Otp::setAttempts(3)->validate('identifier-key-here', 'password-here');
```

* `setAttempts($times)`: The number of incorrect password attempts. Default: 5

### Validate OTP with case sensitive

```php
$password = Otp::setSensitive(true)->generate('identifier-key-here');

// validate

$result = Otp::setSensitive(true)->validate('identifier-key-here', 'password-here');

// in controller

use Teckwei1993\Otp\Rules\OtpValidate;

$request->validate([
    'code' => ['required', new OtpValidate('identifier-key-here', ['sensitive' => true])]
]);
```

* `setSensitive($boolean)`: Requiring correct input of uppercase and lowercase letters. Default: true

### Generate OTP with seperate password

```php
$password = Otp::setLength([4,3,4])->setSeparator(':')->generate('identifier-key-here');
```
**Sample password**

```text
3526:126:3697
```

* `setLength($array)`: The length of the password, use array to separate each length.
* `setSeparator($string)`: The separator of the password. Default: "-"

### Validate OTP with extra data

```php
$password = Otp::setData(['user_id' => auth()->id()])->generate('login-confirmation');
```

* `setData($var)`: Allows you to get the extra data of OTP.

```php
// validate

$result = Otp::setDisposable(false)->validate('login-confirmation', 'password-here');

// in controller

use Teckwei1993\Otp\Rules\OtpValidate;

$request->validate([
    'code' => ['required', new OtpValidate('login-confirmation', ['disposable' => false])]
]);
```

* `setDisposable($boolean)`: The disposable of the Otp identifier, the different password is not valid when same identifier password used. Default: true

**On Success Response**

```object
{
  "status": true,
  "data": [
    "user_id": 10
  ]
}
```

* When you set disposable to `false`, you are able support different password with different extra data for different user in the same identifier key of the OTP.

### Validate OTP with skip using

```php
// validate

$result = Otp::setSkip(true)->validate('identifier-key-here', 'password-here');

// in controller

use Teckwei1993\Otp\Rules\OtpValidate;

$request->validate([
    'code' => ['required', new OtpValidate('identifier-key-here', ['skip' => true])]
]);
```

* `setSkip($boolean)`: Skip using the password when validate, which means you can reuse the password again. Default: false

### Delete OTP

```php
Otp::forget('identifier-key-here');
```

* Delete all password with this specific identifier

### Delete specific password

```php
Otp::forget('identifier-key-here', 'password-here');
```

### Reset attempt times

```php
Otp::resetAttempt('identifier-key-here');
```

## Contribution

All contributions are welcome! 😄

## License

The MIT License (MIT).