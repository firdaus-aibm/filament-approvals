<?php

namespace EightyNine\Approvals\Facades;

use Illuminate\Support\Facades\Facade;

class Approval extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \EightyNine\Approvals\Approval::class;
    }
}
