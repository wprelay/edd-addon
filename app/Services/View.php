<?php

namespace EDDA\Affiliate\App\Services;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\App;
use RelayWp\Affiliate\App\Helpers\AssetHelper;

class View
{
    public static function instance()
    {
        return new static();
    }

    public static function render($path, $data = [])
    {
        return static::instance()->view($path, array_merge(['app' => App::make()], $data));
    }

    public function view($path, $data, $print = true)
    {
        $file = RWPA_PLUGIN_PATH . 'resources/' . $path . '.php';
        return AssetHelper::renderTemplate($file, $data);
    }
}
