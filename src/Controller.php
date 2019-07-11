<?php

namespace RudovskiyPO;

abstract class Controller
{
    protected $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }
}
