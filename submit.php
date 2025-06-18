<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php', 'Please login to submit news articles', 'warning');
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user['is_verified']) {
    redirect('index.php', 'Please verify your email before submitting news articles', 'warning');
}

//submission limit
$canSubmit = true;
$timeRemaining = 0;
$submissionLimit = MAX_SUBMISSIONS_PER_DAY;

$stmt = $conn->prepare("SELECT submission_count, last_submission FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if ($userData['submission_count'] >= $submissionLimit) {
    $lastSubmission = strtotime($userData['last_submission']);
    $currentTime = time();
    $timeDiff = $currentTime - $lastSubmission;
    
    if ($timeDiff < 86400) { 
        $canSubmit = false;
        $timeRemaining = 86400 - $timeDiff;
    } else {
        $stmt = $conn->prepare("UPDATE users SET submission_count = 0 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canSubmit) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $source = trim($_POST['source']);
    $publicationDate = !empty($_POST['publication_date']) ? $_POST['publication_date'] : null;
    
    //input validation!!
    if (empty($title)) {
        $errors[] = "Title is required";
    } elseif (strlen($title) > 255) {
        $errors[] = "Title must be less than 255 characters";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    if (empty($source)) {
        $errors[] = "Source is required";
    }
    
    if (empty($errors)) {
        //encrypt sensitive content
        $shouldEncrypt = isset($_POST['encrypt']) && $_POST['encrypt'] === '1';
        $encryptedContent = $shouldEncrypt ? encryptData($content) : $content;

            $sourceUrl = filter_var($source, FILTER_VALIDATE_URL) ? $source : "https://" . $source;
            
            $logStmt = $conn->prepare("INSERT INTO submission_activity (user_id, submission_url) VALUES (?, ?)");
            $logStmt->bind_param("is", $userId, $sourceUrl);
            $logStmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content, source, publication_date, encrypted) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $userId, $title, $encryptedContent, $source, $publicationDate, $shouldEncrypt);
            
            if ($stmt->execute()) {
                $articleId = $stmt->insert_id;
                
                $stmt = $conn->prepare("UPDATE users SET submission_count = submission_count + 1, last_submission = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            
            //AI cred score
            $credibilityScore = generateCredibilityScore($content);
            $analysisText = generateAnalysisText($content, $credibilityScore);
            
            //digital signature sa report
            $reportData = $articleId . $credibilityScore . $analysisText;
            $digitalSignature = generateSignature($reportData);
            
            $stmt = $conn->prepare("INSERT INTO reports (article_id, credibility_score, analysis_text, digital_signature) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $articleId, $credibilityScore, $analysisText, $digitalSignature);
            $stmt->execute();
            
            logAction($userId, "Article submitted", "article", $articleId);
            
            $success = true;
        } else {
            $errors[] = "Failed to submit article. Please try again.";
        }
    }
}

function generateCredibilityScore($content) {
    $score = 50; //base score muna
    
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
        $analysis .= "This article appears to be highly credible based on our analysis. ";
        $analysis .= "The content is well-structured and does not contain typical patterns associated with fake news. ";
        $analysis .= "The information presented seems to be factual and balanced.";
    } elseif ($score >= 60) {
        $analysis .= "This article appears to be generally credible, though some aspects could be improved. ";
        $analysis .= "The content contains mostly factual information but may benefit from additional sources or citations. ";
        $analysis .= "No major red flags for misinformation were detected.";
    } elseif ($score >= 40) {
        $analysis .= "This article has mixed credibility indicators. ";
        $analysis .= "While some information appears factual, there are elements that raise concerns. ";
        $analysis .= "Readers should verify key claims from additional trusted sources.";
    } elseif ($score >= 20) {
        $analysis .= "This article shows several characteristics commonly associated with misleading content. ";
        $analysis .= "The information presented lacks sufficient evidence or contains potentially misleading claims. ";
        $analysis .= "Readers should approach with significant caution.";
    } else {
        $analysis .= "This article displays multiple red flags associated with fake news. ";
        $analysis .= "The content contains sensationalist language, unverified claims, or other patterns typical of misinformation. ";
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
    <title>Submit News - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
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
                        <a class="nav-link active" href="submit.php">Submit News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="verify.php">Verify News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['username']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="my-submissions.php">My Submissions</a></li>
                            <?php if (hasRole('admin') || hasRole('reviewer')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin/dashboard.php">Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Submit News Form -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center">Submit News Article</h2>
            
            <?php if (!$canSubmit): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Submission limit reached:</strong> You have reached your daily submission limit of <?php echo $submissionLimit; ?> articles.
                    <p class="mt-2 mb-0">You can submit again in: 
                        <span id="countdown" data-seconds="<?php echo $timeRemaining; ?>"></span>
                    </p>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Success!</strong> Your article has been submitted successfully and is now being analyzed.
                    <p class="mt-2 mb-0">You can view your submission in <a href="my-submissions.php" class="alert-link">My Submissions</a>.</p>
                </div>
                <div class="text-center mt-4">
                    <a href="submit.php" class="btn btn-primary">Submit Another Article</a>
                    <a href="my-submissions.php" class="btn btn-outline-primary ms-2">View My Submissions</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <form action="submit.php" method="POST" novalidate>
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Article Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="content" class="form-label">Article Content <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                                        <div class="form-text">Characters remaining: <span id="char-counter" class="text-muted">5000</span></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="source" class="form-label">Source <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="source" name="source" placeholder="Website, newspaper, or original source" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="publication_date" class="form-label">Publication Date</label>
                                        <input type="text" class="form-control datepicker" id="publication_date" name="publication_date" placeholder="YYYY-MM-DD">
                                    </div>
                                    
                                    <div class="mb-4 form-check">
                                        <input type="checkbox" class="form-check-input" id="encrypt" name="encrypt" value="1">
                                        <label class="form-check-label" for="encrypt">Encrypt this article (for sensitive content)</label>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">Submit Article</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-body p-4">
                                <h5><i class="fas fa-info-circle text-primary me-2"></i>Submission Guidelines</h5>
                                <ul class="mb-0">
                                    <li>All submissions are analyzed by our AI system for credibility.</li>
                                    <li>You can submit up to <?php echo $submissionLimit; ?> articles per day.</li>
                                    <li>Provide accurate source information to improve credibility assessment.</li>
                                    <li>For sensitive content, use the encryption option to protect your data.</li>
                                    <li>Submitted articles will be reviewed by our team if flagged by the AI system.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
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

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/main.js"></script>
    <script>
        const countdownElement = document.getElementById('countdown');
        if (countdownElement) {
            let seconds = parseInt(countdownElement.getAttribute('data-seconds'));
            
            function updateCountdown() {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                
                countdownElement.textContent = `${hours}h ${minutes}m ${secs}s`;
                
                if (seconds > 0) {
                    seconds--;
                    setTimeout(updateCountdown, 1000);
                } else {
                    window.location.reload();
                }
            }
            
            updateCountdown();
        }
    </script>
</body>
</html>