<?php

namespace Hexlet\Code;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }

        return self::$connection;
    }

    private static function createConnection(): PDO
    {
        if (!isset($_ENV['DATABASE_URL'])) {
            throw new PDOException('DATABASE_URL environment variable is not set');
        }

        $databaseUrl = $_ENV['DATABASE_URL'];
        $params = parse_url($databaseUrl);
        
        if ($params === false) {
            throw new PDOException('Invalid DATABASE_URL format');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $params['host'],
            $params['port'] ?? '5432',
            ltrim($params['path'], '/')
        );

        $username = $params['user'] ?? '';
        $password = $params['pass'] ?? '';

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Failed to connect to database: ' . $e->getMessage());
        }
    }
} 