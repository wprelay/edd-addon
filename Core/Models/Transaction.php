<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Model;

class Transaction extends Model
{
    protected static $table = 'transactions';


    const CREDIT = 'credit';
    const DEBIT = 'debit';

    public function createTable()
    {
        $table = static::getTableName();
        $charset = static::getCharSetCollate();

        return "CREATE TABLE {$table} (
                id BIGINT UNSIGNED AUTO_INCREMENT,
                affiliate_id BIGINT UNSIGNED NULL,
                transactionable_id BIGINT UNSIGNED NULL,
                transactionable_type VARCHAR(255) NULL,
                type VARCHAR(255),
                currency VARCHAR(255),
                amount DECIMAL(10, 2),
                system_note TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
                ) {$charset};";
    }
}
