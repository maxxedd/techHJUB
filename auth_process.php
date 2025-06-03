<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        // Handle login
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!file_exists('data/users.json')) {
            header("Location: index.php?login_error=No users registered yet");
            exit();
        }

        $users = json_decode(file_get_contents('data/users.json'), true);
        $userFound = false;

        foreach ($users as $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                header("Location: dashboard.php");
                exit();
            }
        }

        header("Location: index.php?login_error=Invalid email or password");
        exit();

    } elseif ($action === 'register') {
        // Handle registration
        $username = trim($_POST['username']);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $userType = $_POST['user_type'] ?? 'customer';
        $passkey = $_POST['passkey'] ?? '';

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            header("Location: index.php?register_error=All fields are required");
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: index.php?register_error=Invalid email format");
            exit();
        }

        if (strlen($password) < 8) {
            header("Location: index.php?register_error=Password must be at least 8 characters");
            exit();
        }

        // Employee passkey verification
        if ($userType === 'employee' && $passkey !== 'techHUB') {
            // Log failed attempt
            $logs = file_exists('data/alerts.json') ? json_decode(file_get_contents('data/alerts.json'), true) : [];
            $logs[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => "Unauthorized employee registration attempt from $email"
            ];
            file_put_contents('data/alerts.json', json_encode($logs, JSON_PRETTY_PRINT));

            header("Location: index.php?register_error=Invalid passkey for employee registration");
            exit();
        }

        // Check if email exists
        $users = file_exists('data/users.json') ? json_decode(file_get_contents('data/users.json'), true) : [];
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                header("Location: index.php?register_error=Email already registered");
                exit();
            }
        }

        // Create new user
        $newUser = [
            'id' => uniqid(),
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'type' => $userType,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $users[] = $newUser;
        file_put_contents('data/users.json', json_encode($users, JSON_PRETTY_PRINT));

        $_SESSION['user'] = $newUser;
        header("Location: dashboard.php");
        exit();
    }
}

header("Location: index.php");
exit();