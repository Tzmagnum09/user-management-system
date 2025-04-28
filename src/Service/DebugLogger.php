<?php

namespace App\Service;

class DebugLogger
{
    public static function log(string $message): void
    {
        file_put_contents(
            __DIR__ . '/../../var/log/reset_password_debug.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }
}