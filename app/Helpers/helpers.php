<?php

if (!function_exists('toast')) {
    function toast(string $message, string $type = 'success')
    {
        session()->flash($type, $message);
    }
}
