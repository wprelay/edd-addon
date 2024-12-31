<?php

namespace EDDA\Affiliate\App;

defined('ABSPATH') or exit;

use Cartrabbit\Request\Response;

abstract class Collection
{

    public static function collection($data, $to_browser = true)
    {
        $response = (new static)->toArray(...$data);

        if ($to_browser) {
            return Response::success($response);
        }

        return $response;
    }
}

