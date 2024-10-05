<?php
session_start();

// 定義 Packetlist 資料夾的網頁路徑
$packetlistWebDir = '/Packetlist/';
$packetlistDir = 'C:\\share\\phpweb\\Packetlist\\';
$sessionFile = "C:\\share\\phpweb\\session_data.txt"; // session 備份文件路徑

// 讀取資料夾中的所有檔案
$allFiles = scandir($packetlistDir);



// 篩選出 .pcap 檔案
$historyFiles = array_filter($allFiles, function($file) use ($packetlistDir) {
    return is_file($packetlistDir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'pcap';
});

// 刪除檔案（防止路徑穿越攻擊，並加強安全性）
if (isset($_POST['delete_file'])) {
    $fileToDelete = $_POST['delete_file'];
    $filePath = realpath($packetlistDir . basename($fileToDelete));

    // 確保檔案存在並且位於正確的資料夾內
    if ($filePath && strpos($filePath, realpath($packetlistDir)) === 0) {
        if (file_exists($filePath)) {
            unlink($filePath); // 刪除檔案
            echo "<p style='color:green;'>檔案已成功刪除: " . htmlspecialchars($fileToDelete) . "</p>";
        } else {
            echo "<p style='color:red;'>檔案不存在或無法刪除: " . htmlspecialchars($fileToDelete) . "</p>";
        }
    } else {
        echo "<p style='color:red;'>非法的檔案路徑。</p>";
    }
}

// 設定計時器狀態
$timerRunning = false;
$elapsedTime = 0; // 初始化已過時間

// 嘗試從 session 文件讀取資料
if (file_exists($sessionFile)) {
    $sessionData = json_decode(file_get_contents($sessionFile), true);
    if (isset($sessionData['start_time'])) {
        $_SESSION['start_time'] = $sessionData['start_time'];
        $_SESSION['end_time'] = $sessionData['end_time'];
    }
}
else
{
    session_unset();
        session_destroy();
        $timerRunning = false; // 停止計時器
}

if (isset($_SESSION['start_time'])) {
    $timerRunning = true;
    $elapsedTime = time() - $_SESSION['start_time']; // 計算經過的時間
}

// 執行命令（加強輸入安全性，並避免潛在危險命令）
$cmdResult = '';
if (isset($_POST['cmd'])) {
    $command = $_POST['cmd'];
    $allowedCommands = ['ipconfig', 'ping', 'wmic', 'whoami', 'dir', 'tshark','taskkill','tasklist','schtasks'];

    $isAllowed = false;
    foreach ($allowedCommands as $allowedCommand) {
        if (stripos($command, $allowedCommand) === 0) {
            $isAllowed = true;
            break;
        }
    }

    if ($isAllowed) {
        // 安全執行命令，防止特殊字符帶來的命令注入
        $command = 'chcp 65001 & ' .escapeshellcmd($command);
        exec($command, $output, $return_var); // 使用 exec 來獲取結果

        if ($return_var == 0) {
            $cmdResult = implode("<br>", array_map('htmlspecialchars', $output));
        } else {
            $cmdResult = '命令執行失敗！';
        }
    } else {
        $cmdResult = '命令不被允許執行！';
    }
    
}



?>




<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>GZ封包側錄系統</title>
    <style>
        body {
            background-color: #87CEEB; /* 天空藍背景 */
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        h1 {
            color: #2c3e50;
        }

        form {
            margin: 20px 0;
        }

        label {
            font-size: 18px;
            color: #34495e;
        }

        input[type="number"], input[type="text"] {
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
            font-size: 16px;
        }

        input[type="submit"] {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #2980b9;
        }

        .timer {
            font-size: 18px;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        .history table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .history th, .history td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        .history th {
            background-color: #3498db;
            color: white;
        }

        .history td a {
            color: #3498db;
            text-decoration: none;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .cmd-result {
            margin-top: 20px;
            text-align: left;
        }

        .cmd-result p {
            background-color: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
        }

        /* 新增的樣式 */
        .logo {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 100px; /* 調整 LOGO 寬度 */
        }
    </style>
     <script>
    let timerInterval;
    let elapsedTime = <?php echo $elapsedTime; ?>; // 取得 PHP 中已過時間

    function startCapture() {
        let hours = document.getElementById('hours').value;

        fetch('capture.php?hours=' + hours, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'started') {
                // 成功後開始計時器
                document.getElementById('timer').innerHTML = "擷取中...";
                startTimer(); // 開始前端計時器
            } else {
                console.error('封包擷取發生錯誤:', data.message);
            }
        })
        .catch(error => {
            console.error('發生錯誤:', error);
        });
    }

    function stopCapture() {
    fetch('stopcapture.php?action=stop_capture', {
        method: 'GET'
    })
    .then(response => response.text())
    .then(data => {
        console.log(data); // 在控制台顯示返回的訊息
    })
    .catch(error => {
        console.error('發生錯誤:', error);
    });
}


    function startTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
        }
        timerInterval = setInterval(() => {
            elapsedTime++;
            let hours = Math.floor(elapsedTime / 3600);
            let minutes = Math.floor((elapsedTime % 3600) / 60);
            let seconds = elapsedTime % 60;

            document.getElementById('timer').innerHTML = 
                `擷取時間: ${hours} 時 ${minutes} 分 ${seconds} 秒`;
        }, 1000);
    }

    window.onload = function() {
        if (elapsedTime > 0) {
            startTimer();
            document.getElementById('timer').innerHTML = "擷取中...";
        } else {
            document.getElementById('timer').innerHTML = "尚未開始擷取";
        }
    };

    function confirmDelete(fileName) {
        return confirm('確定要刪除檔案: ' + fileName + ' 嗎？');
    }
</script>
</head>
<body>

    <!-- 左上角的 LOGO -->
    <img src="logo.png" alt="Logo" class="logo">

    <!-- 主要內容 -->
    <div class="container">
        <h1>GZ封包側錄系統</h1>
        <form onsubmit="event.preventDefault(); startCapture();">
            <label for="hours">擷取時間（小時）：</label><br>
            <input type="number" id="hours" name="hours" min="1" max="24" required><br>
            <input type="submit" value="開始擷取封包">
        </form>

        <!-- 計時器 -->
        <div class="timer" id="timer">
            <?php
            if ($timerRunning) {
                echo "擷取中...";
            } else {
                echo "尚未開始擷取";
            }
            ?>
        </div>

        <!-- 停止擷取按鈕 -->
        <?php if ($timerRunning): ?>
        <form onsubmit="event.preventDefault(); stopCapture();">
            <input type="hidden" name="action" value="stop_capture">
            <input type="submit" value="停止擷取封包">
        </form>
        <?php endif; ?>


        <!-- 歷史採集項目 -->
        <div class="history" id="history">
            <h2>歷史採集的項目</h2>
            <table>
                <thead>
                    <tr>
                        <th>檔名</th>
                        <th>檔案大小</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($historyFiles as $file) {
                        $fileSize = filesize($packetlistDir . $file);
                        $fileSizeFormatted = number_format($fileSize / 1024, 2) . ' KB';
                        $fileSafe = htmlspecialchars(basename($file));

                        echo "<tr>";
                        echo "<td><a href='$packetlistWebDir$fileSafe' download>$fileSafe</a></td>";
                        echo "<td>$fileSizeFormatted</td>";
                        echo "<td>";
                        echo "<form method='POST' style='display:inline;' onsubmit='return confirmDelete(\"$fileSafe\")'>";
                        echo "<input type='hidden' name='delete_file' value='$fileSafe'>";
                        echo "<input type='submit' class='delete-btn' value='刪除'>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <h2>執行系統命令</h2>
<form method="POST">
    <select name="cmd">
        <option value="tshark -D">列出採集網卡代號</option>
        <option value="ipconfig">顯示網卡資訊</option>
        <option value="wmic logicaldisk get size,freespace,caption">顯示磁碟使用情況</option>
        <option value='tasklist /FI "IMAGENAME eq dumpcap.exe"'>顯示程式資源使用情況</option>
        <option value='schtasks /run /tn "Poweroff"'>系統關機</option>
    </select>
    <input type="submit" value="執行">
</form>

        <!-- 顯示命令結果 -->
        <div class="cmd-result">
            <h3>命令結果：</h3>
            <p><?php echo $cmdResult; ?></p>
        </div>
    </div>

</body>
</html>
