<?php

namespace RudovskiyPO;

abstract class Middleware
{
    protected $configs;

    public function __construct($configs = [])
    {
        $this->configs = $configs;
    }

    abstract function run();
}
