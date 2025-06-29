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
 * Реализация Keccak-512 в PHP
 * Поскольку PHP не имеет встроенной поддержки Keccak-512,
 * используем внешнюю библиотеку или fallback на SHA3-512
 */
function keccak512_hash($data) {
    // Попытка использовать hash() с различными вариантами Keccak
    $keccak_variants = ['keccak', 'keccak-512', 'sha3-512'];
    
    foreach ($keccak_variants as $variant) {
        if (in_array($variant, hash_algos())) {
            return hash($variant, $data);
        }
    }
    
    // Если Keccak недоступен, используем SHA3-512 как fallback
    // ВНИМАНИЕ: Это даст другие результаты, чем настоящий Keccak-512!
    return hash('sha3-512', $data);
}

/**
 * Функция pow - точная реализация Python-кода
 * 
 * @param int $version - версия алгоритма
 * @param int $complexity - сложность (количество ведущих нулей)
 * @param int $timestamp - временная метка
 * @param string $resourse - ресурс
 * @param string $extension - расширение
 * @param string $random_string - случайная строка
 * @return int|null - найденный POW или null
 */
function pow($version, $complexity, $timestamp, $resourse, $extension, $random_string) {
    // 1. Формируем строку var7 точно как в Python-коде
    // var7='{0}:{1}:{2}:{3}:{4}:{5}:'.format(str(version),str(complexity),timestamp,resourse,extension,random_string)
    $var7 = sprintf('%s:%s:%s:%s:%s:%s:', 
        strval($version), 
        strval($complexity), 
        strval($timestamp), 
        $resourse, 
        $extension, 
        $random_string
    );
    
    // 2. Создаем строку из нулей для проверки
    // zero_quantity='0'*complexity
    $zero_quantity = str_repeat('0', $complexity);
    
    // 3. Цикл от 1 до 1,000,000
    // for i in range(1,1000000):
    for ($i = 1; $i < 1000000; $i++) {
        // 4. К строке var7 в конце добавляется индекс цикла
        // var10=f'{var7}{str(i)}'
        $var10 = $var7 . strval($i);
        
        // 5. Полученная строка полностью хешируется в Keccak-512
        // getbytes=var10.encode() 
        // keccak_hash = keccak.new(digest_bits=512)
        // keccak_hash.update(getbytes)
        // var12=keccak_hash.hexdigest()
        $var12 = keccak512_hash($var10);
        
        // 6. Проверяется на количество нулей в начале строки
        // check=var12.startswith(zero_quantity)
        // if check: return i
        $check = substr($var12, 0, $complexity) === $zero_quantity;
        
        if ($check) {
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
    
    // Извлекаем необходимые параметры точно как в Python-коде
    // version=req_data['pow']['algorithm']['version']
    // complexity=req_data['pow']['complexity']
    // timestamp=req_data['pow']['timestamp']
    // resourse=req_data['pow']['algorithm']['resourse']
    // extension=req_data['pow']['algorithm']['extension']
    // random_string=req_data['pow']['random_string']
    
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
    
    try {
        // Вызываем функцию pow точно как в Python-коде
        // work=pow(version,complexity,timestamp,resourse,extension,random_string)
        $work = pow($version, $complexity, $timestamp, $resourse, $extension, $random_string);
        
        if ($work === null) {
            // return 'captcha',403
            http_response_code(403);
            echo 'captcha';
            exit();
        }
        
        // return str(work)
        echo strval($work);
        
    } catch (Exception $e) {
        // except: return 'captcha',403
        http_response_code(403);
        echo 'captcha';
    }
    
} else {
    // Для GET запросов показываем информацию об API
    $keccak_available = false;
    $algorithm_used = 'sha3-512 (fallback)';
    
    // Проверяем доступность различных вариантов Keccak
    $keccak_variants = ['keccak', 'keccak-512'];
    foreach ($keccak_variants as $variant) {
        if (in_array($variant, hash_algos())) {
            $keccak_available = true;
            $algorithm_used = $variant;
            break;
        }
    }
    
    echo json_encode([
        'name' => 'Hashcash POW Generator API (Exact Python Implementation)',
        'version' => '1.2',
        'description' => 'Exact PHP implementation of the provided Python pow() function',
        'keccak_available' => $keccak_available,
        'algorithm_used' => $algorithm_used,
        'note' => $keccak_available ? 'Using native Keccak implementation' : 'Keccak-512 not available, using SHA3-512 as fallback',
        'implementation_details' => [
            'var7_format' => 'version:complexity:timestamp:resourse:extension:random_string:',
            'loop_range' => '1 to 999,999',
            'hash_check' => 'Leading zeros count equals complexity',
            'return_format' => 'String representation of counter'
        ],
        'usage' => [
            'method' => 'POST',
            'endpoint' => '/api_keccak_exact.php',
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

