<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bio = trim($_POST['bio']);
    $profile_pic = $user['profile_pic'];

    // ---- HANDLE PROFILE PIC UPLOAD ----
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $file = $_FILES['profile_pic'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png'];
        $max_size = 2 * 1024 * 1024; // 2 MB

        if (in_array($ext, $allowed_exts) && $file['size'] <= $max_size) {

            $uploads_dir = 'uploads/';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }

            $new_name = $uploads_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $file['name']);

            if (move_uploaded_file($file['tmp_name'], $new_name)) {
                $profile_pic = $new_name;
            }
        }
    }

    // ---- UPDATE USER ----
    $update = $pdo->prepare("
        UPDATE users 
        SET bio = :bio, profile_pic = :pic 
        WHERE id = :id
    ");
    $update->execute([
        ':bio' => $bio,
        ':pic' => $profile_pic,
        ':id'  => $user_id
    ]);

    // Refresh page to see changes
    header("Location: profile.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
        rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="row">

        <!-- Profile Info -->
        <div class="col-md-4 text-center">
            <img 
                src="<?php echo htmlspecialchars($user['profile_pic'] ?: 'uploads/default.png'); ?>" 
                class="img-fluid rounded-circle mb-3" 
                width="180" 
                alt="Profile">

            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
        </div>

        <!-- About & Update Form -->
        <div class="col-md-8">

            <!-- About Me -->
            <div class="card mb-4">
                <div class="card-header"><h5>About Me</h5></div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($user['bio'] ?: 'No bio.')); ?></p>
                </div>
            </div>

            <!-- Update Form -->
            <div class="card">
                <div class="card-header"><h5>Update Profile</h5></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea 
                                name="bio" 
                                class="form-control" 
                                rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Profile Picture (JPG/PNG, max 2MB)</label>
                            <input 
                                type="file" 
                                name="profile_pic" 
                                class="form-control" 
                                accept=".jpg,.jpeg,.png,image/*">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Save Changes</button>

                    </form>
                </div>
            </div>

        </div>

    </div>
</div>

</body>
</html>
