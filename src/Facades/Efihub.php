<?php

namespace Efihub\Facades;

use Illuminate\Support\Facades\Facade;

class Efihub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Efihub\EfihubClient::class;
    }
}
