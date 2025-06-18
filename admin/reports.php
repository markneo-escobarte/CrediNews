<?php
require_once '../config.php';

// to get vote counts for a report
function getVoteCounts($reportId) {
    global $conn;
    
    $agree = 0;
    $disagree = 0;
    
    $stmt = $conn->prepare("SELECT vote_type, COUNT(*) as count FROM report_votes WHERE report_id = ? GROUP BY vote_type");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['vote_type'] === 'agree') {
            $agree = $row['count'];
        } else {
            $disagree = $row['count'];
        }
    }
    
    return ['agree' => $agree, 'disagree' => $disagree];
}

// Check if nakalogin si user and kung admin/reviewer role
if (!isLoggedIn()) {
    redirect('../login.php', 'Please login to access the dashboard', 'warning');
}

if (!isAdminOrReviewer()) {
    redirect('../index.php', 'You do not have permission to access this page', 'danger');
}

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Get reports with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter by status if meron
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$statusWhere = '';
if (!empty($statusFilter)) {
    $statusWhere = "WHERE r.review_status = '$statusFilter'";
}

// Get total reports count
$countQuery = "SELECT COUNT(*) as total FROM reports r $statusWhere";
$countResult = $conn->query($countQuery);
$totalReports = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalReports / $limit);

// Get reports
$query = "SELECT r.id, a.title, r.credibility_score, r.generated_at, r.review_status, u.username 
          FROM reports r 
          JOIN articles a ON r.article_id = a.id 
          JOIN users u ON a.user_id = u.id 
          $statusWhere 
          ORDER BY r.generated_at DESC 
          LIMIT $offset, $limit";
$result = $conn->query($query);
$reports = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Reports - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">

        <div class="bg-gradient-primary sidebar" id="sidebar-wrapper">
            <div class="sidebar-brand text-white">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="sidebar-brand-text mx-3"><?php echo APP_NAME; ?> Admin</div>
            </div>
            
            <hr class="sidebar-divider my-0">
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-fw fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link active" href="reports.php">
                        <i class="fas fa-fw fa-clipboard-check"></i>
                        <span>Review Reports</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="articles.php">
                        <i class="fas fa-fw fa-newspaper"></i>
                        <span>Manage Articles</span>
                    </a>
                </li>
                
                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-fw fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="high-frequency-submitters.php">
                        <i class="fas fa-fw fa-chart-line"></i>
                        <span>High Frequency Submitters</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-fw fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <hr class="sidebar-divider">
                
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-fw fa-home"></i>
                        <span>Back to Site</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-fw fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
        

        <div id="page-content-wrapper" class="bg-light">

            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <div class="container-fluid">
                    <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggle">
                        <i class="fa fa-bars"></i>
                    </button>
                    
                    <h1 class="h3 mb-0 text-gray-800">Review Reports</h1>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['username']; ?></span>
                                <i class="fas fa-user-circle fa-fw"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow">
                                <a class="dropdown-item" href="../profile.php">
                                    <i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Review Reports</h1>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    </div>
                    <div class="card-body">
                        <form action="reports.php" method="GET" class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>All</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="reports.php" class="btn btn-secondary ms-2">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Reports</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Article Title</th>
                                        <th>Submitted By</th>
                                        <th>Credibility Score</th>
                                        <th>Generated At</th>
                                        <th>Status</th>
                                        <th>User Feedback</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reports)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No reports found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td><?php echo $report['id']; ?></td>
                                                <td><?php echo htmlspecialchars($report['title']); ?></td>
                                                <td><?php echo htmlspecialchars($report['username']); ?></td>
                                                <td>
                                                    <?php 
                                                    $score = $report['credibility_score'];
                                                    $scoreClass = 'danger';
                                                    if ($score >= 80) $scoreClass = 'success';
                                                    elseif ($score >= 60) $scoreClass = 'primary';
                                                    elseif ($score >= 40) $scoreClass = 'warning';
                                                    ?>
                                                    <span class="badge bg-<?php echo $scoreClass; ?>"><?php echo $score; ?>/100</span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($report['generated_at'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = 'secondary';
                                                    $statusText = 'Unknown';
                                                    
                                                    switch ($report['review_status']) {
                                                        case 'pending':
                                                            $statusClass = 'warning';
                                                            $statusText = 'Pending';
                                                            break;
                                                        case 'approved':
                                                            $statusClass = 'success';
                                                            $statusText = 'Approved';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'danger';
                                                            $statusText = 'Rejected';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $voteCounts = getVoteCounts($report['id']);
                                                    $totalVotes = $voteCounts['agree'] + $voteCounts['disagree'];
                                                    
                                                    if ($totalVotes > 0):
                                                        $agreePercentage = round(($voteCounts['agree'] / $totalVotes) * 100);
                                                    ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <i class="fas fa-thumbs-up text-success"></i> <?php echo $voteCounts['agree']; ?>
                                                            <i class="fas fa-thumbs-down text-danger ms-1"></i> <?php echo $voteCounts['disagree']; ?>
                                                        </div>
                                                        <div class="progress flex-grow-1" style="height: 8px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $agreePercentage; ?>%" 
                                                                aria-valuenow="<?php echo $agreePercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <?php else: ?>
                                                    <span class="text-muted small">No votes</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="review.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-4">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; <?php echo APP_NAME . ' ' . date('Y'); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>

        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    </script>
</body>
</html>