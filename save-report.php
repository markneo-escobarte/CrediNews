<?php
require_once 'config.php';

if (!isLoggedIn()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You must be logged in to save reports']);
        exit;
    }
    redirect('login.php', 'Please login to save reports', 'warning');
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user['is_verified']) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please verify your email before saving reports']);
        exit;
    }
    redirect('index.php', 'Please verify your email before saving reports', 'warning');
}

//process ng report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    $reportId = isset($_POST['report_id']) ? trim($_POST['report_id']) : '';
    $score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
    $analysis = isset($_POST['analysis']) ? trim($_POST['analysis']) : '';
    $signature = isset($_POST['signature']) ? trim($_POST['signature']) : '';
    
    if (empty($reportId) || empty($analysis) || empty($signature)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required report data']);
            exit;
        }
        redirect('verify.php', 'Missing required report data', 'danger');
    }
    
    if (strlen($reportId) === 32 && ctype_xdigit($reportId)) { 
     
        $title = "Verified Content " . date('Y-m-d H:i:s');
        $content = "Content verified through the verification tool";
        $source = "Manual verification";
        
        $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content, source, submission_date) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->bind_param("isss", $userId, $title, $content, $source);
        
        if ($stmt->execute()) {
            $articleId = $stmt->insert_id;
            
            $stmt = $conn->prepare("INSERT INTO reports (article_id, credibility_score, analysis_text, digital_signature, generated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->bind_param("idss", $articleId, $score, $analysis, $signature);
            
            if ($stmt->execute()) {
                $newReportId = $stmt->insert_id;
                
                logAction($userId, "Report saved", "report", $newReportId);
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Report saved successfully',
                        'report_id' => $newReportId
                    ]);
                    exit;
                }
                redirect('my-submissions.php', 'Report saved successfully', 'success');
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to save report']);
                    exit;
                }
                redirect('verify.php', 'Failed to save report', 'danger');
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to create article for report']);
                exit;
            }
            redirect('verify.php', 'Failed to create article for report', 'danger');
        }
    } else {
        $stmt = $conn->prepare("SELECT id FROM reports WHERE id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Report not found']);
                exit;
            }
            redirect('verify.php', 'Report not found', 'danger');
        }
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Report already exists',
                'report_id' => $reportId
            ]);
            exit;
        }
        redirect('view-report.php?id=' . $reportId, 'Report already exists', 'info');
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

?>