<?php 

namespace Teckwei1993\Otp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

class Otp
{
    private $length;
    private $format;
    private $customize;
    private $expires;
    private $attempts;
    private $repeated;

    public function __construct()
    {
        $this->length = config('otp.length');
        $this->format = config('otp.format');
        $this->customize = config('otp.customize');
        $this->expires = config('otp.expires');
        $this->attempts = config('otp.attempts');
        $this->repeated = config('otp.repeated');
    }

    public function __call(string $method, $params)
    {
        if(substr($method, 0, 3) != 'set'){
            return;
        }

        $property = Str::camel(substr($method, 3));
        if(! property_exists($this, $property)){
            return;
        }

        $this->{$property} = $params[0] ?? null;
        if($property == 'customize'){
            $this->format = 'customize';
        }
        return $this;
    }

    public function generate(string $identifier = null): string
    {
        if($identifier === null) $identifier = session()->getId();
        $array = $this->repeated ? $this->cache()->get(config('otp.cache_prefix').$identifier, []) : [];
        $password = $this->generateNewPassword();
        if(!config('otp.sensitive')) $password = strtoupper($password);
        $array[md5($password)] = time()+$this->expires*60;
        $this->cache()->put(config('otp.cache_prefix').$identifier, $array);
        return $password;
    }

    public function validate(string $identifier = null, string $password = null): object
    {
        if($password === null){
            if($identifier === null){
                throw new \Exception("Validate parameter can not be null");
            }
            $password = $identifier;
            $identifier = null;
        }

        if($identifier === null) $identifier = session()->getId();
        $attempt = $this->cache()->get(config('otp.cache_prefix').'_attempt_'.$identifier, 0);
        if($attempt >= $this->attempts){
            return (object) [
                'status' => false,
                'error' => 'max_attempt',
            ];
        }

        $this->cache()->put(config('otp.cache_prefix').'_attempt_'.$identifier, $attempt+1);

        $codes = $this->cache()->get(config('otp.cache_prefix').$identifier, []);
        if(!config('otp.sensitive')) $password = strtoupper($password);

        if(!isset($codes[md5($password)])){
            return (object) [
                'status' => false,
                'error' => 'invalid',
            ];
        }

        if(time() > $codes[md5($password)]){
            return (object) [
                'status' => false,
                'error' => 'expired',
            ];
        }

        $this->forget($identifier);
        return (object) [
            'status' => true
        ];
    }

    public function forget(string $identifier = null)
    {
        if($identifier === null) $identifier = session()->getId();
        $this->cache()->forget(config('otp.cache_prefix').$identifier);
        $this->cache()->forget(config('otp.cache_prefix').'_attempt_'.$identifier);
    }

    private function generateNewPassword(): string
    {
        try{
             $formats = [
                'string' => config('otp.sensitive') ? '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ' : '23456789ABCDEFGHJKLMNPQRSTUVWXYZ',
                'numeric' => '0123456789',
                'numeric-no-zero' => '123456789',
                'customize' => $this->customize
            ];

            return substr(str_shuffle(str_repeat($x=$formats[$this->format], ceil($this->length/strlen($x)) )),1, config('otp.length'));
        }catch(\Exception $e){
            throw new \Exception("Fail to generate password, please check the format is correct.");
        }    
    }

    private function cache()
    {
        return Cache::setDefaultCacheTime($this->expires*60);
    }
}