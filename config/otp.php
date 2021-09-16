<?php

return [

	/*
     * The format option allows you to decide
     * which generator implementation to be used when
     * generating new passwords.
     *
     * Here are the options:
     *  - string
     *  - numeric
     *  - numeric-no-zero
     *  - customize
     */

    'format' => env('OTP_FORMAT', 'numeric'),

    /*
    * The customize option required when option is customize.
    */

    'customize' => '123456789ABCDEFG@#$%',

	/*
     * The length of the password.
     */

	'length' => env('OTP_LENGTH', 6),

    /*
     * The separator of the password.
     */

    'separator' => '-',

	/*
     * Requiring correct input of uppercase and lowercase letters.
     */

	'sensitive' => env('OTP_SENSITIVE', false),

	/*
     * The expiry time of the password in minutes.
     */

    'expires' => env('OTP_EXPIRES_TIME', 15),

    /*
     * The number of incorrect password attempts.
     */

    'attempts' => env('OTP_ATTEMPT_TIMES', 5),

	/*
     * The repeated of the password.
     * The previous password is valid when new password generated
     * until either one password used or itself expired.
     */

    'repeated' => env('OTP_REPEATED', true),

    /*
     * The disposable of the Otp identifier.
     * The different password is not valid when same identifier password used.
     */

    'disposable' => true,

	/*
     * The prefix of the cache key to be used to store.
     */

    'prefix' => 'OTPPX_',

    /*
     * Demo mode for development purposes, no need to use real password to validate.
     */

    'demo' => env('OTP_DEMO', false),
    'demo_passwords' => ['1234','123456','12345678']

];