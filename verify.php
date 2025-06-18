<?php
require_once 'config.php';

function getVoteCounts($reportId) {
    global $conn;
    
    $agree = 0;
    $disagree = 0;
    
    $stmt = $conn->prepare("SELECT  vote_type, COUNT(*) as count FROM report_votes WHERE report_id = ? GROUP BY vote_type");
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

// nag chech if yung user natin is nag vote
function getUserVote($reportId, $userId) {
    global $conn;
    
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

$isLoggedIn = isLoggedIn();

$errors = [];
$result = null;

// Process verification form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['url']) && !empty($_POST['url']) && !$isLoggedIn) {
        $errors[] = "Please login to verify news by URL";
    } else {
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        if (empty($content) && empty($url)) {
            $errors[] = "Please provide either article content or a URL";
        }
        
        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "Please enter a valid URL";
        }
        
        if (empty($errors)) {
            $textToAnalyze = !empty($content) ? $content : "Sample content from URL: $url";
    
            $credibilityScore = generateCredibilityScore($textToAnalyze);
            $analysisText = generateAnalysisText($textToAnalyze, $credibilityScore);
            $reportData = $textToAnalyze . $credibilityScore . $analysisText;
            $digitalSignature = generateSignature($reportData);
            
            // display yung result
            $result = [
                'score' => $credibilityScore,
                'analysis' => $analysisText,
                'signature' => $digitalSignature
            ];
            
            // login na yung user
            if ($isLoggedIn) {
                logAction($_SESSION['user_id'], "News verification", "verification", null);
            }
        }
    }
}

// scanning or determining yung url na binigay ni user dito guys
function generateCredibilityScore($content) {
    $score = 50; 
    
    $length = strlen($content);
    if ($length > 1000) $score += 10;
    if ($length > 3000) $score += 10;

    if (preg_match('/\[\d+\]/', $content)) $score += 15;
    
    $questionablePhrases = ['shocking truth', 'they don\'t want you to know', 'secret', 'conspiracy', 
                           'miracle', 'shocking', 'you won\'t believe', 'doctors hate'];
    foreach ($questionablePhrases as $phrase) {
        if (stripos($content, $phrase) !== false) $score -= 10;
    }
    
    return max(0, min(100, $score));
}
function generateAnalysisText($content, $score) {
    $analysis = "Automated AI Analysis:\n\n";
    
    if ($score >= 80) {
        $analysis .= "This content appears to be highly credible based on our analysis. ";
        $analysis .= "The text is well-structured and does not contain typical patterns associated with fake news. ";
        $analysis .= "The information presented seems to be factual and balanced.";
    } elseif ($score >= 60) {
        $analysis .= "This content appears to be generally credible, though some aspects could be improved. ";
        $analysis .= "The text contains mostly factual information but may benefit from additional sources or citations. ";
        $analysis .= "No major red flags for misinformation were detected.";
    } elseif ($score >= 40) {
        $analysis .= "This content has mixed credibility indicators. ";
        $analysis .= "While some information appears factual, there are elements that raise concerns. ";
        $analysis .= "Readers should verify key claims from additional trusted sources.";
    } elseif ($score >= 20) {
        $analysis .= "This content shows several characteristics commonly associated with misleading information. ";
        $analysis .= "The text lacks sufficient evidence or contains potentially misleading claims. ";
        $analysis .= "Readers should approach with significant caution.";
    } else {
        $analysis .= "This content displays multiple red flags associated with fake news. ";
        $analysis .= "The text contains sensationalist language, unverified claims, or other patterns typical of misinformation. ";
        $analysis .= "The information should not be considered reliable without substantial verification.";
    }
    
    return $analysis;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify News - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt me-2"></i><?php echo APP_NAME; ?>
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
                        <a class="nav-link" href="submit.php">Submit News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="verify.php">Verify News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['username']; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="my-submissions.php">My Submissions</a></li>
                                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'reviewer')): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/dashboard.php">Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-light me-2">Login</a>
                        <a href="register.php" class="btn btn-outline-light">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center">Verify News</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($result): ?>
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="card-title text-center mb-4">Verification Result</h4>
                                
                                <div class="verification-result bg-light">
                                    <?php 
                                    $scoreClass = 'score-low';
                                    $scoreIcon = 'exclamation-triangle';
                                    $scoreText = 'Low Credibility';
                                    
                                    if ($result['score'] >= 80) {
                                        $scoreClass = 'score-high';
                                        $scoreIcon = 'check-circle';
                                        $scoreText = 'High Credibility';
                                    } elseif ($result['score'] >= 60) {
                                        $scoreClass = 'score-high';
                                        $scoreIcon = 'check-circle';
                                        $scoreText = 'Generally Credible';
                                    } elseif ($result['score'] >= 40) {
                                        $scoreClass = 'score-medium';
                                        $scoreIcon = 'exclamation-circle';
                                        $scoreText = 'Mixed Credibility';
                                    } elseif ($result['score'] >= 20) {
                                        $scoreClass = 'score-low';
                                        $scoreIcon = 'exclamation-triangle';
                                        $scoreText = 'Questionable';
                                    }
                                    ?>
                                    
                                    <div class="mb-3">
                                        <i class="fas fa-<?php echo $scoreIcon; ?> fa-3x <?php echo $scoreClass; ?>"></i>
                                    </div>
                                    <div class="verification-score <?php echo $scoreClass; ?>">
                                        <?php echo $result['score']; ?>/100
                                    </div>
                                    <h4 class="mb-4"><?php echo $scoreText; ?></h4>
                                </div>
                                
                                <div class="mt-4">
                                    <h5>AI Analysis</h5>
                                    <div class="p-3 bg-light rounded">
                                        <pre class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($result['analysis']); ?></pre>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h5>Digital Signature</h5>
                                    <p class="small text-muted">This signature ensures the integrity of this report:</p>
                                    <div class="p-3 bg-light rounded">
                                        <code class="small"><?php echo $result['signature']; ?></code>
                                    </div>
                                </div>
                                
                                <?php if ($isLoggedIn): ?>
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h5>Do you agree with this AI analysis?</h5>
                                    <p class="text-muted small">Your feedback helps improve our AI system and provides transparency to other users.</p>
                                    
                                    <?php 
                                    $tempReportId = md5($result['signature']);
                                    $userVote = getUserVote($tempReportId, $_SESSION['user_id']);
                                    
                                    //vote counts
                                    $voteCounts = getVoteCounts($tempReportId);
                                    ?>
                                    
                                    <div class="d-flex justify-content-center align-items-center gap-4 mb-3">
                                        <button id="agree-btn" class="btn btn-outline-success <?php echo ($userVote === 'agree') ? 'active' : ''; ?>" 
                                                onclick="voteOnReport('<?php echo $tempReportId; ?>', 'agree')">
                                            <i class="fas fa-thumbs-up me-2"></i> Agree
                                            <span id="agree-count" class="badge bg-success ms-1"><?php echo $voteCounts['agree']; ?></span>
                                        </button>
                                        
                                        <button id="disagree-btn" class="btn btn-outline-danger <?php echo ($userVote === 'disagree') ? 'active' : ''; ?>" 
                                                onclick="voteOnReport('<?php echo $tempReportId; ?>', 'disagree')">
                                            <i class="fas fa-thumbs-down me-2"></i> Disagree
                                            <span id="disagree-count" class="badge bg-danger ms-1"><?php echo $voteCounts['disagree']; ?></span>
                                        </button>
                                    </div>
                                    
                                    <div id="vote-message" class="text-center small"></div>
                                </div>
                                <?php else: ?>
                                <div class="mt-4 p-3 bg-light rounded text-center">
                                    <p><i class="fas fa-info-circle me-2"></i> <a href="login.php">Login</a> to provide feedback on this AI analysis.</p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-4 text-center">
                                    <a href="verify.php" class="btn btn-primary">Verify Another</a>
                                    <?php if ($isLoggedIn): ?>
                                        <a href="#" class="btn btn-outline-primary ms-2" onclick="saveReport('<?php echo $tempReportId; ?>', <?php echo $result['score']; ?>, '<?php echo addslashes($result['analysis']); ?>', '<?php echo $result['signature']; ?>')">Save Report</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h4 class="card-title"><i class="fas fa-file-alt text-primary me-2"></i>Verify by Text</h4>
                                <p class="card-text">Paste the news article text to analyze its credibility.</p>
                                
                                <form action="verify.php" method="POST" novalidate>
                                    <div class="mb-3">
                                        <label for="content" class="form-label">Article Content</label>
                                        <textarea class="form-control" id="content" name="content" rows="10" placeholder="Paste the news article text here..."></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Verify Content</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h4 class="card-title"><i class="fas fa-link text-primary me-2"></i>Verify by URL</h4>
                                <p class="card-text">Enter the URL of a news article to analyze its credibility.</p>
                                
                                <?php if (!$isLoggedIn): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Please <a href="login.php" class="alert-link">login</a> to use the URL verification feature.
                                    </div>
                                <?php endif; ?>
                                
                                <form action="verify.php" method="POST" novalidate>
                                    <div class="mb-3">
                                        <label for="url" class="form-label">Article URL</label>
                                        <input type="url" class="form-control" id="url" name="url" placeholder="https://example.com/news-article" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>Verify URL</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- How It Works -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h4 class="card-title"><i class="fas fa-question-circle text-primary me-2"></i>How It Works</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="text-center">
                                    <div class="step-icon mb-3">
                                        <i class="fas fa-robot fa-2x text-primary"></i>
                                    </div>
                                    <h5>AI Analysis</h5>
                                    <p class="small">Our advanced AI algorithms analyze the content for patterns associated with fake news.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="text-center">
                                    <div class="step-icon mb-3">
                                        <i class="fas fa-chart-bar fa-2x text-primary"></i>
                                    </div>
                                    <h5>Credibility Score</h5>
                                    <p class="small">Receive a credibility score from 0-100 based on multiple verification factors.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="step-icon mb-3">
                                        <i class="fas fa-shield-alt fa-2x text-primary"></i>
                                    </div>
                                    <h5>Secure Reports</h5>
                                    <p class="small">All verification reports are digitally signed to ensure their integrity.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5><i class="fas fa-shield-alt me-2"></i><?php echo APP_NAME; ?></h5>
                    <p>An AI-driven platform dedicated to combating fake news and misinformation through advanced verification technology.</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="about.php" class="text-white">About</a></li>
                        <li><a href="verify.php" class="text-white">Verify News</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4 mb-md-0">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li><a href="faq.php" class="text-white">FAQ</a></li>
                        <li><a href="privacy.php" class="text-white">Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-white">Terms of Service</a></li>
                        <li><a href="blog.php" class="text-white">Blog</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h5>Connect</h5>
                    <div class="social-icons mb-3">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="small mb-0">Designed with <i class="fas fa-heart text-danger"></i> for truth seekers</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function voteOnReport(reportId, voteType) {
            $('#vote-message').html('<i class="fas fa-spinner fa-spin"></i> Processing...');
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
                        $('#agree-count').text(response.agree_count);
                        $('#disagree-count').text(response.disagree_count);
                        
                        if (response.message === 'Vote removed successfully') {
                            $('#agree-btn').removeClass('active');
                            $('#disagree-btn').removeClass('active');
                        } else if (voteType === 'agree') {
                            $('#agree-btn').addClass('active');
                            $('#disagree-btn').removeClass('active');
                        } else {
                            $('#agree-btn').removeClass('active');
                            $('#disagree-btn').addClass('active');
                        }
                        
                        $('#vote-message').html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + response.message + '</span>');
                    } else {
                        $('#vote-message').html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>' + response.message + '</span>');
                    }
                    
                    //clear 3 sec
                    setTimeout(function() {
                        $('#vote-message').html('');
                    }, 3000);
                },
                error: function() {
                    $('#vote-message').html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>An error occurred. Please try again.</span>');
                }
            });
        }
        
        function saveReport(reportId, score, analysis, signature) {
            $.ajax({
                url: 'save-report.php',
                type: 'POST',
                data: {
                    report_id: reportId,
                    score: score,
                    analysis: analysis,
                    signature: signature
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        if (response.report_id) {
                            window.location.href = 'my-submissions.php';
                        }
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while saving the report.');
                }
            });
        }
    </script>
</body>
</html>