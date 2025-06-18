<?php
require_once '../config.php';


//Gets the vote counts for submitted article
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


// chincheck yung logged na user kung admin or reviewer lang
if (!isLoggedIn()) {
    redirect('../login.php', 'Please login to access the dashboard', 'warning');
}

if (!isAdminOrReviewer()) {
    redirect('../index.php', 'You do not have permission to access this page', 'danger');
}

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// statistics
$totalUsers = 0;
$totalArticles = 0;
$pendingReviews = 0;
$recentActivity = [];

// total users
$query = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalUsers = $row['count'];
}

// total articles
$query = "SELECT COUNT(*) as count FROM articles";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalArticles = $row['count'];
}

// for pending reviews
$query = "SELECT COUNT(*) as count FROM reports WHERE review_status = 'pending'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $pendingReviews = $row['count'];
}

// for recent activity
$query = "SELECT a.action, a.timestamp, u.username, a.entity_type, a.entity_id 
          FROM audit_logs a 
          LEFT JOIN users u ON a.user_id = u.id 
          ORDER BY a.timestamp DESC LIMIT 10";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recentActivity[] = $row;
    }
}

// articles by status
$articlesByStatus = [];
$query = "SELECT status, COUNT(*) as count FROM articles GROUP BY status";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $articlesByStatus[$row['status']] = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-fw fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-fw fa-clipboard-check"></i>
                        <span>Review Reports</span>
                        <?php if ($pendingReviews > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $pendingReviews; ?></span>
                        <?php endif; ?>
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
              
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-end w-100 mb-2 mt-2">
                        <a href="high-frequency-submitters.php" class="btn btn-warning me-2">
                            <i class="fas fa-chart-line me-2"></i>View High Frequency Submitters
                        </a>
                    </div>
                </div>
                <div class="container-fluid">
                    <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggle">
                        <i class="fa fa-bars"></i>
                    </button>
                    
                    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    
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
                    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-clipboard-check fa-sm text-white-50"></i> Review Reports
                    </a>
                </div>
                
             
                <div class="row">
                
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalUsers; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                   
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Articles</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalArticles; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                   
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Reviews</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingReviews; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                   
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Fake News Detected</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo isset($articlesByStatus['fake']) ? $articlesByStatus['fake'] : 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="row">
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Articles by Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4 pb-2">
                                    <canvas id="articleStatusChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small">
                                    <span class="me-2">
                                        <i class="fas fa-circle text-primary"></i> Pending
                                    </span>
                                    <span class="me-2">
                                        <i class="fas fa-circle text-success"></i> Verified
                                    </span>
                                    <span class="me-2">
                                        <i class="fas fa-circle text-danger"></i> Fake
                                    </span>
                                    <span>
                                        <i class="fas fa-circle text-warning"></i> Rejected
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Action</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentActivity)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No recent activity</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recentActivity as $activity): ?>
                                                    <tr>
                                                        <td><?php echo $activity['username'] ? htmlspecialchars($activity['username']) : 'System'; ?></td>
                                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                        <td><?php echo date('M d, H:i', strtotime($activity['timestamp'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
               
                <div class="row">
                  
                    <div class="col-lg-12 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Pending Reviews</h6>
                                <a href="reports.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php
                                
                                $pendingReviewsQuery = "SELECT r.id, a.title, r.credibility_score, r.generated_at, u.username 
                                                      FROM reports r 
                                                      JOIN articles a ON r.article_id = a.id 
                                                      JOIN users u ON a.user_id = u.id 
                                                      WHERE r.review_status = 'pending' 
                                                      ORDER BY r.generated_at DESC LIMIT 5";
                                $pendingReviewsResult = $conn->query($pendingReviewsQuery);
                                ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Article Title</th>
                                                <th>Submitted By</th>
                                                <th>Score</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($pendingReviewsResult && $pendingReviewsResult->num_rows > 0): ?>
                                                <?php while ($review = $pendingReviewsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($review['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($review['username']); ?></td>
                                                        <td>
                                                            <?php 
                                                            $score = $review['credibility_score'];
                                                            $scoreClass = 'danger';
                                                            if ($score >= 80) $scoreClass = 'success';
                                                            elseif ($score >= 60) $scoreClass = 'primary';
                                                            elseif ($score >= 40) $scoreClass = 'warning';
                                                            ?>
                                                            <span class="badge bg-<?php echo $scoreClass; ?>"><?php echo $score; ?>/100</span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($review['generated_at'])); ?></td>
                                                        <td>
                                                            <a href="review.php?id=<?php echo $review['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No pending reviews</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="row">
                   
                    <div class="col-lg-12 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Reports with Most User Feedback</h6>
                                <a href="reports.php" class="btn btn-sm btn-primary">View All Reports</a>
                            </div>
                            <div class="card-body">
                                <?php
                                
                                $mostVotedQuery = "SELECT r.id, a.title, r.credibility_score, r.review_status, COUNT(rv.id) as vote_count 
                                                  FROM reports r 
                                                  JOIN articles a ON r.article_id = a.id 
                                                  JOIN report_votes rv ON r.id = rv.report_id 
                                                  GROUP BY r.id 
                                                  ORDER BY vote_count DESC 
                                                  LIMIT 5";
                                $mostVotedResult = $conn->query($mostVotedQuery);
                                ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Article Title</th>
                                                <th>Score</th>
                                                <th>Status</th>
                                                <th>User Feedback</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($mostVotedResult && $mostVotedResult->num_rows > 0): ?>
                                                <?php while ($report = $mostVotedResult->fetch_assoc()): 
                                                    $voteCounts = getVoteCounts($report['id']);
                                                    $totalVotes = $voteCounts['agree'] + $voteCounts['disagree'];
                                                    $agreePercentage = $totalVotes > 0 ? round(($voteCounts['agree'] / $totalVotes) * 100) : 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($report['title']); ?></td>
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
                                                        </td>
                                                        <td>
                                                            <a href="review.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No reports with user feedback yet</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            document.body.classList.toggle('sb-sidenav-toggled');
        });
        
        
        const ctx = document.getElementById('articleStatusChart');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Verified', 'Fake', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo isset($articlesByStatus['pending']) ? $articlesByStatus['pending'] : 0; ?>,
                        <?php echo isset($articlesByStatus['verified']) ? $articlesByStatus['verified'] : 0; ?>,
                        <?php echo isset($articlesByStatus['fake']) ? $articlesByStatus['fake'] : 0; ?>,
                        <?php echo isset($articlesByStatus['rejected']) ? $articlesByStatus['rejected'] : 0; ?>
                    ],
                    backgroundColor: ['#4e73df', '#1cc88a', '#e74a3b', '#f6c23e'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#be2617', '#dda20a'],
                    hoverBorderColor: 'rgba(234, 236, 244, 1)',
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
                legend: {
                    display: false
                },
                cutoutPercentage: 80,
            },
        });
    </script>
</body>
</html>