<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// -------------------------------
// FETCH USERNAME
// -------------------------------
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// -------------------------------
// COUNT PENDING TASKS
// -------------------------------
$pending = $pdo->prepare("
    SELECT COUNT(*) 
    FROM todos 
    WHERE user_id = :uid AND status = 'pending'
");
$pending->execute([':uid' => $user_id]);
$pending_count = $pending->fetchColumn();

// -------------------------------
// COUNT OVERDUE TASKS
// -------------------------------
$overdue = $pdo->prepare("
    SELECT COUNT(*) 
    FROM todos 
    WHERE user_id = :uid
      AND due_date < CURDATE()
      AND status != 'completed'
");
$overdue->execute([':uid' => $user_id]);
$overdue_count = $overdue->fetchColumn();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">

    <h2 class="mb-4">
        Welcome, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!
    </h2>

    <div class="row g-4">

        <!-- Pending Tasks -->
        <div class="col-md-6">
            <div class="card text-white bg-warning shadow">
                <div class="card-body">
                    <h5 class="card-title">Pending Tasks</h5>
                    <h2 class="card-text"><?php echo $pending_count; ?></h2>
                </div>
            </div>
        </div>

        <!-- Overdue Tasks -->
        <div class="col-md-6">
            <div class="card text-white bg-danger shadow">
                <div class="card-body">
                    <h5 class="card-title">Overdue</h5>
                    <h2 class="card-text"><?php echo $overdue_count; ?></h2>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4">
        <a href="todos.php" class="btn btn-primary">Go to TODOs</a>
    </div>

</div>

</body>
</html>
