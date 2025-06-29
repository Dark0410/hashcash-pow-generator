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
 * PHP API с серверным выполнением Node.js для 100% совместимости с Lesta.ru
 * Использует отдельный Node.js скрипт для вычисления POW с Keccak-512
 */

// Функция для выполнения Node.js скрипта
function executeNodeScript($params, $timeout = 60) {
    // Путь к Node.js скрипту (относительно текущего файла)
    $scriptPath = __DIR__ . '/pow_calculator.js';
    
    // Проверяем существование скрипта
    if (!file_exists($scriptPath)) {
        return [
            'success' => false,
            'error' => 'Node.js script not found: ' . $scriptPath
        ];
    }
    
    // Кодируем параметры в JSON
    $jsonParams = json_encode($params);
    $escapedParams = escapeshellarg($jsonParams);
    
    // Пытаемся выполнить через различные команды Node.js
    $nodeCommands = [
        'node',
        'nodejs',
        '/usr/bin/node',
        '/usr/local/bin/node'
    ];
    
    foreach ($nodeCommands as $nodeCmd) {
        // Проверяем доступность команды
        $checkCmd = "which $nodeCmd 2>/dev/null";
        $nodePath = trim(shell_exec($checkCmd));
        
        if (!empty($nodePath) || $nodeCmd === 'node' || $nodeCmd === 'nodejs') {
            // Формируем команду для выполнения
            $command = "$nodeCmd $scriptPath $escapedParams 2>&1";
            
            // Выполняем команду с timeout
            $output = shell_exec($command);
            
            if ($output !== null && !empty(trim($output))) {
                // Пытаемся распарсить JSON ответ
                $result = json_decode(trim($output), true);
                
                if ($result !== null) {
                    return [
                        'success' => true,
                        'result' => $result,
                        'node_command' => $nodeCmd,
                        'raw_output' => trim($output)
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Invalid JSON response from Node.js',
                        'raw_output' => trim($output),
                        'node_command' => $nodeCmd
                    ];
                }
            }
        }
    }
    
    return [
        'success' => false,
        'error' => 'Node.js not available on server',
        'commands_tried' => $nodeCommands
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
    
    // Подготавливаем параметры для Node.js скрипта
    $nodeParams = [
        'version' => $version,
        'complexity' => $complexity,
        'timestamp' => $timestamp,
        'resourse' => $resourse,
        'extension' => $extension,
        'random_string' => $random_string
    ];
    
    try {
        // Выполняем Node.js скрипт
        $nodeResult = executeNodeScript($nodeParams, 90); // Увеличиваем timeout до 90 секунд
        
        if ($nodeResult['success']) {
            $jsResult = $nodeResult['result'];
            
            if ($jsResult['success']) {
                // Возвращаем успешный результат
                echo json_encode([
                    'pow' => $jsResult['pow'],
                    'hash' => $jsResult['hash'],
                    'string' => $jsResult['string'],
                    'time_ms' => $jsResult['time_ms'],
                    'algorithm' => $jsResult['algorithm'],
                    'keccak_available' => $jsResult['keccak_available'],
                    'node_command' => $nodeResult['node_command'],
                    'server_execution' => true,
                    'compatibility' => $jsResult['keccak_available'] ? '100% (Keccak-512)' : 'Limited (Fallback Hash)',
                    'lesta_compatible' => $jsResult['keccak_available']
                ]);
            } else {
                // Node.js выполнился, но POW не найден
                http_response_code(404);
                echo json_encode([
                    'error' => $jsResult['error'],
                    'time_ms' => $jsResult['time_ms'],
                    'algorithm' => $jsResult['algorithm'],
                    'keccak_available' => $jsResult['keccak_available'],
                    'node_command' => $nodeResult['node_command'],
                    'server_execution' => true
                ]);
            }
        } else {
            // Node.js не удалось выполнить
            http_response_code(500);
            echo json_encode([
                'error' => 'Node.js execution failed',
                'details' => $nodeResult['error'],
                'commands_tried' => isset($nodeResult['commands_tried']) ? $nodeResult['commands_tried'] : [],
                'raw_output' => isset($nodeResult['raw_output']) ? $nodeResult['raw_output'] : null,
                'fallback_suggestion' => 'Install Node.js on server or use api.php for SHA3-512 implementation'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Server error during Node.js execution',
            'message' => $e->getMessage()
        ]);
    }
    
} else {
    // Для GET запросов показываем информацию об API
    
    // Проверяем доступность Node.js
    $nodeAvailable = false;
    $nodeVersion = '';
    $nodeCommands = ['node', 'nodejs'];
    
    foreach ($nodeCommands as $cmd) {
        $version = trim(shell_exec("$cmd --version 2>/dev/null"));
        if (!empty($version)) {
            $nodeAvailable = true;
            $nodeVersion = $version;
            break;
        }
    }
    
    echo json_encode([
        'name' => 'Hashcash POW Generator API (Node.js Backend)',
        'version' => '2.1',
        'description' => 'PHP API with Node.js backend for 100% Lesta.ru compatibility',
        'features' => [
            'Server-side Node.js execution',
            'True Keccak-512 implementation (with keccak or js-sha3 library)',
            'Fallback to SHA3-512 if Keccak unavailable',
            'JSON API response',
            '100% compatibility with Lesta.ru (when Keccak available)'
        ],
        'node_status' => [
            'available' => $nodeAvailable,
            'version' => $nodeVersion,
            'script_path' => __DIR__ . '/pow_calculator.js'
        ],
        'requirements' => [
            'Node.js installed on server',
            'Optional: npm install keccak (for true Keccak-512)',
            'Optional: npm install js-sha3 (alternative Keccak implementation)',
            'Fallback: Built-in crypto module (SHA3-512)'
        ],
        'installation' => [
            '1. Upload api_node.php and pow_calculator.js to server',
            '2. Make pow_calculator.js executable: chmod +x pow_calculator.js',
            '3. Install Node.js dependencies (optional): npm install keccak js-sha3',
            '4. Test API endpoint'
        ],
        'usage' => [
            'method' => 'POST',
            'endpoint' => '/api_node.php',
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
                'pow' => 'integer (the calculated POW value)',
                'hash' => 'string (the Keccak-512 hash)',
                'algorithm' => 'string (keccak-512 or fallback)',
                'keccak_available' => 'boolean',
                'lesta_compatible' => 'boolean',
                'server_execution' => true
            ],
            'error' => [
                'error' => 'string',
                'details' => 'string (optional)'
            ]
        ],
        'compatibility' => [
            'lesta_ru' => $nodeAvailable ? 'Depends on Keccak library availability' : 'Requires Node.js installation',
            'infinityfree' => 'Depends on Node.js support on hosting'
        ]
    ]);
}
?>

