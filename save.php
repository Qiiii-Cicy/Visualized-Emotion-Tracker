<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 读取请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 验证必需字段
if (!isset($data['user_id']) || !isset($data['week']) || !isset($data['day']) || !isset($data['emotion_data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$user_id = $data['user_id'];
$week = $data['week'];
$day = $data['day'];
$emotion_data = $data['emotion_data'];
$first_use_date = isset($data['first_use_date']) ? $data['first_use_date'] : null;
$current_day = isset($data['current_day']) ? $data['current_day'] : null;

// 验证用户ID是否在允许列表中
$allowed_user_ids = ['A07u', 'B22o', 'C3ci', 'D5an', 'E4cho', 'F7nn', 'G9gi', 'test'];
if (!in_array($user_id, $allowed_user_ids)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized user ID']);
    exit;
}

// 读取现有数据
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

// 初始化用户数据结构
if (!isset($all_data['users'][$user_id])) {
    $all_data['users'][$user_id] = [];
}

if (!isset($all_data['users'][$user_id][$week])) {
    $all_data['users'][$user_id][$week] = [
        'day1' => [],
        'day2' => [],
        'day3' => [],
        'day4' => [],
        'day5' => [],
        'day6' => [],
        'day7' => [],
        'meta' => [
            'first_use_date' => null,
            'current_day' => null
        ]
    ];
}

// 确保meta字段存在
if (!isset($all_data['users'][$user_id][$week]['meta'])) {
    $all_data['users'][$user_id][$week]['meta'] = [
        'first_use_date' => null,
        'current_day' => null
    ];
}

// 更新首次使用日期和当前day
// 首次使用日期：如果后端已有，保持不变；如果没有且前端提供了，则使用前端的
if (!$all_data['users'][$user_id][$week]['meta']['first_use_date'] && $first_use_date) {
    $all_data['users'][$user_id][$week]['meta']['first_use_date'] = $first_use_date;
}
// 当前day：总是更新为最新的
if ($current_day !== null) {
    $all_data['users'][$user_id][$week]['meta']['current_day'] = $current_day;
}

// 保存情绪数据
if (!isset($all_data['users'][$user_id][$week][$day])) {
    $all_data['users'][$user_id][$week][$day] = [];
}

// 检查是否已存在相同的emotion，如果存在则更新，否则添加
$emotion_type = $emotion_data['emotion'];
$found = false;

foreach ($all_data['users'][$user_id][$week][$day] as $index => $item) {
    if ($item['emotion'] === $emotion_type) {
        // 更新现有记录
        $all_data['users'][$user_id][$week][$day][$index] = $emotion_data;
        $found = true;
        break;
    }
}

if (!$found) {
    // 添加新记录
    $all_data['users'][$user_id][$week][$day][] = $emotion_data;
}

// 保存到文件
$result = file_put_contents($data_file, json_encode($all_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data']);
    exit;
}

// 返回成功响应
echo json_encode([
    'success' => true,
    'message' => 'Data saved successfully'
]);
?>

