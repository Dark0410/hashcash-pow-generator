const express = require('express');
const cors = require('cors');
const { execSync } = require('child_process');
const path = require('path');
const app = express();
const PORT = process.env.PORT || 3000;

// Настройка CORS для разрешения запросов с любых доменов
app.use(cors());

// Парсинг JSON в запросах
app.use(express.json());

// Обслуживание статических файлов из папки public
app.use(express.static(path.join(__dirname, 'public')));

// Проверка доступности Keccak
function checkKeccak() {
  try {
    require('keccak');
    return true;
  } catch {
    try {
      require('js-sha3');
      return true;
    } catch {
      return false;
    }
  }
}

// API для генерации POW
app.post('/api', (req, res) => {
  try {
    // Проверяем наличие обязательных данных
    if (!req.body || !req.body.pow) {
      return res.status(400).json({
        error: 'Invalid request format',
        message: 'Missing "pow" object in request body'
      });
    }

    // Извлекаем параметры
    const powData = req.body.pow;
    const version = powData.algorithm?.version || 1;
    const complexity = parseInt(powData.complexity) || 0;
    const timestamp = parseInt(powData.timestamp) || 0;
    const resource = powData.algorithm?.resourse || '';
    const extension = powData.algorithm?.extension || '';
    const randomString = powData.random_string || '';

    // Проверяем обязательные параметры
    if (complexity <= 0 || timestamp <= 0 || !randomString) {
      return res.status(400).json({
        error: 'Missing required parameters',
        required: ['complexity > 0', 'timestamp > 0', 'random_string']
      });
    }

    // Формируем параметры для дочернего процесса
    const params = JSON.stringify({
      version,
      complexity,
      timestamp,
      resourse: resource,
      extension,
      random_string: randomString
    });

    // Выполняем вычисление POW
    const result = execSync(`node pow_calculator.js '${params}'`, {
      timeout: 60000 // 60 секунд таймаут
    });

    // Возвращаем результат
    res.json(JSON.parse(result));
    
  } catch (error) {
    console.error('API Error:', error);
    res.status(500).json({
      error: 'POW computation failed',
      details: error.message,
      stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
    });
  }
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'OK',
    time: new Date().toISOString(),
    keccakAvailable: checkKeccak(),
    memoryUsage: process.memoryUsage()
  });
});

// Тестовый endpoint для проверки фронтенда
app.get('/api-test', (req, res) => {
  res.json({
    api: 'working',
    message: 'API endpoint is accessible',
    timestamp: Date.now()
  });
});

// Обработка 404 ошибок
app.use((req, res) => {
  res.status(404).json({
    error: 'Not found',
    path: req.path,
    method: req.method
  });
});

// Запуск сервера
app.listen(PORT, () => {
  console.log(`🚀 Server running on port ${PORT}`);
  console.log(`📡 Access the server: http://localhost:${PORT}`);
  console.log(`🔐 Keccak available: ${checkKeccak() ? 'YES' : 'NO'}`);
  console.log(`📊 Health check: http://localhost:${PORT}/health`);
  console.log(`🧪 API test: http://localhost:${PORT}/api-test`);
});
