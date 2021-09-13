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
$code = Otp::generate('reg:name@domain.com');
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

// in controller

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

// in controller request validate

$request->validate([
    'code' => ['required', new OtpValidate()]
]);
```

* The setting without identifier will automatically use the session ID as the default, and the OTP generation and verification will be completed in same session (browser's cookies).  

## Advanced Usage

### Generate OTP with option

```php
$code = Otp::setLength(8)->setFormat('string')->setExpires(60)->setRepeated(false)->generate('identifier-key-here');

// or

$code = Otp::generate('identifier-key-here', [
    'length' => 8,
    'format' => 'string',
    'expires' => 60,
    'repeated' => false
]);
```

* `setLength($length)`: The length of the password.
* `setFormat($format)`: The format option allows you to decide which generator implementation to be used when generating new passwords. Options: 'string','numeric','numeric-no-zero','customize'
* `setExpires($minutes)`: The expiry time of the password in minutes.
* `setRepeated($boolean)`: The repeated of the password. The previous password is valid when new password generated until either one password used or itself expired

### Generate OTP with customize password

```php
$code = Otp::setCustomize('12345678ABC@#$')->generate('identifier-key-here');
```

* `setCustomize($string)`: Random letter from the customize string

### Validate OTP with specific attempt times

```php
$code = Otp::setAttempts(3)->validate('identifier-key-here', 'password-here');
```

* `setAttempts($times)`: The number of incorrect password attempts

### Validate OTP with case sensitive

```php
$code = Otp::setSensitive(true)->generate('identifier-key-here');

// validate

$result = Otp::setSensitive(true)->validate('identifier-key-here', $code);

// in controller request validate

use Teckwei1993\Otp\Rules\OtpValidate;

$request->validate([
    'code' => ['required', new OtpValidate('identifier-key-here', ['sensitive' => true])]
]);
```

* `setSensitive($boolean)`: Requiring correct input of uppercase and lowercase letters

### Generate OTP with seperate password

```php
$code = Otp::setLength([4,3,4])->generate('identifier-key-here');
```
**OTP Sample**

```text
3526-126-3697
```

* `setLength($array)`: The length of the password, use array to separate each length.

### Validate OTP with extra data

```php
$code = Otp::setData(['user_id' => auth()->id()])->generate('login-confirmation');

// validate

$result = Otp::validate('login-confirmation', $code);
```

**On Success Response**

```object
{
  "status": true,
  "data": [
    "user_id": 10
  ]
}
```

* `setData($var)`: Allows you to get the extra data of OTP

## Contribution

All contributions are welcome! 😄

## License

The MIT License (MIT).