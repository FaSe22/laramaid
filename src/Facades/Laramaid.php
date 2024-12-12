<?php

namespace Fase22\Laramaid\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Fase22\Laramaid\Laramaid
 */
class Laramaid extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Fase22\Laramaid\Laramaid::class;
    }
}
