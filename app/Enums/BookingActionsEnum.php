<?php

namespace App\Enums;

use ReflectionClass;

abstract class BookingActionsEnum extends Enum {
    const Outbound    = 0 ;
    const Inbound     = 1 ;
    const Clearance   = 2 ;
}

abstract class Enum {
    static function getKeys(){
        $class = new ReflectionClass(get_called_class());
        return array_keys($class->getConstants());
    }
}
