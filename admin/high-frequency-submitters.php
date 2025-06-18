<?php
require_once '../config.php';
var_dump($_SESSION);
// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php', 'Access denied. Admin privileges required.', 'danger');
}

// nagsset ng default time period and minimum submissions countss
$timePeriod = isset($_GET['period']) ? $_GET['period'] : '7days';
$minSubmissions = isset($_GET['min']) ? (int)$_GET['min'] : 5;

// determine the data rangee for filtering submissions
switch ($timePeriod) {
    case '24hours':
        $interval = 'INTERVAL 24 HOUR';
        $periodLabel = 'Last 24 Hours';
        break;
    case '7days':
        $interval = 'INTERVAL 7 DAY';
        $periodLabel = 'Last 7 Days';
        break;
    case '30days':
        $interval = 'INTERVAL 30 DAY';
        $periodLabel = 'Last 30 Days';
        break;
    case 'alltime':
        $interval = null;
        $periodLabel = 'All Time';
        break;
    default:
        $interval = 'INTERVAL 7 DAY';
        $periodLabel = 'Last 7 Days';
}

// kinukuha yung mga users na may high frequency of submissions
if ($interval) {
    $query = "SELECT u.id, u.username, u.email, u.created_at, u.last_login, u.submission_count, 
             COUNT(a.id) as recent_submissions, 
             MAX(a.submission_date) as last_submission_date
             FROM users u
             LEFT JOIN articles a ON u.id = a.user_id AND a.submission_date > DATE_SUB(NOW(), $interval)
             GROUP BY u.id
             HAVING recent_submissions >= ?
             ORDER BY recent_submissions DESC";
} else {
    // for 'all time', nagamits ng total submission count
    $query = "SELECT u.id, u.username, u.email, u.created_at, u.last_login, u.submission_count, 
             u.submission_count as recent_submissions, 
             u.last_submission as last_submission_date
             FROM users u
             WHERE u.submission_count >= ?
             ORDER BY u.submission_count DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $minSubmissions);
$stmt->execute();
$result = $stmt->get_result();
$highFrequencySubmitters = $result->fetch_all(MYSQLI_ASSOC);

// submission trend data for top submitters
$submissionTrends = [];
if (count($highFrequencySubmitters) > 0) {
    
    // Inanalyze yung top 5 submitters based sa recent submissions
    $topSubmitters = array_slice($highFrequencySubmitters, 0, 5);
    
    foreach ($topSubmitters as $submitter) {
        $userId = $submitter['id'];
        
        // ginget nya ung daily submission counts for last 30 days
        $trendQuery = "SELECT DATE(submission_date) as date, COUNT(*) as count 
                      FROM articles 
                      WHERE user_id = ? 
                      AND submission_date > DATE_SUB(NOW(), INTERVAL 30 DAY) 
                      GROUP BY DATE(submission_date) 
                      ORDER BY date";
        
        $trendStmt = $conn->prepare($trendQuery);
        $trendStmt->bind_param("i", $userId);
        $trendStmt->execute();
        $trendResult = $trendStmt->get_result();
        
        $dailyCounts = [];
        while ($row = $trendResult->fetch_assoc()) {
            $dailyCounts[$row['date']] = $row['count'];
        }
        
        $submissionTrends[$submitter['username']] = $dailyCounts;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>High Frequency Submitters - <?php echo APP_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <style>
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .submitter-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .submitter-card:hover {
            transform: translateY(-5px);
        }
        .high-activity {
            border-left: 5px solid #dc3545;
        }
        .medium-activity {
            border-left: 5px solid #ffc107;
        }
        .low-activity {
            border-left: 5px solid #28a745;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid py-4">
       
        <div class="row mb-4">
            <div class="col-12">
                <div class="filter-card">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="period" class="form-label"><i class="fas fa-calendar me-2"></i>Time Period</label>
                            <select name="period" id="period" class="form-select">
                                <option value="24hours" <?php echo $timePeriod === '24hours' ? 'selected' : ''; ?>>Last 24 Hours</option>
                                <option value="7days" <?php echo $timePeriod === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="30days" <?php echo $timePeriod === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="alltime" <?php echo $timePeriod === 'alltime' ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="min" class="form-label"><i class="fas fa-filter me-2"></i>Minimum Submissions</label>
                            <input type="number" name="min" id="min" class="form-control" value="<?php echo $minSubmissions; ?>" min="1">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <div class="row">
            <?php foreach ($highFrequencySubmitters as $submitter): 
                // for activity level
                $activityClass = '';
                if ($submitter['recent_submissions'] >= 20) {
                    $activityClass = 'high-activity';
                } elseif ($submitter['recent_submissions'] >= 10) {
                    $activityClass = 'medium-activity';
                } else {
                    $activityClass = 'low-activity';
                }
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card submitter-card <?php echo $activityClass; ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($submitter['username']); ?>
                        </h5>
                        <p class="card-text">
                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($submitter['email']); ?><br>
                            <i class="fas fa-chart-bar me-2"></i>Recent Submissions: <?php echo $submitter['recent_submissions']; ?><br>
                            <i class="fas fa-history me-2"></i>Total Submissions: <?php echo $submitter['submission_count']; ?><br>
                            <i class="fas fa-clock me-2"></i>Last Submission: <?php echo date('Y-m-d H:i', strtotime($submitter['last_submission_date'])); ?>
                        </p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="viewSubmitterDetails(<?php echo $submitter['id']; ?>)">
                                <i class="fas fa-info-circle me-2"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
                    <a class="nav-link" href="reports.php">
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
                
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-fw fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link active" href="high-frequency-submitters.php">
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
                    
                    <h1 class="h3 mb-0 text-gray-800">High Frequency Submitters</h1>
                    
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
                    <h1 class="h3 mb-0 text-gray-800">High Frequency Submitters</h1>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
                    </div>
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="period" class="form-label">Time Period</label>
                                <select class="form-select" id="period" name="period">
                                    <option value="24hours" <?php echo $timePeriod == '24hours' ? 'selected' : ''; ?>>Last 24 Hours</option>
                                    <option value="7days" <?php echo $timePeriod == '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="30days" <?php echo $timePeriod == '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="alltime" <?php echo $timePeriod == 'alltime' ? 'selected' : ''; ?>>All Time</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="min" class="form-label">Minimum Submissions</label>
                                <input type="number" class="form-control" id="min" name="min" value="<?php echo $minSubmissions; ?>" min="1">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>


                <?php if (!empty($submissionTrends)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Submission Trends (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="submissionTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

 
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">High Frequency Submitters (<?php echo $periodLabel; ?>)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($highFrequencySubmitters) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Account Created</th>
                                        <th>Last Login</th>
                                        <th>Submissions (<?php echo $periodLabel; ?>)</th>
                                        <th>Total Submissions</th>
                                        <th>Last Submission</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($highFrequencySubmitters as $submitter): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($submitter['username']); ?></td>
                                        <td><?php echo htmlspecialchars($submitter['email']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($submitter['created_at'])); ?></td>
                                        <td><?php echo $submitter['last_login'] ? date('Y-m-d H:i', strtotime($submitter['last_login'])) : 'Never'; ?></td>
                                        <td class="text-center"><?php echo $submitter['recent_submissions']; ?></td>
                                        <td class="text-center"><?php echo $submitter['submission_count']; ?></td>
                                        <td><?php echo $submitter['last_submission_date'] ? date('Y-m-d H:i', strtotime($submitter['last_submission_date'])) : 'Never'; ?></td>
                                        <td>
                                            <a href="user-details.php?id=<?php echo $submitter['id']; ?>" class="btn btn-sm btn-info">View Details</a>
                                            <a href="user-submissions.php?id=<?php echo $submitter['id']; ?>" class="btn btn-sm btn-primary">View Submissions</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            No high frequency submitters found for the selected criteria.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
         
        </div>

    </div>

   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($submissionTrends)): ?>
    <script>
    // Sinset up yung submission trends chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('submissionTrendsChart').getContext('2d');
        
        // nagawa ng labels for last 30 days
        const labels = [];
        const today = new Date();
        for (let i = 29; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(today.getDate() - i);
            labels.push(date.toISOString().split('T')[0]);
        }
        
        // nagawa ng datasets para sa bawat submitter
        const datasets = [];
        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
        
        <?php $colorIndex = 0; foreach ($submissionTrends as $username => $dailyCounts): ?>
            const data_<?php echo md5($username); ?> = [];
            
            // naglalagay ng data for each day
            labels.forEach(date => {
                data_<?php echo md5($username); ?>.push(<?php echo isset($dailyCounts[$date]) ? $dailyCounts[$date] : 0; ?>);
            });
            
            datasets.push({
                label: '<?php echo addslashes($username); ?>',
                data: data_<?php echo md5($username); ?>,
                backgroundColor: 'transparent',
                borderColor: '<?php echo $colors[$colorIndex % count($colors)]; ?>',
                pointBackgroundColor: '<?php echo $colors[$colorIndex % count($colors)]; ?>',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '<?php echo $colors[$colorIndex % count($colors)]; ?>',
                borderWidth: 2
            });
        <?php $colorIndex++; endforeach; ?>
        
        const submissionTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
    });
    </script>
    <?php endif; ?>

    <script>
    // Nagv-view ng submitter details
    function viewSubmitterDetails(userId) {

        // for checking ng bootstrap modal
        let modal = document.getElementById('submitterModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'submitterModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Submitter Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }


        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        fetch(`user-details.php?id=${userId}&format=json`)
            .then(response => response.json())
            .then(data => {
                const modalBody = modal.querySelector('.modal-body');
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                    <p class="card-text">
                                        <strong>Username:</strong> ${data.username}<br>
                                        <strong>Email:</strong> ${data.email}<br>
                                        <strong>Joined:</strong> ${new Date(data.created_at).toLocaleDateString()}<br>
                                        <strong>Last Login:</strong> ${data.last_login ? new Date(data.last_login).toLocaleString() : 'Never'}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-chart-line me-2"></i>Submission Statistics</h6>
                                    <p class="card-text">
                                        <strong>Total Submissions:</strong> ${data.submission_count}<br>
                                        <strong>Recent Submissions:</strong> ${data.recent_submissions}<br>
                                        <strong>Last Submission:</strong> ${data.last_submission_date ? new Date(data.last_submission_date).toLocaleString() : 'Never'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-history me-2"></i>Recent Activity</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Article Title</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${data.recent_articles.map(article => `
                                                    <tr>
                                                        <td>${new Date(article.submission_date).toLocaleString()}</td>
                                                        <td>${article.title}</td>
                                                        <td><span class="badge bg-${article.status === 'approved' ? 'success' : (article.status === 'pending' ? 'warning' : 'danger')}">${article.status}</span></td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                modal.querySelector('.modal-body').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Error loading submitter details. Please try again.
                    </div>
                `;
            });

        
        modal.addEventListener('hidden.bs.modal', function () {
            modalInstance.dispose();
            modal.remove();
        });
    }

  
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html>