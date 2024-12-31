<?php

namespace EDDA\Affiliate\App\Traits;

defined('ABSPATH') or exit;

trait useScopes
{
    public function nameLike($column1, $column2, $value, $isWhere = true)
    {
        if ($isWhere) {
            $query = $this->where("$column1 LIKE %s", ["%{$value}%"]);
        } else {
            $query = $this->orWhere("$column1 LIKE %s", ["%{$value}%"]);
        }

        return $query->orWhere("$column2 LIKE %s", ["%{$value}%"])
            ->orWhere("CONCAT($column1, ' ', $column2) LIKE %s", ["%{$value}%"]);
    }

    public function orderIdLike($column, $value, $isWhere = true)
    {
        if ($isWhere) {
            $query = $this->where("$column LIKE %s", ["%{$value}%"]);
        } else {
            $query = $this->orWhere("$column LIKE %s", ["%{$value}%"]);
        }

        if (strpos($value, '#') === 0) {
            $value = substr($value, 1);
        }

        return $query->orWhere("$column LIKE %s", ["%{$value}%"]);
    }
}

