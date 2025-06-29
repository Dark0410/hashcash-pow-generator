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

/**
 * Гибридная реализация POW с использованием JavaScript для Keccak-512
 * Если JavaScript недоступен, используется fallback на PHP SHA3-512
 */

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
    
    // Проверяем, есть ли параметр use_js для принудительного использования JavaScript
    $use_js = isset($data['use_js']) ? $data['use_js'] : true;
    
    if ($use_js) {
        // Возвращаем HTML страницу с JavaScript для вычисления POW
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>POW Calculator</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-sha3/0.8.0/sha3.min.js"></script>
</head>
<body>
    <div id="result">Calculating POW...</div>
    <div id="debug"></div>
    
    <script>
        // Параметры из PHP
        const version = <?php echo json_encode($version); ?>;
        const complexity = <?php echo json_encode($complexity); ?>;
        const timestamp = <?php echo json_encode($timestamp); ?>;
        const resourse = <?php echo json_encode($resourse); ?>;
        const extension = <?php echo json_encode($extension); ?>;
        const random_string = <?php echo json_encode($random_string); ?>;
        
        // Функция pow - точная реализация Python-кода с использованием Keccak-512
        function pow(version, complexity, timestamp, resourse, extension, random_string) {
            // 1. Формируем строку var7
            const var7 = `${version}:${complexity}:${timestamp}:${resourse}:${extension}:${random_string}:`;
            
            // 2. Создаем строку из нулей для проверки
            const zero_quantity = '0'.repeat(complexity);
            
            document.getElementById('debug').innerHTML = `
                <p><strong>Debug Info:</strong></p>
                <p>var7: ${var7}</p>
                <p>zero_quantity: ${zero_quantity}</p>
                <p>Searching for POW...</p>
            `;
            
            // 3. Цикл от 1 до 1,000,000
            for (let i = 1; i < 1000000; i++) {
                // 4. К строке var7 в конце добавляется индекс цикла
                const var10 = var7 + i.toString();
                
                // 5. Полученная строка полностью хешируется в Keccak-512
                const var12 = sha3.keccak512(var10);
                
                // 6. Проверяется на количество нулей в начале строки
                const check = var12.startsWith(zero_quantity);
                
                if (check) {
                    return i;
                }
                
                // Показываем прогресс каждые 10000 итераций
                if (i % 10000 === 0) {
                    document.getElementById('result').innerHTML = `Calculating... ${i}/1000000`;
                }
            }
            
            return null;
        }
        
        // Вычисляем POW
        const startTime = Date.now();
        const result = pow(version, complexity, timestamp, resourse, extension, random_string);
        const endTime = Date.now();
        
        if (result !== null) {
            document.getElementById('result').innerHTML = `
                <h2>POW Found: ${result}</h2>
                <p>Time taken: ${endTime - startTime}ms</p>
                <p><strong>Result for API:</strong> ${result}</p>
            `;
            
            // Если это AJAX запрос, возвращаем JSON
            if (window.parent && window.parent.powCallback) {
                window.parent.powCallback(result);
            }
        } else {
            document.getElementById('result').innerHTML = `
                <h2>POW Not Found</h2>
                <p>No valid POW found in range 1-999999</p>
                <p>Time taken: ${endTime - startTime}ms</p>
            `;
            
            if (window.parent && window.parent.powCallback) {
                window.parent.powCallback(null);
            }
        }
    </script>
</body>
</html>
        <?php
        exit();
    } else {
        // Fallback на PHP реализацию с SHA3-512
        function pow_fallback($version, $complexity, $timestamp, $resourse, $extension, $random_string) {
            $var7 = sprintf('%s:%s:%s:%s:%s:%s:', 
                strval($version), 
                strval($complexity), 
                strval($timestamp), 
                $resourse, 
                $extension, 
                $random_string
            );
            
            $zero_quantity = str_repeat('0', $complexity);
            
            for ($i = 1; $i < 1000000; $i++) {
                $var10 = $var7 . strval($i);
                $var12 = hash('sha3-512', $var10);
                $check = substr($var12, 0, $complexity) === $zero_quantity;
                
                if ($check) {
                    return $i;
                }
            }
            
            return null;
        }
        
        try {
            $work = pow_fallback($version, $complexity, $timestamp, $resourse, $extension, $random_string);
            
            if ($work === null) {
                http_response_code(403);
                echo 'captcha';
                exit();
            }
            
            echo json_encode([
                'pow' => $work,
                'algorithm_used' => 'sha3-512',
                'note' => 'Fallback implementation, may differ from Lesta.ru'
            ]);
            
        } catch (Exception $e) {
            http_response_code(403);
            echo 'captcha';
        }
    }
    
} else {
    // Для GET запросов показываем информацию об API
    echo json_encode([
        'name' => 'Hashcash POW Generator API (Hybrid PHP+JS)',
        'version' => '1.3',
        'description' => 'Hybrid implementation using JavaScript Keccak-512 with PHP fallback',
        'features' => [
            'JavaScript Keccak-512 implementation (100% compatible with Lesta.ru)',
            'PHP SHA3-512 fallback',
            'Real-time progress indication',
            'Debug information'
        ],
        'usage' => [
            'method' => 'POST',
            'endpoint' => '/api_hybrid.php',
            'content_type' => 'application/json',
            'parameters' => [
                'use_js' => 'boolean (optional, default: true) - Use JavaScript implementation',
            ],
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
                ],
                'use_js' => true
            ]
        ],
        'notes' => [
            'JavaScript version uses sha3.js library for true Keccak-512',
            'PHP fallback uses SHA3-512 (may produce different results)',
            'For API usage, parse the HTML response to extract the POW value'
        ]
    ]);
}
?>

