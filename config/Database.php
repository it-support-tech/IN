<?php

namespace App;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'invoices';
            $user = getenv('DB_USER') ?: 'invoice_app';
            $pass = getenv('DB_PASS') ?: '';

            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        // if (self::$instance === null) {
        //     $host = 'localhost';
        //     $port = '5432';
        //     $name = 'invoices';
        //     $user = 'ntp2026';
        //     $pass = 'admin@123#';

        //     $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

        //     self::$instance = new PDO($dsn, $user, $pass, [
        //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        //         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        //         PDO::ATTR_EMULATE_PREPARES => false,
        //     ]);
        // }

        return self::$instance;
    }
}
