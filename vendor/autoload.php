<?php
// autoloader para sa PHPMailer
spl_autoload_register(function ($class) {
    // amespace prefix ng PHPMailer
    $prefix = 'PHPMailer\\PHPMailer\\';
    
    // base folder kung saan naka-store ang PHPMailer classes
    $base_dir = __DIR__ . '/phpmailer/phpmailer/src/';
    
    // check kung gumagamit ng tamang namespace yung class
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // kung no, skip lang at try next autoloader
        return;
    }
    
    // kunin yung part ng class name na wala na yung prefix
    $relative_class = substr($class, $len);
    
    // paltan yung namespace separator (\\) ng folder separator (/) at idagdag ang .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // kung existing yung file, i-require na siya
    if (file_exists($file)) {
        require $file;
    }
});
