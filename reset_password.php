<?php
require 'db.php';
$message = '';
$token = $_GET['token'] ?? '';

// Redirect if no token
if (empty($token)) {
    header("Location: index.php");
    exit;
}

// Fetch user by token
$stmt = $pdo->prepare("
    SELECT * 
    FROM users 
    WHERE reset_token = :token AND reset_expiry > NOW()
");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    $message = '<div class="alert alert-danger">Invalid or expired token!</div>';
}

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {

    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm || strlen($password) < 8) {
        $message = '<div class="alert alert-danger">Passwords must match and be at least 8 characters long!</div>';
    } else {

        // Update password & clear token
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("
            UPDATE users 
            SET password = :password, reset_token = NULL, reset_expiry = NULL 
            WHERE id = :id
        ");
        $update->execute([
            ':password' => $hashed,
            ':id'       => $user['id']
        ]);

        $message = '<div class="alert alert-success">Password reset successfully! <a href="index.php">Login</a></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm mx-auto" style="max-width: 450px;">
        <div class="card-body">
            <h3 class="mb-3">Reset Password</h3>

            <?php echo $message; ?>

            <?php if ($user): ?>
            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-success w-100">Reset Password</button>

            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>
