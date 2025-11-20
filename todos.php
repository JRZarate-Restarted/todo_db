<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require 'db.php';
require 'utils.php';
$user_id = $_SESSION['user_id'];

// ---- HANDLE ADD / EDIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc = $_POST['description'] ?? '';
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $due_date = $_POST['due_date'] ?: null;
    $category_id = $_POST['category_id'] ?: null;

    // Handle attachment
    $attachment = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
        $file = $_FILES['attachment'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','jpg','jpeg','png'];
        if (in_array($ext, $allowed) && $file['size'] <= 5*1024*1024) {
            $uploads_dir = 'uploads/';
            if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
            $attachment = $uploads_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $file['name']);
            move_uploaded_file($file['tmp_name'], $attachment);
        }
    }

    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO todos 
            (user_id, category_id, title, description, status, priority, notifications, due_date, attachment)
            VALUES (:uid,:cat,:title,:desc,:status,:priority,:notif,:due,:attach)
        ");
        $stmt->execute([
            ':uid'      => $user_id,
            ':cat'      => $category_id,
            ':title'    => $title,
            ':desc'     => $desc,
            ':status'   => $status,
            ':priority' => $priority,
            ':notif'    => $notifications,
            ':due'      => $due_date,
            ':attach'   => $attachment
        ]);
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];

        // Fetch old todo for comparison
        $old_stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ? AND user_id = ?");
        $old_stmt->execute([$id, $user_id]);
        $old = $old_stmt->fetch();

        if ($status === 'completed' && $old['status'] !== 'completed' && $notifications) {
            $email_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $email_stmt->execute([$user_id]);
            $email = $email_stmt->fetchColumn();
            simulate_email($email, "Task Done!", "Task '$title' completed!");
        }

        $stmt = $pdo->prepare("
            UPDATE todos SET 
            category_id=:cat, title=:title, description=:desc, status=:status, 
            priority=:priority, notifications=:notif, due_date=:due, attachment=:attach
            WHERE id=:id AND user_id=:uid
        ");
        $stmt->execute([
            ':cat'    => $category_id,
            ':title'  => $title,
            ':desc'   => $desc,
            ':status' => $status,
            ':priority' => $priority,
            ':notif'  => $notifications,
            ':due'    => $due_date,
            ':attach' => $attachment ?: $old['attachment'],
            ':id'     => $id,
            ':uid'    => $user_id
        ]);
    }

    header("Location: todos.php");
    exit;
}

// ---- HANDLE DELETE ----
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: todos.php");
    exit;
}

// ---- FETCH TODOS WITH FILTERS & PAGINATION ----
$page = max(1, $_GET['page'] ?? 1);
$limit = 5;
$offset = ($page-1)*$limit;

$where = "WHERE t.user_id = ?";
$params = [$user_id];

if (!empty($_GET['search'])) {
    $s = "%{$_GET['search']}%";
    $where .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = $s;
    $params[] = $s;
}

if (!empty($_GET['status'])) {
    $where .= " AND t.status = ?";
    $params[] = $_GET['status'];
}

if (!empty($_GET['category'])) {
    $where .= " AND t.category_id = ?";
    $params[] = $_GET['category'];
}

// Count total todos
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM todos t $where");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$pages = ceil($total / $limit);

// Fetch todos
$stmt = $pdo->prepare("
    SELECT t.*, c.name as cat_name 
    FROM todos t 
    LEFT JOIN categories c ON t.category_id = c.id
    $where ORDER BY t.created_at DESC LIMIT ? OFFSET ?
");
$exec_params = array_merge($params, [$limit, $offset]);
$stmt->execute($exec_params);
$todos = $stmt->fetchAll();

// Fetch categories
$cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ?");
$cat_stmt->execute([$user_id]);
$cats = $cat_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>TODOs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <h3>My TODOs</h3>

    <!-- Filters -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($_GET['category'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>

    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Task</button>

    <!-- TODO Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Due</th>
                <th>File</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($todos as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['title']) ?></td>
                <td>
                    <span class="badge bg-<?= $t['status']=='completed'?'success':($t['status']=='in_progress'?'info':'warning') ?>">
                        <?= htmlspecialchars($t['status']) ?>
                    </span>
                </td>
                <td><?= $t['due_date'] ?: '—' ?></td>
                <td><?= $t['attachment'] ? '<a href="'.$t['attachment'].'" target="_blank">View</a>' : '—' ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick='editTodo(<?= json_encode($t) ?>)' data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
                    <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Del</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <?php for ($i=1; $i<=$pages; $i++): ?>
            <li class="page-item <?= $page==$i ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(['search'=>$_GET['search']??'','status'=>$_GET['status']??'','category'=>$_GET['category']??'']) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="add">
<?php include 'todo_form.php'; ?>
<div class="modal-footer">
    <button type="submit" class="btn btn-success">Save</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>
</form></div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" id="edit_id">
<?php include 'todo_form.php'; ?>
<div class="modal-footer">
    <button type="submit" class="btn btn-primary">Update</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>
</form></div></div></div>

<script>
function editTodo(t) {
    document.getElementById('edit_id').value = t.id;
    document.querySelector('#editModal [name="title"]').value = t.title;
    document.querySelector('#editModal [name="description"]').value = t.description;
    document.querySelector('#editModal [name="status"]').value = t.status;
    document.querySelectorAll('#editModal [name="priority"]').forEach(r => r.checked = r.value === t.priority);
    document.querySelector('#editModal [name="notifications"]').checked = t.notifications == 1;
    document.querySelector('#editModal [name="due_date"]').value = t.due_date;
    document.querySelector('#editModal [name="category_id"]').value = t.category_id || '';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
