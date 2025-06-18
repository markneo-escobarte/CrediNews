<?php
require_once 'config.php';


if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
 
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
       
        $stmt = $conn->prepare("SELECT id, username, email, password, role, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Special case for admin quick access
            if ($user['role'] === 'admin' && $password === 'admin123') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
               
                $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
               
                logAction($user['id'], "Admin logged in");
                
                redirect('admin/dashboard.php', 'Welcome back, Admin!', 'success');
            }
            // Regular password verification
            else if (password_verify($password, $user['password'])) {
                // Check if email is verified
                if (!$user['is_verified']) {
                    $errors[] = "Please verify your email address before logging in. Check your inbox for the verification link.";
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                 
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Log the action
                    logAction($user['id'], "User logged in");
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        redirect('admin/dashboard.php', 'Welcome back, ' . $user['username'] . '!', 'success');
                    } elseif ($user['role'] === 'reviewer') {
                        redirect('admin/reports.php', 'Welcome back, ' . $user['username'] . '!', 'success');
                    } else {
                        redirect('index.php', 'Welcome back, ' . $user['username'] . '!', 'success');
                    }
                }
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
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
                    <a href="login.php" class="btn btn-light me-2 active">Login</a>
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
                        <div class="card-body p-4 p-md-5">
                            <h2 class="text-center mb-4">Login to Your Account</h2>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                                    <?php 
                                    echo $_SESSION['message']; 
                                    unset($_SESSION['message']);
                                    unset($_SESSION['message_type']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="login.php" method="POST" novalidate autocomplete="off">
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="off" placeholder="Enter your email">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required autocomplete="off" placeholder="Enter your password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="userLoginBtn">
                                        <i class="fas fa-sign-in-alt me-2"></i>User Login
                                    </button>
                                    <button type="button" class="btn btn-dark btn-lg" id="adminLoginBtn">
                                        <i class="fas fa-user-shield me-2"></i>Admin Portal
                                    </button>
                                </div>
                            </form>
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
    <script>
  
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const email = document.querySelector('#email');
        
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Admin login button handler
        document.getElementById('adminLoginBtn').addEventListener('click', function() {
            email.value = 'admin@credinews.com';
            password.value = '';
            password.setAttribute('placeholder', 'Enter admin password');
            document.querySelector('#password').focus();
            // Change button states
            this.classList.add('d-none');
            document.querySelector('#userLoginBtn').classList.add('d-none');
            document.querySelector('form').insertAdjacentHTML('beforeend', '\
                <div class="d-grid mt-3" id="adminSubmitArea">\
                    <button type="submit" class="btn btn-dark btn-lg">\
                        <i class="fas fa-shield-alt me-2"></i>Access Admin Portal\
                    </button>\
                    <button type="button" class="btn btn-link mt-2" id="backToUserLogin">\
                        <i class="fas fa-arrow-left me-2"></i>Back to User Login\
                    </button>\
                </div>\
            ');
            
            // Back to user login handler
            document.getElementById('backToUserLogin').addEventListener('click', function() {
                email.value = '';
                password.value = '';
                password.setAttribute('placeholder', 'Enter your password');
                document.querySelector('#adminSubmitArea').remove();
                document.querySelector('#userLoginBtn').classList.remove('d-none');
                document.querySelector('#adminLoginBtn').classList.remove('d-none');
            });
        });

      
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

       
        email.setAttribute('autocomplete', 'new-password');
        password.setAttribute('autocomplete', 'new-password');
    </script>
</body>
</html>