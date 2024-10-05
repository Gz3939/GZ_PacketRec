<?php
session_start();

if (isset($_SESSION['start_time'])) {
    $currentTime = time();
    $elapsedTime = $currentTime - $_SESSION['start_time'];
    $remainingTime = $_SESSION['end_time'] - $currentTime;
    
    // 判斷封包擷取是否完成
    if ($remainingTime <= 0) {
        echo json_encode(['status' => 'completed']);
    } else {
        echo json_encode([
            'status' => 'running',
            'elapsed_time' => $elapsedTime,
            'remaining_time' => $remainingTime
        ]);
    }
} else {
    echo json_encode(['status' => 'not_started']);
}
?>
