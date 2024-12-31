<?php

namespace EDDA\Affiliate\App\Helpers;

defined('ABSPATH') or exit;

class Util
{

    public static function isMethodExists($object_or_class, $method): bool
    {
        return (is_object($object_or_class) || is_string($object_or_class)) && method_exists($object_or_class, $method);
    }
}

