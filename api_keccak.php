<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Функция для вычисления Keccak-512 хеша
function keccak512($data) {
    // Эта функция требует установки расширения или библиотеки для Keccak
    // На InfinityFree это может быть недоступно
    
    // Попытка использовать hash() с keccak
    if (in_array('keccak', hash_algos())) {
        return hash('keccak', $data, false);
    }
    
    // Если Keccak недоступен, используем SHA3-512 с предупреждением
    return hash('sha3-512', $data);
}

function hashcash_pow_keccak($version, $complexity, $timestamp, $resource, $extension, $random_string) {
    // Формируем базовую строку для хеширования
    $base_string = "$version:$complexity:$timestamp:$resource:$extension:$random_string:";
    
    // Создаем строку из нулей для проверки
    $zero_quantity = str_repeat("0", $complexity);
    
    // Проверяем доступность Keccak
    $using_keccak = in_array('keccak', hash_algos());
    
    // Итерируем от 1 до 1,000,000
    for ($i = 1; $i < 1000000; $i++) {
        $challenge_string = $base_string . $i;
        
        if ($using_keccak) {
            // Используем настоящий Keccak-512
            $hash = hash('keccak', $challenge_string);
        } else {
            // Fallback на SHA3-512
            $hash = hash('sha3-512', $challenge_string);
        }
        
        // Проверяем, начинается ли хеш с нужного количества нулей
        if (substr($hash, 0, $complexity) === $zero_quantity) {
            return array('pow' => $i, 'algorithm_used' => $using_keccak ? 'keccak-512' : 'sha3-512');
        }
    }
    
    return null;
}

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем JSON данные из тела запроса
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Проверяем корректность данных
    if (!$data || !isset($data['pow'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit();
    }
    
    $pow_data = $data['pow'];
    
    // Извлекаем необходимые параметры
    $version = isset($pow_data['algorithm']['version']) ? $pow_data['algorithm']['version'] : 1;
    $complexity = isset($pow_data['complexity']) ? (int)$pow_data['complexity'] : 0;
    $timestamp = isset($pow_data['timestamp']) ? (int)$pow_data['timestamp'] : 0;
    $resource = isset($pow_data['algorithm']['resourse']) ? $pow_data['algorithm']['resourse'] : '';
    $extension = isset($pow_data['algorithm']['extension']) ? $pow_data['algorithm']['extension'] : '';
    $random_string = isset($pow_data['random_string']) ? $pow_data['random_string'] : '';
    
    // Проверяем обязательные параметры
    if ($complexity <= 0 || $timestamp <= 0 || empty($random_string)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit();
    }
    
    // Вычисляем pow
    $result = hashcash_pow_keccak($version, $complexity, $timestamp, $resource, $extension, $random_string);
    
    if ($result === null) {
        http_response_code(403);
        echo json_encode(['error' => 'POW not found within range']);
        exit();
    }
    
    // Возвращаем результат
    echo json_encode($result);
    
} else {
    // Для GET запросов показываем информацию об API
    $keccak_available = in_array('keccak', hash_algos());
    
    echo json_encode([
        'name' => 'Hashcash POW Generator API (Keccak Version)',
        'version' => '1.1',
        'description' => 'API for generating Proof of Work (POW) values using Keccak-512 algorithm',
        'keccak_available' => $keccak_available,
        'algorithm_used' => $keccak_available ? 'keccak-512' : 'sha3-512 (fallback)',
        'note' => $keccak_available ? 'Using native Keccak-512' : 'Keccak-512 not available, using SHA3-512 as fallback',
        'usage' => [
            'method' => 'POST',
            'endpoint' => '/api_keccak.php',
            'content_type' => 'application/json',
            'body' => [
                'pow' => [
                    'algorithm' => [
                        'version' => 1,
                        'resourse' => 'wgni',
                        'extension' => ''
                    ],
                    'complexity' => 3,
                    'timestamp' => 1751209794,
                    'random_string' => 'bCXX2S4JiyogmA0J'
                ]
            ]
        ]
    ]);
}
?>

