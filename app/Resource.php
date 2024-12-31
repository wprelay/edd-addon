<?php

namespace EDDA\Affiliate\App;

defined('ABSPATH') or exit;

use Cartrabbit\Request\Response;

abstract class Resource
{
    public static function resource(array $params)
    {
        $response = (new static)->toArray(...$params);

        return Response::success($response);
    }
}

