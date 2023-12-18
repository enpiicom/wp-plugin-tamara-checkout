<?php

namespace Tamara_Checkout\App\Support\Helpers;

class MoneyHelper
{
    /**
     * Format the amount of money for Tamara SDK
     *
     * @param $amount
     *
     * @return float
     */
    public static function formatNumber($amount): float {
        return floatval(number_format($amount, 2, ".", ""));
    }

    /**
     * Format the amount of money for general with 2 decimals
     *
     * @param $amount
     *
     * @return string
     */
    public static function formatNumberGeneral($amount): string {
        return number_format($amount, 2, ".", "");
    }
}
