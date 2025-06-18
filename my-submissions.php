<?php
require_once 'config.php';


if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view your submissions', 'warning');
}

$userId = $_SESSION['user_id'];

// Get user's saved reports
$stmt = $conn->prepare("SELECT r.id, r.credibility_score, r.analysis_text, r.digital_signature, r.generated_at, 
                        a.title, a.content, a.source, a.submission_date 
                        FROM reports r 
                        JOIN articles a ON r.article_id = a.id 
                        WHERE a.user_id = ? 
                        ORDER BY r.generated_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Get user's vote counts for each report
function getVoteCounts($reportId, $conn) {
    $agree = 0;
    $disagree = 0;
    
    $stmt = $conn->prepare("SELECT vote_type, COUNT(*) as count FROM report_votes WHERE report_id = ? GROUP BY vote_type");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $voteResult = $stmt->get_result();
    
    while ($vote = $voteResult->fetch_assoc()) {
        if ($vote['vote_type'] === 'agree') {
            $agree = $vote['count'];
        } else if ($vote['vote_type'] === 'disagree') {
            $disagree = $vote['count'];
        }
    }
    
    return ['agree' => $agree, 'disagree' => $disagree];
}

// Get user's vote for a specific report
function getUserVote($reportId, $userId, $conn) {
    $stmt = $conn->prepare("SELECT vote_type FROM report_votes WHERE report_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reportId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $vote = $result->fetch_assoc();
        return $vote['vote_type'];
    }
    
    return null;
}

// Helper function to get CSS class for credibility score
function getScoreClass($score) {
    if ($score >= 80) {
        return 'text-success';
    } else if ($score >= 60) {
        return 'text-primary';
    } else if ($score >= 40) {
        return 'text-warning';
    } else {
        return 'text-danger';
    }
}

// Helper function to get icon for credibility score
function getScoreIcon($score) {
    if ($score >= 80) {
        return 'fa-check-circle';
    } else if ($score >= 60) {
        return 'fa-info-circle';
    } else if ($score >= 40) {
        return 'fa-exclamation-circle';
    } else {
        return 'fa-times-circle';
    }
}

// Using redirect() function from config.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Submissions - CrediNews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt"></i> CrediNews
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="verify.php">Verify</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="submit.php">Submit</a>
                    </li>
                    <?php if (isLoggedIn() && isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/dashboard.php">Admin</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="my-submissions.php">Submissions</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    
    <div class="container my-5">
        <h1 class="mb-4">Submissions</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['message']; 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-info">
            <p>You haven't saved any reports yet. <a href="verify.php">Verify a news article</a> to get started.</p>
        </div>
        <?php else: ?>
        <div class="row">
            <?php while ($report = $result->fetch_assoc()): 
                $voteCounts = getVoteCounts($report['id'], $conn);
                $userVote = getUserVote($report['id'], $userId, $conn);
                $scoreClass = getScoreClass($report['credibility_score']);
                $scoreIcon = getScoreIcon($report['credibility_score']);
            ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($report['title']); ?></h5>
                        <span class="badge bg-primary"><?php echo date('M d, Y', strtotime($report['submission_date'])); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Source:</h6>
                            <p><?php echo htmlspecialchars($report['source']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Credibility Score:</h6>
                            <p class="<?php echo $scoreClass; ?> fs-4">
                                <i class="fas <?php echo $scoreIcon; ?>"></i>
                                <?php echo $report['credibility_score']; ?>%
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>AI Analysis:</h6>
                            <p><?php echo nl2br(htmlspecialchars($report['analysis_text'])); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Digital Signature:</h6>
                            <p class="text-muted small"><?php echo htmlspecialchars($report['digital_signature']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Community Feedback:</h6>
                            <div class="d-flex">
                                <button id="agreeBtn-<?php echo $report['id']; ?>" 
                                        class="btn <?php echo ($userVote === 'agree') ? 'btn-success' : 'btn-outline-success'; ?> me-2" 
                                        onclick="voteOnReport('<?php echo $report['id']; ?>', 'agree')">
                                    <i class="fas fa-thumbs-up"></i> Agree
                                    <span id="agreeCount-<?php echo $report['id']; ?>" class="badge bg-success"><?php echo $voteCounts['agree']; ?></span>
                                </button>
                                <button id="disagreeBtn-<?php echo $report['id']; ?>" 
                                        class="btn <?php echo ($userVote === 'disagree') ? 'btn-danger' : 'btn-outline-danger'; ?>" 
                                        onclick="voteOnReport('<?php echo $report['id']; ?>', 'disagree')">
                                    <i class="fas fa-thumbs-down"></i> Disagree
                                    <span id="disagreeCount-<?php echo $report['id']; ?>" class="badge bg-danger"><?php echo $voteCounts['disagree']; ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="view-report.php?id=<?php echo $report['id']; ?>" class="btn btn-primary">View Full Report</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-shield-alt"></i> CrediNews</h5>
                    <p>Empowering users with credible news verification tools.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="verify.php" class="text-white">Verify</a></li>
                        <li><a href="submit.php" class="text-white">Submit</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Connect</h5>
                    <div class="d-flex">
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2025 CrediNews. All rights reserved.</p>
            </div>
        </div>
    </footer>

   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        function voteOnReport(reportId, voteType) {
            // Make AJAX request to vote on report
            $.ajax({
                url: 'report-vote.php',
                type: 'POST',
                data: {
                    report_id: reportId,
                    vote_type: voteType
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        
                        $('#agreeCount-' + reportId).text(response.vote_counts.agree);
                        $('#disagreeCount-' + reportId).text(response.vote_counts.disagree);
                        
                       
                        if (response.user_vote === 'agree') {
                            $('#agreeBtn-' + reportId).removeClass('btn-outline-success').addClass('btn-success');
                            $('#disagreeBtn-' + reportId).removeClass('btn-danger').addClass('btn-outline-danger');
                        } else if (response.user_vote === 'disagree') {
                            $('#agreeBtn-' + reportId).removeClass('btn-success').addClass('btn-outline-success');
                            $('#disagreeBtn-' + reportId).removeClass('btn-outline-danger').addClass('btn-danger');
                        } else {
                            $('#agreeBtn-' + reportId).removeClass('btn-success').addClass('btn-outline-success');
                            $('#disagreeBtn-' + reportId).removeClass('btn-danger').addClass('btn-outline-danger');
                        }
                        
                       
                        alert(response.message);
                    } else {
                        
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your vote.');
                }
            });
        }
    </script>
</body>
</html>