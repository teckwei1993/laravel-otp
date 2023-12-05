<?php

namespace Teckwei1993\Otp;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generate(string $identifier = null, array $options = [])
 * @method static object validate(string $identifier = null, string $password = null, array $options = [])
 * @method static bool forget(string $identifier = null, string $password = null)
 * @method static bool resetAttempt(string $identifier = null)
 */
class OtpFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'otp';
    }
}