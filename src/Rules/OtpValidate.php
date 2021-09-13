<?php

namespace Teckwei1993\Otp\Rules;

use Illuminate\Contracts\Validation\Rule;
use Teckwei1993\Otp\OtpFacade as Otp;

class OtpValidate implements Rule
{
    protected $identifier;
    protected $options;
    protected $attribute;
    protected $error;

    public function __construct(string $identifier = null, array $options = [])
    {
        $this->identifier = $identifier ?: session()->getId();
        $this->options = $options;
    }

    public function passes($attribute, $value): bool
    {
        $result = Otp::validate($this->identifier, $value, $this->options);
        if($result->status !== true){
            $this->attribute = $attribute;
            $this->error = $result->error;
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return __('otp::messages.'.$this->error, [
            'attribute' => $this->attribute
        ]);
    }
}