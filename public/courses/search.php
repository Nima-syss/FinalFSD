<?php
// ============================================
// FILE: public/courses/search.php
// ============================================
?>
<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$pageTitle = 'Search Courses';

// Get search parameters
$searchTerm = isset($_GET['search']) ? sanitizeSearch($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? sanitizeSearch($_GET['category']) : '';
$levelFilter = isset($_GET['level']) ? sanitizeSearch($_GET['level']) : '';
$instructorFilter = isset($_GET['instructor']) ? intval($_GET['instructor']) : 0;

$categories = getCategories();
$levels = getLevels();
$instructors = getAllInstructors($pdo);

$results = [];
$searchPerformed = false;

// Build search query
if (!empty($searchTerm) || !empty($categoryFilter) || !empty($levelFilter) || $instructorFilter > 0) {
    $searchPerformed = true;
    
    $sql = "SELECT c.*, 
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.status = 'Active') as enrolled_count
            FROM courses c
            LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
            WHERE 1=1";
    
    $params = [];
    
    // Add search term (searches in name, code, and description)
    if (!empty($searchTerm)) {
        $sql .= " AND (c.course_name LIKE ? OR c.course_code LIKE ? OR c.description LIKE ?)";
        $searchParam = "%{$searchTerm}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Add category filter
    if (!empty($categoryFilter)) {
        $sql .= " AND c.category = ?";
        $params[] = $categoryFilter;
    }
    
    // Add level filter
    if (!empty($levelFilter)) {
        $sql .= " AND c.level = ?";
        $params[] = $levelFilter;
    }
    
    // Add instructor filter
    if ($instructorFilter > 0) {
        $sql .= " AND c.instructor_id = ?";
        $params[] = $instructorFilter;
    }
    
    $sql .= " ORDER BY c.course_name";
    
    try {
        $results = getAll($pdo, $sql, $params);
    } catch (PDOException $e) {
        error_log("Search Error: " . $e->getMessage());
        setMessage("Error performing search", "error");
    }
}

include '../../includes/header.php';
?>

<h1 class="mb-4"><i class="bi bi-search"></i> Search Courses</h1>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Search Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" id="searchForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="search" class="form-label">Search Term</label>
                    <input type="text" 
                           class="form-control" 
                           id="search" 
                           name="search" 
                           value="<?= h($searchTerm) ?>"
                           placeholder="Search by course name, code, or description"
                           autocomplete="off">
                    <div id="autocomplete-results" class="list-group mt-1" style="display: none;"></div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat) ?>" <?= $categoryFilter == $cat ? 'selected' : '' ?>>
                                <?= h($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="level" class="form-label">Level</label>
                    <select class="form-select" id="level" name="level">
                        <option value="">All Levels</option>
                        <?php foreach ($levels as $lv): ?>
                            <option value="<?= h($lv) ?>" <?= $levelFilter == $lv ? 'selected' : '' ?>>
                                <?= h($lv) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="instructor" class="form-label">Instructor</label>
                    <select class="form-select" id="instructor" name="instructor">
                        <option value="">All Instructors</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?= h($instructor['instructor_id']) ?>" 
                                    <?= $instructorFilter == $instructor['instructor_id'] ? 'selected' : '' ?>>
                                <?= h($instructor['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="search.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
<?php if ($searchPerformed): ?>
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-list-check"></i> Search Results 
                <span class="badge bg-light text-dark"><?= count($results) ?> courses found</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($results)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No courses found matching your search criteria.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-success">
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Category</th>
                                <th>Level</th>
                                <th>Credits</th>
                                <th>Instructor</th>
                                <th>Available Slots</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $course): ?>
                                <?php
                                $availableSlots = $course['max_students'] - $course['enrolled_count'];
                                $slotClass = $availableSlots > 10 ? 'text-success' : ($availableSlots > 0 ? 'text-warning' : 'text-danger');
                                ?>
                                <tr>
                                    <td><strong><?= h($course['course_code']) ?></strong></td>
                                    <td>
                                        <?= h($course['course_name']) ?>
                                        <?php if (!empty($course['description'])): ?>
                                            <br><small class="text-muted"><?= h(substr($course['description'], 0, 60)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($course['category']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $course['level'] == 'Beginner' ? 'bg-success' : 
                                                ($course['level'] == 'Intermediate' ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= h($course['level']) ?>
                                        </span>
                                    </td>
                                    <td><?= h($course['credits']) ?></td>
                                    <td><?= h($course['instructor_name'] ?? 'Not Assigned') ?></td>
                                    <td class="<?= $slotClass ?>">
                                        <strong><?= h($availableSlots) ?></strong> available
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?= h($course['course_id']) ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>