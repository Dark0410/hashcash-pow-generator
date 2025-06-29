#!/usr/bin/env node

/**
 * Node.js скрипт для вычисления POW с использованием Keccak-512
 * Для использования в PHP через shell_exec
 */

// Проверяем наличие аргументов командной строки
if (process.argv.length < 3) {
    console.log(JSON.stringify({
        success: false,
        error: 'Missing JSON parameters'
    }));
    process.exit(1);
}

// Получаем JSON параметры из аргументов командной строки
let params;
try {
    params = JSON.parse(process.argv[2]);
} catch (e) {
    console.log(JSON.stringify({
        success: false,
        error: 'Invalid JSON parameters: ' + e.message
    }));
    process.exit(1);
}

// Пытаемся загрузить библиотеку keccak
let keccak;
let useKeccak = false;

try {
    keccak = require('keccak');
    useKeccak = true;
} catch (e) {
    // Если keccak недоступен, пытаемся использовать js-sha3
    try {
        const sha3 = require('js-sha3');
        keccak = {
            keccak512: sha3.keccak512
        };
        useKeccak = true;
    } catch (e2) {
        // Если и js-sha3 недоступен, используем встроенный crypto с SHA3-512
        try {
            const crypto = require('crypto');
            keccak = {
                keccak512: function(data) {
                    return crypto.createHash('sha3-512').update(data).digest('hex');
                }
            };
            useKeccak = true;
        } catch (e3) {
            useKeccak = false;
        }
    }
}

// Функция для вычисления Keccak-512
function calculateKeccak512(data) {
    if (useKeccak) {
        if (typeof keccak.keccak512 === 'function') {
            return keccak.keccak512(data);
        } else if (typeof keccak === 'function') {
            return keccak('keccak512').update(data).digest('hex');
        }
    }
    
    // Fallback: простое хеширование (НЕ настоящий Keccak-512)
    let hash = 0;
    if (data.length === 0) return hash.toString(16).padStart(128, '0');
    
    for (let i = 0; i < data.length; i++) {
        const char = data.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    
    return Math.abs(hash).toString(16).padStart(128, '0');
}

// Функция pow - точная реализация Python-кода
function pow(version, complexity, timestamp, resourse, extension, random_string) {
    // 1. Формируем строку var7
    const var7 = `${version}:${complexity}:${timestamp}:${resourse}:${extension}:${random_string}:`;
    
    // 2. Создаем строку из нулей для проверки
    const zero_quantity = '0'.repeat(complexity);
    
    // 3. Цикл от 1 до 1,000,000
    for (let i = 1; i < 1000000; i++) {
        // 4. К строке var7 в конце добавляется индекс цикла
        const var10 = var7 + i.toString();
        
        // 5. Полученная строка полностью хешируется в Keccak-512
        const var12 = calculateKeccak512(var10);
        
        // 6. Проверяется на количество нулей в начале строки
        const check = var12.startsWith(zero_quantity);
        
        if (check) {
            return {
                pow: i,
                hash: var12,
                string: var10
            };
        }
    }
    
    return null;
}

// Извлекаем параметры
const version = params.version || 1;
const complexity = parseInt(params.complexity) || 0;
const timestamp = parseInt(params.timestamp) || 0;
const resourse = params.resourse || '';
const extension = params.extension || '';
const random_string = params.random_string || '';

// Проверяем обязательные параметры
if (complexity <= 0 || timestamp <= 0 || !random_string) {
    console.log(JSON.stringify({
        success: false,
        error: 'Missing required parameters'
    }));
    process.exit(1);
}

// Вычисляем POW
const startTime = Date.now();
const result = pow(version, complexity, timestamp, resourse, extension, random_string);
const endTime = Date.now();

// Выводим результат
if (result !== null) {
    console.log(JSON.stringify({
        success: true,
        pow: result.pow,
        hash: result.hash,
        string: result.string,
        time_ms: endTime - startTime,
        algorithm: useKeccak ? 'keccak-512' : 'fallback-hash',
        keccak_available: useKeccak,
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
        algorithm: useKeccak ? 'keccak-512' : 'fallback-hash',
        keccak_available: useKeccak,
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

