<?php 

namespace Teckwei1993\Otp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

class Otp
{
    private $format = 'numeric';
    private $customize;
    private $length = 6;
    private $separator = '-';
    private $sensitive = false;
    private $expires = 15;
    private $attempts = 5;
    private $repeated = true;
    private $disposable = true;
    private $prefix = 'OTPPX_';
    private $data;
    private $skip = false;
    private $demo = false;
    private $demo_passwords = ['1234','123456','12345678'];

    public function __construct()
    {
        foreach(['format','customize','length','separator','sensitive','expires','attempts','repeated','disposable','prefix','data','demo','demo_passwords'] as $value){
            if(!empty(config('otp.'.$value))) $this->{$value} = config('otp.'.$value);
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
        if(!empty($options)) foreach(['format','customize','length','separator','sensitive','expires','repeated','prefix','data'] as $value){
            if(isset($options[$value])) $this->{$value} = $options[$value];
        }

        if($identifier === null) $identifier = session()->getId();
        $array = $this->repeated ? $this->readData($identifier, []) : [];
        $password = $this->generateNewPassword();
        if(!$this->sensitive) $password = strtoupper($password);
        $array[md5($password)] = [
            'expires' => time()+$this->expires*60,
            'data' => $this->data
        ];
        $this->writeData($identifier, $array);
        return $password;
    }

    public function validate(string $identifier = null, string $password = null, array $options = []): object
    {
        if(!empty($options)) foreach(['attempts','sensitive','disposable','skip'] as $value){
            if(isset($options[$value])) $this->{$value} = $options[$value];
        }

        if($password === null){
            if($identifier === null){
                throw new \Exception("Validate parameter can not be null");
            }
            $password = $identifier;
            $identifier = null;
        }

        if($this->demo && in_array($password,$this->demo_passwords)){
            return (object) [
                'status' => true,
                'demo' => true
            ];
        }

        if($identifier === null) $identifier = session()->getId();
        $attempt = $this->readData('_attempt_'.$identifier, 0);
        if($attempt >= $this->attempts){
            return (object) [
                'status' => false,
                'error' => 'max_attempt',
            ];
        }

        $codes = $this->readData($identifier, []);
        if(!$this->sensitive) $password = strtoupper($password);

        if(!isset($codes[md5($password)])){
            $this->writeData('_attempt_'.$identifier, $attempt+1);
            return (object) [
                'status' => false,
                'error' => 'invalid',
            ];
        }

        if(time() > $codes[md5($password)]['expires']){
            $this->writeData('_attempt_'.$identifier, $attempt+1);
            return (object) [
                'status' => false,
                'error' => 'expired',
            ];
        }

        $response = [
            'status' => true,
        ];

        if(!empty($codes[md5($password)]['data'])){
            $response['data'] = $codes[md5($password)]['data'];
        }

        if(!$this->skip) $this->forget($identifier, !$this->disposable ? $password : null);
        $this->resetAttempt($identifier);

        return (object) $response;
    }

    public function forget(string $identifier = null, string $password = null)
    {
        if($identifier === null) $identifier = session()->getId();
        if($password !== null){
            $codes = $this->readData($identifier, []);
            if(!isset($codes[md5($password)])){
                return false;
            }
            unset($codes[md5($password)]);
            $this->writeData($identifier, $codes);
            return true;
        }

        $this->deleteData($identifier);
        return true;
    }

    public function resetAttempt(string $identifier = null)
    {
        if($identifier === null) $identifier = session()->getId();
        $this->deleteData('_attempt_'.$identifier);
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
        $expires = $this->expires;
        if(app()->version() >= "5.8"){
            $expires *= 60;
        }
        return Cache::put($this->prefix.$key, $value, $expires*3);
    }

    private function readData(string $key, $default = null)
    {
        return Cache::get($this->prefix.$key, $default);
    }

    private function deleteData(string $key)
    {
        return Cache::forget($this->prefix.$key);
    }
}