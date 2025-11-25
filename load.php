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

// 读取请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 验证必需字段
if (!isset($data['user_id']) || !isset($data['week'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$user_id = $data['user_id'];
$week = $data['week'];

// 验证用户ID是否在允许列表中
$allowed_user_ids = ['A07u', 'B22o', 'C3ci', 'D5an', 'E4cho', 'F7nn', 'G9gi', 'test'];
if (!in_array($user_id, $allowed_user_ids)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized user ID']);
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

// 获取用户数据
$user_data = [];
if (isset($all_data['users'][$user_id])) {
    $user_data = $all_data['users'][$user_id];
}

// 只返回指定week的数据
$result_data = [];
$meta_data = null;

if (isset($user_data[$week])) {
    $week_data = $user_data[$week];
    
    // 提取meta信息
    if (isset($week_data['meta'])) {
        $meta_data = $week_data['meta'];
        unset($week_data['meta']); // 从数据中移除meta，只保留day数据
    }
    
    $result_data = $week_data;
    // 兼容旧数据格式，转换为新格式
    $day_mapping = [
        'monday' => 'day1',
        'tuesday' => 'day2',
        'wednesday' => 'day3',
        'thursday' => 'day4',
        'friday' => 'day5',
        'saturday' => 'day6',
        'sunday' => 'day7'
    ];
    
    $converted_data = [
        'day1' => [],
        'day2' => [],
        'day3' => [],
        'day4' => [],
        'day5' => [],
        'day6' => [],
        'day7' => []
    ];
    
    foreach ($result_data as $day => $data) {
        if (isset($day_mapping[$day])) {
            $converted_data[$day_mapping[$day]] = $data;
        } elseif (preg_match('/^day[1-7]$/', $day)) {
            $converted_data[$day] = $data;
        }
    }
    
    $result_data = $converted_data;
} else {
    // 如果该周没有数据，返回空结构
    $result_data = [
        'day1' => [],
        'day2' => [],
        'day3' => [],
        'day4' => [],
        'day5' => [],
        'day6' => [],
        'day7' => []
    ];
}

// 返回数据（包含meta信息）
$response = [
    'success' => true,
    'data' => $result_data,
    'week' => $week
];

if ($meta_data !== null) {
    $response['meta'] = $meta_data;
}

echo json_encode($response);
?>

