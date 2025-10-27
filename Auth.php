<?php

class Auth {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
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

        // Check if email already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if ($existing) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $result = $this->db->query(
            "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())",
            [$name, $email, $hashedPassword]
        );

        if ($result) {
            $userId = $this->db->lastInsertId();
            $user = $this->db->fetchOne("SELECT id, name, email FROM users WHERE id = ?", [$userId]);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;

            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Registration failed'];
    }

    public function login($email, $password) {
        // Validation
        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }

        // Get user
        $user = $this->db->fetchOne(
            "SELECT id, name, email, password FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Set session
        unset($user['password']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;

        return ['success' => true];
    }

    public function logout() {
        session_destroy();
        session_start();
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
}
