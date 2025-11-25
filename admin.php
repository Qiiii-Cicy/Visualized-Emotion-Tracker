<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 读取数据文件
$data_file = 'data.json';
$all_data = [];

if (file_exists($data_file)) {
    $file_content = file_get_contents($data_file);
    $all_data = json_decode($file_content, true);
    if ($all_data === null) {
        $all_data = ['users' => []];
    }
} else {
    $all_data = ['users' => []];
}

// 计算每个用户每个week的当前day（如果首次使用日期存在）
$users_data = $all_data['users'] ?? [];
foreach ($users_data as $user_id => $weeks) {
    foreach ($weeks as $week => $week_data) {
        if (isset($week_data['meta']['first_use_date']) && $week_data['meta']['first_use_date']) {
            $first_use_date = new DateTime($week_data['meta']['first_use_date']);
            $today = new DateTime();
            $today->setTime(0, 0, 0, 0);
            $first_use_date->setTime(0, 0, 0, 0);
            
            // 使用时间戳计算天数差，与前端JavaScript逻辑保持一致
            $first_timestamp = $first_use_date->getTimestamp();
            $today_timestamp = $today->getTimestamp();
            $diff_seconds = $today_timestamp - $first_timestamp;
            $diff_days = floor($diff_seconds / (24 * 60 * 60));
            
            // 计算当前day（1-7循环），与前端逻辑保持一致
            $current_day = (($diff_days % 7) + 7) % 7 + 1;
            $users_data[$user_id][$week]['meta']['current_day'] = $current_day;
            $users_data[$user_id][$week]['meta']['first_use_date_display'] = $first_use_date->format('Y-m-d');
        }
    }
}

// 返回所有用户的所有数据
echo json_encode([
    'success' => true,
    'data' => $users_data
]);
?>

