<?php

class Database {
    private $conn;

    public function __construct() {
        try {
            // Normalize host: use TCP/IP instead of unix socket when appropriate
            $host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
            if ($host === 'localhost') {
                // force TCP loopback to avoid PDO using unix socket
                $host = '127.0.0.1';
            }

            $dsn = 'mysql:host=' . $host;
            if (defined('DB_PORT') && DB_PORT) {
                $dsn .= ';port=' . DB_PORT;
            } elseif (getenv('DB_PORT')) {
                $dsn .= ';port=' . getenv('DB_PORT');
            }
            $dsn .= ';dbname=' . (defined('DB_NAME') ? DB_NAME : getenv('DB_NAME')) . ';charset=utf8mb4';

            $this->conn = new PDO(
                $dsn,
                defined('DB_USER') ? DB_USER : getenv('DB_USER'),
                defined('DB_PASS') ? DB_PASS : getenv('DB_PASS'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
