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
// ADD NEW CATEGORY
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['name'])) {
        $name = trim($_POST['name']);

        if ($name !== "") {
            $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (:name, :uid)");
            $stmt->execute([
                ':name' => $name,
                ':uid'  => $user_id
            ]);
        }
    }
}

// -------------------------------
// DELETE CATEGORY
// -------------------------------
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id AND user_id = :uid");
    $stmt->execute([
        ':id'  => $id,
        ':uid' => $user_id
    ]);

    header("Location: categories.php");
    exit;
}

// -------------------------------
// FETCH CATEGORIES
// -------------------------------
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = :uid ORDER BY name ASC");
$stmt->execute([':uid' => $user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">

    <h3 class="mb-4">Manage Categories</h3>

    <!-- ADD FORM -->
    <form method="POST" class="row g-3 mb-4">
        <div class="col-auto">
            <input type="text" name="name" class="form-control" placeholder="New category" required>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-success">Add</button>
        </div>
    </form>

    <!-- CATEGORY TABLE -->
    <table class="table table-bordered table-striped">
        <thead class="table-primary">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th width="120">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($categories) === 0): ?>
                <tr>
                    <td colspan="3" class="text-center text-muted">No categories yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $i => $cat): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td>
                            <a href="?delete=<?php echo $cat['id']; ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this category?');">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div>
</body>
</html>
