<?php
namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class PurifyService
{
    protected $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li');
        $this->purifier = new HTMLPurifier($config);
    }

    public function purify($input)
    {
        return $this->purifier->purify($input);
    }
}


