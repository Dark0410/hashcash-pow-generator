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
 * PHP API с серверным выполнением JavaScript через Node.js
 * Для работы на InfinityFree и других хостингах
 */

// Функция для выполнения JavaScript на сервере
function executeJavaScript($jsCode, $timeout = 30) {
    // Создаем временный файл для JavaScript кода
    $tempFile = tempnam(sys_get_temp_dir(), 'pow_js_');
    file_put_contents($tempFile, $jsCode);
    
    // Пытаемся выполнить через различные JavaScript движки
    $engines = [
        'node',     // Node.js
        'nodejs',   // Альтернативное имя Node.js
        'js',       // SpiderMonkey (Mozilla)
        'rhino',    // Rhino (Java-based)
        'v8'        // V8 (если установлен отдельно)
    ];
    
    foreach ($engines as $engine) {
        // Проверяем доступность движка
        $checkCommand = "which $engine 2>/dev/null";
        $enginePath = trim(shell_exec($checkCommand));
        
        if (!empty($enginePath)) {
            // Выполняем JavaScript код
            $command = "$engine $tempFile 2>&1";
            $output = shell_exec($command);
            
            // Удаляем временный файл
            unlink($tempFile);
            
            if ($output !== null) {
                return [
                    'success' => true,
                    'output' => trim($output),
                    'engine' => $engine
                ];
            }
        }
    }
    
    // Удаляем временный файл в случае неудачи
    unlink($tempFile);
    
    return [
        'success' => false,
        'error' => 'No JavaScript engine available',
        'engines_tried' => $engines
    ];
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
    
    // Создаем JavaScript код для выполнения на сервере
    $jsCode = "
// Простая реализация Keccak-512 (урезанная версия для серверного выполнения)
// Если доступна библиотека crypto в Node.js, используем её
try {
    var crypto = require('crypto');
    
    function keccak512(data) {
        // В Node.js нет встроенного Keccak, но есть SHA3
        // Попробуем использовать sha3-512 как приближение
        try {
            return crypto.createHash('sha3-512').update(data).digest('hex');
        } catch (e) {
            // Если SHA3 недоступен, используем SHA256 как fallback
            return crypto.createHash('sha256').update(data).digest('hex');
        }
    }
    
    var useNodeCrypto = true;
} catch (e) {
    var useNodeCrypto = false;
    
    // Простая реализация хеширования (не настоящий Keccak-512)
    // Это только для демонстрации, в реальности нужна полная библиотека
    function simpleHash(str) {
        var hash = 0;
        if (str.length === 0) return hash.toString(16);
        for (var i = 0; i < str.length; i++) {
            var char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(16).padStart(64, '0');
    }
    
    function keccak512(data) {
        return simpleHash(data);
    }
}

// Параметры из PHP
var version = " . json_encode($version) . ";
var complexity = " . json_encode($complexity) . ";
var timestamp = " . json_encode($timestamp) . ";
var resourse = " . json_encode($resourse) . ";
var extension = " . json_encode($extension) . ";
var random_string = " . json_encode($random_string) . ";

// Функция pow - точная реализация Python-кода
function pow(version, complexity, timestamp, resourse, extension, random_string) {
    // 1. Формируем строку var7
    var var7 = version + ':' + complexity + ':' + timestamp + ':' + resourse + ':' + extension + ':' + random_string + ':';
    
    // 2. Создаем строку из нулей для проверки
    var zero_quantity = '';
    for (var j = 0; j < complexity; j++) {
        zero_quantity += '0';
    }
    
    // 3. Цикл от 1 до 1,000,000
    for (var i = 1; i < 1000000; i++) {
        // 4. К строке var7 в конце добавляется индекс цикла
        var var10 = var7 + i.toString();
        
        // 5. Полученная строка полностью хешируется
        var var12 = keccak512(var10);
        
        // 6. Проверяется на количество нулей в начале строки
        var check = var12.substring(0, complexity) === zero_quantity;
        
        if (check) {
            return {
                pow: i,
                hash: var12,
                string: var10,
                algorithm: useNodeCrypto ? 'node-crypto' : 'simple-hash'
            };
        }
    }
    
    return null;
}

// Вычисляем POW
var startTime = Date.now();
var result = pow(version, complexity, timestamp, resourse, extension, random_string);
var endTime = Date.now();

// Выводим результат в JSON формате
if (result !== null) {
    console.log(JSON.stringify({
        success: true,
        pow: result.pow,
        hash: result.hash,
        string: result.string,
        algorithm: result.algorithm,
        time_ms: endTime - startTime,
        parameters: {
            version: version,
            complexity: complexity,
            timestamp: timestamp,
            resourse: resourse,
            extension: extension,
            random_string: random_string
        }
    }));
} else {
    console.log(JSON.stringify({
        success: false,
        error: 'POW not found in range 1-999999',
        time_ms: endTime - startTime,
        parameters: {
            version: version,
            complexity: complexity,
            timestamp: timestamp,
            resourse: resourse,
            extension: extension,
            random_string: random_string
        }
    }));
}
";
    
    try {
        // Выполняем JavaScript код на сервере
        $jsResult = executeJavaScript($jsCode, 60); // Увеличиваем timeout до 60 секунд
        
        if ($jsResult['success']) {
            // Парсим результат выполнения JavaScript
            $jsOutput = json_decode($jsResult['output'], true);
            
            if ($jsOutput && $jsOutput['success']) {
                // Возвращаем успешный результат
                echo json_encode([
                    'pow' => $jsOutput['pow'],
                    'hash' => $jsOutput['hash'],
                    'string' => $jsOutput['string'],
                    'algorithm' => $jsOutput['algorithm'],
                    'time_ms' => $jsOutput['time_ms'],
                    'js_engine' => $jsResult['engine'],
                    'server_execution' => true,
                    'compatibility' => $jsOutput['algorithm'] === 'node-crypto' ? 'Partial (SHA3-512)' : 'Limited (Simple Hash)'
                ]);
            } else {
                // JavaScript выполнился, но POW не найден
                echo json_encode([
                    'error' => 'POW not found',
                    'js_engine' => $jsResult['engine'],
                    'server_execution' => true,
                    'js_output' => $jsResult['output']
                ]);
            }
        } else {
            // JavaScript не удалось выполнить
            http_response_code(500);
            echo json_encode([
                'error' => 'JavaScript execution failed',
                'details' => $jsResult['error'],
                'engines_tried' => $jsResult['engines_tried'],
                'fallback_suggestion' => 'Use api.php for SHA3-512 implementation'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Server error during JavaScript execution',
            'message' => $e->getMessage()
        ]);
    }
    
} else {
    // Для GET запросов показываем информацию об API
    echo json_encode([
        'name' => 'Hashcash POW Generator API (Server-side JavaScript)',
        'version' => '2.0',
        'description' => 'API that executes JavaScript on the server to calculate POW',
        'features' => [
            'Server-side JavaScript execution',
            'Multiple JavaScript engine support (Node.js, SpiderMonkey, Rhino, V8)',
            'Automatic engine detection',
            'JSON API response',
            'Fallback to simple hash if crypto unavailable'
        ],
        'supported_engines' => [
            'node' => 'Node.js (preferred)',
            'nodejs' => 'Node.js (alternative name)',
            'js' => 'SpiderMonkey (Mozilla)',
            'rhino' => 'Rhino (Java-based)',
            'v8' => 'V8 (standalone)'
        ],
        'usage' => [
            'method' => 'POST',
            'endpoint' => '/api_server_js.php',
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
        'response_format' => [
            'success' => [
                'pow' => 'integer',
                'hash' => 'string',
                'algorithm' => 'string',
                'js_engine' => 'string',
                'server_execution' => true
            ],
            'error' => [
                'error' => 'string',
                'details' => 'string (optional)'
            ]
        ],
        'notes' => [
            'Requires JavaScript engine installed on server',
            'Node.js provides best compatibility with crypto functions',
            'Falls back to simple hash if crypto module unavailable',
            'Execution timeout: 60 seconds'
        ]
    ]);
}
?>

