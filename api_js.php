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
    
    // Извлекаем параметры
    $version = isset($pow_data['algorithm']['version']) ? $pow_data['algorithm']['version'] : 1;
    $complexity = isset($pow_data['complexity']) ? (int)$pow_data['complexity'] : 0;
    $timestamp = isset($pow_data['timestamp']) ? (int)$pow_data['timestamp'] : 0;
    $resourse = isset($pow_data['algorithm']['resourse']) ? $pow_data['algorithm']['resourse'] : '';
    $extension = isset($pow_data['algorithm']['extension']) ? $pow_data['algorithm']['extension'] : '';
    $random_string = isset($pow_data['random_string']) ? $pow_data['random_string'] : '';
    
    // Проверяем обязательные параметры
    if ($complexity <= 0 || $timestamp <= 0 || empty($random_string)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit();
    }
    
    // Возвращаем JavaScript код для выполнения на клиенте
    $js_code = "
    // Загружаем библиотеку sha3.js если она не загружена
    if (typeof sha3 === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/js-sha3/0.8.0/sha3.min.js';
        script.onload = function() {
            calculatePOW();
        };
        document.head.appendChild(script);
    } else {
        calculatePOW();
    }
    
    function calculatePOW() {
        // Параметры
        const version = " . json_encode($version) . ";
        const complexity = " . json_encode($complexity) . ";
        const timestamp = " . json_encode($timestamp) . ";
        const resourse = " . json_encode($resourse) . ";
        const extension = " . json_encode($extension) . ";
        const random_string = " . json_encode($random_string) . ";
        
        // Функция pow - точная реализация Python-кода
        function pow(version, complexity, timestamp, resourse, extension, random_string) {
            const var7 = `\${version}:\${complexity}:\${timestamp}:\${resourse}:\${extension}:\${random_string}:`;
            const zero_quantity = '0'.repeat(complexity);
            
            for (let i = 1; i < 1000000; i++) {
                const var10 = var7 + i.toString();
                const var12 = sha3.keccak512(var10);
                const check = var12.startsWith(zero_quantity);
                
                if (check) {
                    return i;
                }
            }
            
            return null;
        }
        
        // Вычисляем POW
        const result = pow(version, complexity, timestamp, resourse, extension, random_string);
        
        // Отправляем результат обратно на сервер или выводим
        if (result !== null) {
            console.log('POW found:', result);
            // Здесь можно отправить результат обратно на сервер
            return result;
        } else {
            console.log('POW not found');
            return null;
        }
    }
    ";
    
    echo json_encode([
        'status' => 'javascript_required',
        'message' => 'Execute the provided JavaScript code to calculate POW using Keccak-512',
        'javascript' => $js_code,
        'parameters' => [
            'version' => $version,
            'complexity' => $complexity,
            'timestamp' => $timestamp,
            'resourse' => $resourse,
            'extension' => $extension,
            'random_string' => $random_string
        ],
        'instructions' => [
            '1. Execute the JavaScript code in a browser environment',
            '2. The code will load sha3.js library and calculate POW',
            '3. Check browser console for the result',
            '4. For automated usage, parse the JavaScript and execute in Node.js'
        ]
    ]);
    
} else {
    // Для GET запросов показываем информацию об API
    echo json_encode([
        'name' => 'Hashcash POW Generator API (JavaScript Keccak-512)',
        'version' => '1.4',
        'description' => 'API that provides JavaScript code for calculating POW using true Keccak-512',
        'algorithm' => 'keccak-512',
        'compatibility' => '100% compatible with Lesta.ru',
        'usage' => [
            'method' => 'POST',
            'endpoint' => '/api_js.php',
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
        ],
        'response' => [
            'status' => 'javascript_required',
            'javascript' => 'JavaScript code to execute',
            'parameters' => 'Extracted parameters',
            'instructions' => 'How to use the JavaScript code'
        ],
        'notes' => [
            'This API returns JavaScript code that must be executed in a browser or Node.js',
            'The JavaScript uses sha3.js library for true Keccak-512 implementation',
            'Results will be 100% compatible with Lesta.ru',
            'For server-side usage, consider using Node.js with keccak library'
        ]
    ]);
}
?>

