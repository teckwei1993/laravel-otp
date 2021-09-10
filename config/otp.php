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
     *  - customize
     */

    'format' => env('OTP_FORMAT', 'numeric'),

    /*
    * The customize option required when option is customize
    */

    'customize' => '123456789ABCDEFG@#$%',

	/*
     * The length of the password.
     */

	'length' => env('OTP_LENGTH', 6),

	/*
     * Requiring correct input of uppercase and lowercase letters
     */

	'sensitive' => false,

	/*
     * The expiry time of the password in minutes.
     */

    'expires' => env('OTP_EXPIRES_TIME', 15),

    /*
     * The number of incorrect password attempts
     */

    'attempts' => env('OTP_ATTEMPT_TIMES', 5),

	/*
     * The repeated of the password
     * The previous password is valid when new password generated
     * until either one password used or itself expired
     */

    'repeated' => true,

	/*
     * The prefix/tag of the cache key to be used to store
     */

    'cache_prefix' => 'OTPPX_',
    'cache_tag' => 'otptag'

];