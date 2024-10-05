<?php
$sessionFile = "C:\\share\\phpweb\\session_data.txt"; // session 備份文件路徑
// 停止擷取邏輯
if (isset($_GET['action']) && $_GET['action'] === 'stop_capture') {
    // 停止擷取，使用 system 停止 dumpcap 進程
    $stopCommand = "taskkill /F /IM Dumpcap.exe";
    
    // 執行命令並取得返回狀態
    exec($stopCommand, $output, $return_var);

    // 判斷命令是否執行成功
    if ($return_var == 0) {
        echo "<p style='color:green;'>擷取已停止。</p>";

        // 刪除 session_data.txt 文件
        if (file_exists($sessionFile)) {
            unlink($sessionFile);
            echo "<p style='color:green;'>Session 資料已清除。</p>";
        }

        // 清空 session
        session_unset();
        session_destroy();
        $timerRunning = false; // 停止計時器
    } else {
        echo "<p style='color:red;'>停止擷取失敗。</p>";
    }
}
?>

