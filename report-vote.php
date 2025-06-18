<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to vote']);
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user['is_verified']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please verify your email before voting']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id']) && isset($_POST['vote_type'])) {
    $reportId = (int)$_POST['report_id'];
    $voteType = $_POST['vote_type'];
    
    //agree & disagree validation
    if ($voteType !== 'agree' && $voteType !== 'disagree') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid vote type']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id FROM reports WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, vote_type FROM report_votes WHERE report_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reportId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingVote = $result->fetch_assoc();
        
        if ($existingVote['vote_type'] === $voteType) {
            $stmt = $conn->prepare("DELETE FROM report_votes WHERE id = ?");
            $stmt->bind_param("i", $existingVote['id']);
            $stmt->execute();
            
            logAction($userId, "Vote removed", "report", $reportId);
            
            $voteCounts = getVoteCounts($reportId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Vote removed successfully',
                'agree_count' => $voteCounts['agree'],
                'disagree_count' => $voteCounts['disagree']
            ]);
            exit;
        } else {
            $stmt = $conn->prepare("UPDATE report_votes SET vote_type = ? WHERE id = ?");
            $stmt->bind_param("si", $voteType, $existingVote['id']);
            $stmt->execute();
            
            logAction($userId, "Vote updated to " . $voteType, "report", $reportId);
            
            $voteCounts = getVoteCounts($reportId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Vote updated successfully',
                'agree_count' => $voteCounts['agree'],
                'disagree_count' => $voteCounts['disagree']
            ]);
            exit;
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO report_votes (report_id, user_id, vote_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $reportId, $userId, $voteType);
        $stmt->execute();
    
        logAction($userId, "Voted " . $voteType, "report", $reportId);
        
        $voteCounts = getVoteCounts($reportId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Vote recorded successfully',
            'agree_count' => $voteCounts['agree'],
            'disagree_count' => $voteCounts['disagree']
        ]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

function getVoteCounts($reportId) {
    global $conn;
    
    $agree = 0;
    $disagree = 0;
    
    $stmt = $conn->prepare("SELECT vote_type, COUNT(*) as count FROM report_votes WHERE report_id = ? GROUP BY vote_type");
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
?>