<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Model;

class Visit extends Model
{
    protected static $table = 'visits';

    public function createTable()
    {
        $charset = static::getCharSetCollate();

        $table = static::getTableName();

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT ,
            affiliate_id BIGINT UNSIGNED,
            referral_code VARCHAR(255) NOT NULL,
            ip_address   VARCHAR(255) NOT NULL,
            landing_url  VARCHAR(255) NOT NULL,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at timestamp NULL,
            PRIMARY KEY (id)
            ) {$charset};";
    }
}

