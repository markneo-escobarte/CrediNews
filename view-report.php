<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php', 'Invalid report ID', 'danger');
}

$reportId = (int)$_GET['id'];
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;

//report details
$stmt = $conn->prepare("SELECT r.id, r.credibility_score, r.analysis_text, r.digital_signature, r.generated_at, 
                        a.id as article_id, a.title, a.content, a.source, a.submission_date, a.user_id as author_id, 
                        u.username as author_name 
                        FROM reports r 
                        JOIN articles a ON r.article_id = a.id 
                        JOIN users u ON a.user_id = u.id 
                        WHERE r.id = ?");
$stmt->bind_param("i", $reportId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('index.php', 'Report not found', 'danger');
}

$report = $result->fetch_assoc();

//vote counts
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
function getUserVote($reportId, $userId, $conn) {
    if (!$userId) return null;
    
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

$voteCounts = getVoteCounts($reportId, $conn);
$userVote = $userId ? getUserVote($reportId, $userId, $conn) : null;
$scoreClass = getScoreClass($report['credibility_score']);
$scoreIcon = getScoreIcon($report['credibility_score']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Report - CrediNews</title>
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
                            <li><a class="dropdown-item" href="my-submissions.php">My Submissions</a></li>
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
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><?php echo htmlspecialchars($report['title']); ?></h2>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Source:</h5>
                        <p><?php echo htmlspecialchars($report['source']); ?></p>
                        
                        <h5>Submitted by:</h5>
                        <p><?php echo htmlspecialchars($report['author_name']); ?></p>
                        
                        <h5>Submission Date:</h5>
                        <p><?php echo date('F d, Y', strtotime($report['submission_date'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Credibility Score:</h5>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 me-2" style="height: 30px;">
                                <div class="progress-bar bg-<?php echo ($report['credibility_score'] >= 80) ? 'success' : (($report['credibility_score'] >= 60) ? 'primary' : (($report['credibility_score'] >= 40) ? 'warning' : 'danger')); ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $report['credibility_score']; ?>%" 
                                     aria-valuenow="<?php echo $report['credibility_score']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo $report['credibility_score']; ?>%
                                </div>
                            </div>
                            <span class="fs-1 <?php echo $scoreClass; ?>">
                                <i class="fas <?php echo $scoreIcon; ?>"></i>
                            </span>
                        </div>
                        
                        <h5 class="mt-4">Verification Date:</h5>
                        <p><?php echo date('F d, Y', strtotime($report['generated_at'])); ?></p>
                        
                        <h5>Report ID:</h5>
                        <p class="text-muted">#<?php echo $report['id']; ?></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h4>Article Content:</h4>
                    <div class="card">
                        <div class="card-body bg-light">
                            <?php echo nl2br(htmlspecialchars($report['content'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h4>AI Analysis:</h4>
                    <div class="card">
                        <div class="card-body bg-light">
                            <?php echo nl2br(htmlspecialchars($report['analysis_text'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h4>Digital Signature:</h4>
                    <div class="card">
                        <div class="card-body bg-light">
                            <code class="text-muted small"><?php echo htmlspecialchars($report['digital_signature']); ?></code>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h4>Community Feedback:</h4>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-center mb-3">
                                <div class="me-4 text-center">
                                    <div class="fs-1 text-success"><?php echo $voteCounts['agree']; ?></div>
                                    <div>Agree</div>
                                </div>
                                <div class="text-center">
                                    <div class="fs-1 text-danger"><?php echo $voteCounts['disagree']; ?></div>
                                    <div>Disagree</div>
                                </div>
                            </div>
                            
                            <?php if (isLoggedIn()): ?>
                            <div class="d-flex justify-content-center">
                                <button id="agreeBtn" 
                                        class="btn <?php echo ($userVote === 'agree') ? 'btn-success' : 'btn-outline-success'; ?> me-2" 
                                        onclick="voteOnReport('<?php echo $reportId; ?>', 'agree')">
                                    <i class="fas fa-thumbs-up"></i> Agree
                                    <span id="agreeCount" class="badge bg-success"><?php echo $voteCounts['agree']; ?></span>
                                </button>
                                <button id="disagreeBtn" 
                                        class="btn <?php echo ($userVote === 'disagree') ? 'btn-danger' : 'btn-outline-danger'; ?>" 
                                        onclick="voteOnReport('<?php echo $reportId; ?>', 'disagree')">
                                    <i class="fas fa-thumbs-down"></i> Disagree
                                    <span id="disagreeCount" class="badge bg-danger"><?php echo $voteCounts['disagree']; ?></span>
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">Login to Vote</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="verify.php" class="btn btn-primary">Verify Another</a>
                    <?php if (isLoggedIn() && $report['author_id'] === $userId): ?>
                    <a href="my-submissions.php" class="btn btn-secondary">Back to My Submissions</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
                <p class="mb-0">&copy; 2023 CrediNews. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        function voteOnReport(reportId, voteType) {
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
                        $('#agreeCount').text(response.vote_counts.agree);
                        $('#disagreeCount').text(response.vote_counts.disagree);

                        if (response.user_vote === 'agree') {
                            $('#agreeBtn').removeClass('btn-outline-success').addClass('btn-success');
                            $('#disagreeBtn').removeClass('btn-danger').addClass('btn-outline-danger');
                        } else if (response.user_vote === 'disagree') {
                            $('#agreeBtn').removeClass('btn-success').addClass('btn-outline-success');
                            $('#disagreeBtn').removeClass('btn-outline-danger').addClass('btn-danger');
                        } else {
                            $('#agreeBtn').removeClass('btn-success').addClass('btn-outline-success');
                            $('#disagreeBtn').removeClass('btn-danger').addClass('btn-outline-danger');
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