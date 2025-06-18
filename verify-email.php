<?php
require_once 'config.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    redirect('login.php', 'Invalid verification link', 'danger');
}

$token = $_GET['token'];
$verified = false;
$error = '';

$stmt = $conn->prepare("SELECT id, username, is_verified FROM users WHERE verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Check if already verified
    if ($user['is_verified']) {
        redirect('login.php', 'Your account is already verified. Please login.', 'info');
    }
    
    // Update user as verified
    $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
    $update_stmt->bind_param("i", $user['id']);
    
    if ($update_stmt->execute()) {
        $verified = true;
        logAction($user['id'], "Email verified");
    } else {
        $error = "Failed to verify your account. Please try again.";
    }
    
    $update_stmt->close();
} else {
    $error = "Invalid verification token.";
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo APP_NAME; ?></title>
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
                        <a class="nav-link" href="verify.php">Verify News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-light me-2">Login</a>
                    <a href="register.php" class="btn btn-outline-light">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5 text-center">
                            <?php if ($verified): ?>
                                <div class="mb-4">
                                    <i class="fas fa-check-circle text-success fa-5x"></i>
                                </div>
                                <h2 class="mb-3">Email Verified Successfully!</h2>
                                <p class="mb-4">Your account has been successfully verified. You can now login and access all features of <?php echo APP_NAME; ?>.</p>
                                <a href="login.php" class="btn btn-primary btn-lg">Login Now</a>
                            <?php else: ?>
                                <div class="mb-4">
                                    <i class="fas fa-times-circle text-danger fa-5x"></i>
                                </div>
                                <h2 class="mb-3">Verification Failed</h2>
                                <p class="mb-4"><?php echo $error; ?></p>
                                <a href="index.php" class="btn btn-primary btn-lg">Back to Home</a>
                            <?php endif; ?>
                        </div>
                    </div>
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
</body>
</html>