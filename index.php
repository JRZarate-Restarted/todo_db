<?php
session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email !== "" && $password !== "") {

        // ---- FETCH USER BY EMAIL ----
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // ---- VERIFY PASSWORD ----
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit;
        } else {
            $message = '<div class="alert alert-danger">Invalid email or password!</div>';
        }

    } else {
        $message = '<div class="alert alert-warning">Please fill in all fields.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
        rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm mx-auto" style="max-width: 450px;">
        <div class="card-body">
            <h2 class="text-center mb-4">Login</h2>

            <?php echo $message; ?>

            <form method="POST">
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

                <button type="submit" class="btn btn-success w-100">Login</button>
            </form>

            <p class="text-center mt-3 mb-0">
                <a href="register.php">Register</a> | 
                <a href="forgot_password.php">Forgot Password?</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
