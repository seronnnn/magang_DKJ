<?php

if (!function_exists('fmtIDR')) {
    function fmtIDR($v) {
        if ($v >= 1e12) return 'Rp ' . number_format($v / 1e12, 2) . 'T';
        if ($v >= 1e9)  return 'Rp ' . number_format($v / 1e9,  2) . 'B';
        if ($v >= 1e6)  return 'Rp ' . number_format($v / 1e6,  1) . 'M';
        return 'Rp ' . number_format($v);
    }
}

if (!function_exists('pctOf')) {
    function pctOf($v, $t) {
        return $t > 0 ? round($v / $t * 100, 1) : 0;
    }
}