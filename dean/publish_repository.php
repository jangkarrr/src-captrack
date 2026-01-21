<?php
session_start();

require_once '../assets/includes/role_functions.php';

// Check if the user is logged in and is a dean (multi-role aware)
if (!isset($_SESSION['user_data']) || !hasRole($_SESSION['user_data'], 'dean')) {
    header('Location: ../users/login.php?error=unauthorized_access');
    exit();
}

// Ensure active role is dean for sidebar/highlighting consistency
if (!isset($_SESSION['active_role']) || $_SESSION['active_role'] !== 'dean') {
    $_SESSION['active_role'] = 'dean';
    $_SESSION['role'] = 'dean';
}

include '../config/database.php';
include '../assets/includes/author_functions.php';

// Pagination & search
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($limit <= 0) {
    $limit = 10;
}
if ($page <= 0) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// Base query: books not yet published by dean
$query = "SELECT * FROM books WHERE (published_by_dean IS NULL OR published_by_dean = 0)";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ? OR keywords LIKE ?)";
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param, $search_param];
    $types = 'sss';
}

$query .= " ORDER BY year DESC, id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}

// Total count for pagination (same not-yet-published condition)
$countQuery = "SELECT COUNT(id) AS total FROM books WHERE (published_by_dean IS NULL OR published_by_dean = 0)";
$countParams = [];
$countTypes = '';

if (!empty($search)) {
    $countQuery .= " AND (title LIKE ? OR author LIKE ? OR keywords LIKE ?)";
    $countParams = [$search_param, $search_param, $search_param];
    $countTypes = 'sss';
}

$countStmt = $conn->prepare($countQuery);
if ($countStmt) {
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $totalRow = $countRes->fetch_assoc();
    $total = $totalRow ? (int)$totalRow['total'] : 0;
} else {
    $total = 0;
}

$total_pages = max(1, (int)ceil($total / $limit));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dean - Publish Research Repository</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h4 class="mb-2 mb-md-0">
                <i class="bi bi-journal-check me-2"></i>
                Research Pending Publication
            </h4>
            <span class="badge bg-secondary">
                Total pending: <?php echo (int)$total; ?>
            </span>
        </div>

        <!-- Controls -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" onchange="updateLimit(this.value)">
                    <?php foreach ([10,25,50,100] as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo $limit == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4 ms-md-auto">
                <form method="get" class="d-flex">
                    <input type="hidden" name="limit" value="<?php echo (int)$limit; ?>">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control form-control-sm me-2" placeholder="Search title / author / keywords">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th style="width: 20%">Authors</th>
                        <th style="width: 8%" class="text-center">Year</th>
                        <th style="width: 15%">Status</th>
                        <th style="width: 12%" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr id="row-<?php echo (int)$row['id']; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                <div class="small text-muted mt-1">
                                    <?php echo htmlspecialchars(substr($row['abstract'], 0, 120)) . (strlen($row['abstract']) > 120 ? '...' : ''); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars(parseAuthorData($row['author'])); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['year']); ?></td>
                            <td>
                                <span class="badge bg-success">Verified by Admin</span>
                            </td>
                            <td class="text-center">
                                <button 
                                    class="btn btn-sm btn-primary publish-btn"
                                    data-id="<?php echo (int)$row['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>">
                                    <i class="bi bi-cloud-upload"></i> Publish
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-clipboard-check mb-2" style="font-size: 2rem;"></i><br>
                            No verified research is awaiting publication.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-end mb-0">
                <?php $prev = max(1, $page - 1); $next = min($total_pages, $page + 1); ?>
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo buildPageUrl($prev, $limit, $search); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo buildPageUrl($i, $limit, $search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo buildPageUrl($next, $limit, $search); ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<script>
function updateLimit(limit) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limit);
    params.set('page', '1');
    window.location.search = params.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.publish-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const title = this.dataset.title;

            if (!confirm('Publish this research to the repository?\n\n' + title)) {
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Publishing';

            fetch('api/publish_research.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + encodeURIComponent(id),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = document.getElementById('row-' + id);
                        if (row) {
                            row.remove();
                        }
                        showToast('Published', 'Research has been published successfully.', 'success');
                    } else {
                        alert(data.message || 'Failed to publish research');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-cloud-upload"></i> Publish';
                    }
                })
                .catch(() => {
                    alert('An error occurred while publishing.');
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-cloud-upload"></i> Publish';
                });
        });
    });
});

function showToast(title, message, type) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-' + (type === 'success' ? 'success' : 'secondary') + ' border-0 position-fixed top-0 start-50 translate-middle-x mt-3';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}:</strong> ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;

    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
<?php
if ($stmt instanceof mysqli_stmt) {
    $stmt->close();
}
if ($countStmt instanceof mysqli_stmt) {
    $countStmt->close();
}
if (isset($conn)) {
    $conn->close();
}

// Helper to build pagination URLs while keeping search/limit
function buildPageUrl($page, $limit, $search) {
    $params = [
        'page' => (int)$page,
        'limit' => (int)$limit,
    ];
    if ($search !== '') {
        $params['search'] = $search;
    }
    return 'publish_repository.php?' . http_build_query($params);
}
?>
