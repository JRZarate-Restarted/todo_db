<?php
require 'db.php';
require 'utils.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);

    if ($email !== "") {

        // ---- CHECK IF USER EXISTS ----
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {

            // ---- GENERATE RESET TOKEN ----
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // ---- SAVE TOKEN ----
            $update = $pdo->prepare("
                UPDATE users 
                SET reset_token = :token, reset_expiry = :expiry 
                WHERE id = :id
            ");
            $update->execute([
                ':token'  => $token,
                ':expiry' => $expiry,
                ':id'     => $user['id']
            ]);

            // ---- SIMULATED EMAIL (LOG FILE) ----
            $link = "http://localhost/todo-app/reset_password.php?token=" . urlencode($token);

            simulate_email(
                $email, 
                "Password Reset Request", 
                "Click the link to reset your password:\n\n$link"
            );

            $message = '<div class="alert alert-success">
                            Reset link sent! Check <code>email_log.txt</code>.
                        </div>';

        } else {
            $message = '<div class="alert alert-danger">
                            Email not found.
                        </div>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
        rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm mx-auto" style="max-width: 450px;">
        <div class="card-body">

            <h3 class="mb-3">Forgot Password</h3>

            <?php echo $message; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        required>
                </div>

                <button 
                    type="submit" 
                    class="btn btn-warning w-100">
                    Send Reset Link
                </button>
            </form>

            <p class="mt-3 mb-0">
                <a href="index.php">Back to Login</a>
            </p>

        </div>
    </div>
</div>

</body>
</html>
