<?php
session_start(); // 開始 session

if (isset($_GET['hours'])) {
    // 獲取使用者輸入的小時數，並將其轉換成秒數
    $hours = intval($_GET['hours']);
    $duration = $hours * 3600; // 小時數轉換為秒數
    $interface = 5; // 設定介面代號

    // 使用當前時間生成輸出檔案名稱
    $timestamp = date('Y-m-d_H-i-s'); // 當下時間：年-月-日_時-分-秒
    $outputFile = "C:\\share\\phpweb\\Packetlist\\capture_$timestamp.pcap"; // 儲存檔案路徑

    // 記錄開始時間與結束時間到 session 中
    $_SESSION['start_time'] = time();
    $_SESSION['end_time'] = $_SESSION['start_time'] + $duration;

    // 將 session 寫入並結束 session 操作，確保數據被保存
    session_write_close();

    // 保存 session 到伺服器上的文件中
    $sessionData = [
        'start_time' => $_SESSION['start_time'],
        'end_time' => $_SESSION['end_time']
    ];

    // 指定保存的文件路徑
    $sessionFile = "C:\\share\\phpweb\\session_data.txt";
    
    // 將 session 資料轉換為 JSON 字串並寫入文件
    file_put_contents($sessionFile, json_encode($sessionData));

    // 返回 JSON 格式的響應
    echo json_encode(['status' => 'started']);
}

// PowerShell 腳本路徑
$scriptPath = 'C:\\share\\phpweb\\script.ps1';

// 使用 escapeshellarg 來保護變數，防止命令注入
$interfaceArg = escapeshellarg($interface);
$durationArg = escapeshellarg($duration);
$outputFileArg = escapeshellarg($outputFile);

// 構建命令
$command = "powershell -ExecutionPolicy Bypass -File $scriptPath $interfaceArg $durationArg $outputFileArg";

// 執行 PowerShell 腳本
shell_exec($command);

?>
