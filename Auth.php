<?php

/**
 * File-backed Auth implementation for zero-DB mode
 *
 * Users are persisted to a JSON file (`users.json`) so accounts survive restarts.
 * Passwords are stored hashed. This is still not intended for production use,
 * but provides a simple persistent store without a database.
 */
class Auth {
    private $dataFile;
    private $users = [];

    public function __construct($dataFile = null) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if ($dataFile === null) {
            $dataFile = defined('USERS_FILE') ? USERS_FILE : (__DIR__ . '/users.json');
        }
        $this->dataFile = $dataFile;
        if (!file_exists($this->dataFile)) {
            @file_put_contents($this->dataFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        $this->reloadUsers();
    }

    private function reloadUsers() {
        $json = @file_get_contents($this->dataFile);
        $data = json_decode($json, true);
        $this->users = is_array($data) ? $data : [];
    }

    private function writeUsers() {
        $tmp = $this->dataFile . '.tmp';
        file_put_contents($tmp, json_encode(array_values($this->users), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        rename($tmp, $this->dataFile);
        // reload to ensure canonical form
        $this->reloadUsers();
    }

    public function signup($name, $email, $password, $confirmPassword) {
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Please enter a valid email'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'error' => 'Passwords do not match'];
        }

        // Check if email already exists in file store
        foreach ($this->users as $u) {
            if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
                return ['success' => false, 'error' => 'Email already registered'];
            }
        }

        $maxId = 0;
        foreach ($this->users as $u) {
            $maxId = max($maxId, (int)($u['id'] ?? 0));
        }
        $newId = $maxId + 1;
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $user = [
            'id' => $newId,
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->users[] = $user;
        $this->writeUsers();

        // Set session as logged in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];

        return ['success' => true];
    }

    public function login($email, $password) {
        // Validation
        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }

        $found = null;
        foreach ($this->users as $u) {
            if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
                $found = $u;
                break;
            }
        }

        if (!$found) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if (!password_verify($password, $found['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Set session
        $_SESSION['user_id'] = $found['id'];
        $_SESSION['user'] = [
            'id' => $found['id'],
            'name' => $found['name'],
            'email' => $found['email']
        ];

        return ['success' => true];
    }

    public function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['user']);
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
        return true;
    }

    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) return null;
        $uid = $_SESSION['user_id'];
        foreach ($this->users as $u) {
            if ((int)($u['id'] ?? 0) === (int)$uid) {
                return ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email']];
            }
        }
        // user id in session but not found in file (stale session)
        unset($_SESSION['user_id']);
        unset($_SESSION['user']);
        return null;
    }

    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
}
