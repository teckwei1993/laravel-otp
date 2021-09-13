<?php 

namespace Teckwei1993\Otp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

class Otp
{
    private $format;
    private $customize;
    private $length;
    private $separator;
    private $expires;
    private $attempts;
    private $repeated;
    private $sensitive;
    private $data;

    public function __construct()
    {
        foreach(['format','customize','length','separator','expires','attempts','repeated','sensitive','data'] as $value){
            $this->{$value} = config('otp.'.$value);
        }
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

    public function generate(string $identifier = null, array $options = []): string
    {
        if(!empty($options)) foreach(['format','customize','length','separator','expires','repeated','sensitive','data'] as $value){
            if(isset($options[$value])) $this->{$value} = $options[$value];
        }

        if($identifier === null) $identifier = session()->getId();
        $array = $this->repeated ? $this->readData(config('otp.cache_prefix').$identifier, []) : [];
        $password = $this->generateNewPassword();
        if(!$this->sensitive) $password = strtoupper($password);
        $array[md5($password)] = [
            'expires' => time()+$this->expires*60,
            'data' => $this->data
        ];
        $this->writeData(config('otp.cache_prefix').$identifier, $array, $this->expires*60);
        return $password;
    }

    public function validate(string $identifier = null, string $password = null, array $options = []): object
    {
        if(!empty($options)) foreach(['attempts','sensitive'] as $value){
            if(isset($options[$value])) $this->{$value} = $options[$value];
        }

        if($password === null){
            if($identifier === null){
                throw new \Exception("Validate parameter can not be null");
            }
            $password = $identifier;
            $identifier = null;
        }

        if($identifier === null) $identifier = session()->getId();
        $attempt = $this->readData(config('otp.cache_prefix').'_attempt_'.$identifier, 0);
        if($attempt >= $this->attempts){
            return (object) [
                'status' => false,
                'error' => 'max_attempt',
            ];
        }

        $codes = $this->readData(config('otp.cache_prefix').$identifier, []);
        if(!$this->sensitive) $password = strtoupper($password);

        if(!isset($codes[md5($password)])){
            $this->writeData(config('otp.cache_prefix').'_attempt_'.$identifier, $attempt+1);
            return (object) [
                'status' => false,
                'error' => 'invalid',
            ];
        }

        if(time() > $codes[md5($password)]['expires']){
            $this->writeData(config('otp.cache_prefix').'_attempt_'.$identifier, $attempt+1);
            return (object) [
                'status' => false,
                'error' => 'expired',
            ];
        }

        $this->forget($identifier);
        return (object) [
            'status' => true,
            'data' => $codes[md5($password)]['data']
        ];
    }

    public function forget(string $identifier = null)
    {
        if($identifier === null) $identifier = session()->getId();
        $this->deleteData(config('otp.cache_prefix').$identifier);
        $this->deleteData(config('otp.cache_prefix').'_attempt_'.$identifier);
        return true;
    }

    private function generateNewPassword(): string
    {
        try{
            $formats = [
                'string' => $this->sensitive ? '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ' : '23456789ABCDEFGHJKLMNPQRSTUVWXYZ',
                'numeric' => '0123456789',
                'numeric-no-zero' => '123456789',
                'customize' => $this->customize
            ];

            $lengths = is_array($this->length) ? $this->length : [$this->length];

            $password = [];
            foreach($lengths as $length){
                $password[] = substr(str_shuffle(str_repeat($x=$formats[$this->format], ceil($length/strlen($x)) )),1, $length);
            }

            return implode($this->separator, $password);
        }catch(\Exception $e){
            throw new \Exception("Fail to generate password, please check the format is correct.");
        }    
    }

    private function writeData(string $key, $value)
    {
        return Cache::put($key, $value, $this->expires*60*3);
    }

    private function readData(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    private function deleteData(string $key)
    {
        return Cache::forget($key);
    }
}