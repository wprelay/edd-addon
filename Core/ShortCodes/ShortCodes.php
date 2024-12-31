<?php

namespace EDDA\Affiliate\Core\ShortCodes;

defined('ABSPATH') or exit;

class ShortCodes
{
    public static function getShortCodes($classes)
    {
        return array_merge($classes, [
            GlobalRegistrationFormShortCode::class
        ]);
    }
}

