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

function hashcash_pow($version, $complexity, $timestamp, $resource, $extension, $random_string) {
    // Формируем базовую строку для хеширования
    $base_string = "$version:$complexity:$timestamp:$resource:$extension:$random_string:";
    
    // Создаем строку из нулей для проверки
    $zero_quantity = str_repeat("0", $complexity);
    
    // Итерируем от 1 до 1,000,000
    for ($i = 1; $i < 1000000; $i++) {
        $challenge_string = $base_string . $i;
        
        // ВАЖНО: Lesta.ru использует Keccak-512, а не SHA3-512!
        // В PHP нет встроенной поддержки Keccak-512, поэтому используем внешнюю библиотеку
        // Для совместимости с InfinityFree используем SHA3-512 с предупреждением
        $hash = hash('sha3-512', $challenge_string);
        
        // ПРИМЕЧАНИЕ: Этот код использует SHA3-512 вместо Keccak-512
        // Для точного соответствия Lesta.ru нужна библиотека Keccak
        // Результаты будут отличаться от оригинального Lesta.ru
        
        // Проверяем, начинается ли хеш с нужного количества нулей
        if (substr($hash, 0, $complexity) === $zero_quantity) {
            return $i;
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
    $result = hashcash_pow($version, $complexity, $timestamp, $resource, $extension, $random_string);
    
    if ($result === null) {
        http_response_code(403);
        echo json_encode(['error' => 'POW not found within range']);
        exit();
    }
    
    // Возвращаем результат
    echo json_encode(['pow' => $result]);
    
} else {
    // Для GET запросов показываем информацию об API
    echo json_encode([
        'name' => 'Hashcash POW Generator API',
        'version' => '1.0',
        'description' => 'API for generating Proof of Work (POW) values using Hashcash algorithm',
        'usage' => [
            'method' => 'POST',
            'endpoint' => '/api.php',
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

