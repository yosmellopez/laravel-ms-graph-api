<?php

namespace Ylplabs\LaravelMsGraphApi\Facades;

use Illuminate\Support\Facades\Facade;

class MsGraphAPI extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'msgraphapi';
    }
}
