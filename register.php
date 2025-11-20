<?php
require 'db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $terms    = isset($_POST['terms']);

    // ---- VALIDATION ----
    if ($password !== $confirm) {
        $message = '<div class="alert alert-danger">Passwords do not match!</div>';
    } elseif (!$terms) {
        $message = '<div class="alert alert-danger">You must agree to terms!</div>';
    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // ---- INSERT USER ----
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password) 
            VALUES (:username, :email, :password)
        ");

        try {
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':password' => $hashed
            ]);

            $message = '<div class="alert alert-success">
                            Registered successfully! <a href="index.php">Login now</a>
                        </div>';

        } catch (PDOException $e) {
            // Assume duplicate username/email
            $message = '<div class="alert alert-danger">
                            Username or email already taken!
                        </div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
        rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm mx-auto" style="max-width: 450px;">
        <div class="card-body">
            <h2 class="text-center mb-4">Register</h2>

            <?php echo $message; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        class="form-control" 
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        class="form-control" 
                        required>
                </div>

                <div class="form-check mb-3">
                    <input 
                        type="checkbox" 
                        name="terms" 
                        class="form-check-input" 
                        id="terms" 
                        required>
                    <label class="form-check-label" for="terms">
                        Agree to terms
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Register</button>

                <p class="text-center mt-3 mb-0">
                    <a href="index.php">Already have an account? Login</a>
                </p>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
