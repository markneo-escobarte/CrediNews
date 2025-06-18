<?php
require_once 'config.php';

// CHECK IF THE USER IS LOGGED IN
$isLoggedIn = isLoggedIn();
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isReviewer = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'reviewer';

// GET THE LATEST VERIFIED ARTICLES
$latestArticles = [];
$query = "SELECT a.id, a.title, a.source, a.submission_date, u.username 
          FROM articles a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.status = 'verified' 
          ORDER BY a.submission_date DESC LIMIT 5";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latestArticles[] = $row;
    }
}

// GET STATISTICS
$totalArticles = 0;
$totalVerified = 0;
$totalFake = 0;

$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN status = 'fake' THEN 1 ELSE 0 END) as fake
              FROM articles";
$statsResult = $conn->query($statsQuery);

if ($statsResult && $statsResult->num_rows > 0) {
    $stats = $statsResult->fetch_assoc();
    $totalArticles = $stats['total'];
    $totalVerified = $stats['verified'];
    $totalFake = $stats['fake'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - AI-Driven Fake News Detector</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- NAVBAR CREDINEWS  -->
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="submit.php">Submit News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="verify.php">Verify News</a>
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
                                <li><a class="dropdown-item" href="my-submissions.php">Submissions</a></li>
                                <?php if ($isAdmin || $isReviewer): ?>
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
<!-- first row natin to--> 
<header class="highlight-header py-5 mb-5">
  <div class="container">
    <div class="row align-items-center">

      <div class="col-lg-6">
        <h1 class="display-4 fw-bold mb-3">Detect Fake News with AI</h1>
        <p class="lead mb-4">
          CrediNews uses advanced AI algorithms to analyze and verify news articles, helping you distinguish fact from fiction.
        </p>
        <div class="d-grid gap-2 d-md-flex">
          <a href="verify.php" class="btn btn-primary btn-lg px-4 me-md-2">Verify News</a>
          <a href="about.php" class="btn btn-outline-secondary btn-lg px-4">Learn More</a>
        </div>
      </div>

      <div class="col-lg-6 text-center">
        <img src="assets/css/images/credilowgo.png" alt="Logo" class="img-fluid" style="max-height: 300px;">
      </div>

    </div>
  </div>
</header>

<!-- second -->
<section class="status-section py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-newspaper fa-3x text-primary mb-3"></i>
                        <h3 class="counter"><?php echo $totalArticles; ?></h3>
                        <p class="text-muted">Articles Analyzed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h3 class="counter"><?php echo $totalVerified; ?></h3>
                        <p class="text-muted">Verified Articles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h3 class="counter"><?php echo $totalFake; ?></h3>
                        <p class="text-muted">Fake News Detected</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


    <!-- 3rd row-->
    <section class="latest-news py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Latest Verified News</h2>
            
            <?php if (empty($latestArticles)): ?>
                <div class="alert alert-info text-center">
                    No verified news articles available yet.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($latestArticles as $article): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($article['source']); ?></h6>
                                    <p class="card-text small text-muted">
                                        Submitted by <?php echo htmlspecialchars($article['username']); ?> on 
                                        <?php echo date('M d, Y', strtotime($article['submission_date'])); ?>
                                    </p>
                                    <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                                </div>
                                <div class="card-footer bg-white border-0">
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Verified</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="articles.php" class="btn btn-outline-primary">View All Articles</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="step-icon mb-3">
                                <i class="fas fa-upload fa-3x text-primary"></i>
                            </div>
                            <h4>1. Submit News</h4>
                            <p>Register and submit news articles or URLs for verification by our AI system.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="step-icon mb-3">
                                <i class="fas fa-robot fa-3x text-primary"></i>
                            </div>
                            <h4>2. AI Analysis</h4>
                            <p>Our advanced AI algorithms analyze the content for credibility and authenticity.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="step-icon mb-3">
                                <i class="fas fa-clipboard-check fa-3x text-primary"></i>
                            </div>
                            <h4>3. Get Results</h4>
                            <p>Receive a detailed report with credibility score and verification status.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mb-4">Ready to verify news?</h2>
                    <p class="lead mb-4">Join our community of truth-seekers and help combat misinformation.</p>
                    <?php if (!$isLoggedIn): ?>
                        <a href="register.php" class="btn btn-primary btn-lg px-5">Sign Up Now</a>
                    <?php else: ?>
                        <a href="submit.php" class="btn btn-primary btn-lg px-5">Submit News</a>
                    <?php endif; ?>
                </div>
            </div>
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
</body>
</html>