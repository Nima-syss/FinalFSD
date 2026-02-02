<?php
session_start();
// ============================================
// FILE: public/instructors/edit.php
// ============================================
?>
<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from editing instructors
if (isStudentRole()) {
    setMessage("Access denied. Students cannot edit instructors.", "error");
    redirect("list.php");
}

$pageTitle = 'Edit Instructor';
$errors = [];

$instructorId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($instructorId <= 0) {
    setMessage("Invalid instructor ID", "error");
    redirect('list.php');
}

try {
    $instructor = getOne($pdo, "SELECT * FROM instructors WHERE instructor_id = ?", [$instructorId]);
    
    if (!$instructor) {
        setMessage("Instructor not found", "error");
        redirect('list.php');
    }
} catch (PDOException $e) {
    error_log("Edit Instructor Error: " . $e->getMessage());
    setMessage("Error loading instructor", "error");
    redirect('list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validation
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Invalid email format";
    } elseif (instructorEmailExists($pdo, $email, $instructorId)) {
        $errors[] = "Email already exists";
    }
    if (empty($department)) $errors[] = "Department is required";
    if (empty($specialization)) $errors[] = "Specialization is required";
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE instructors SET 
                    first_name = ?, last_name = ?, email = ?, phone = ?, 
                    department = ?, specialization = ?, bio = ?
                    WHERE instructor_id = ?";
            
            execute($pdo, $sql, [$firstName, $lastName, $email, $phone, 
                                  $department, $specialization, $bio, $instructorId]);
            
            setMessage("Instructor '{$firstName} {$lastName}' updated successfully!");
            redirect('list.php');
        } catch (PDOException $e) {
            error_log("Update Instructor Error: " . $e->getMessage());
            $errors[] = "Error updating instructor. Please try again.";
        }
    }
    
    $instructor = array_merge($instructor, $_POST);
}

include '../../includes/header.php';
?>

<!-- Same form as add.php but with pre-filled values and different button -->
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Instructor</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Same fields as add.php with values from $instructor array -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?= h($instructor['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?= h($instructor['last_name']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= h($instructor['email']) ?>" required>
                        <input type="hidden" id="instructor_id" value="<?= h($instructorId) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= h($instructor['phone']) ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department *</label>
                            <input type="text" class="form-control" id="department" name="department" 
                                   value="<?= h($instructor['department']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="specialization" class="form-label">Specialization *</label>
                            <input type="text" class="form-control" id="specialization" name="specialization" 
                                   value="<?= h($instructor['specialization']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio / Description</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?= h($instructor['bio']) ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Update Instructor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>