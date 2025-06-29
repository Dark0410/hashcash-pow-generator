# Мануал по установке Hashcash POW Generator на InfinityFree

## Введение

Данный мануал предназначен для установки и настройки проекта Hashcash POW Generator на бесплатном хостинге InfinityFree. В нем учтены особенности и ограничения бесплатного хостинга.

## Ограничения InfinityFree и рекомендации

### Что НЕ поддерживает InfinityFree:
- ❌ Node.js и выполнение JavaScript на сервере
- ❌ Установка PHP-расширений (например, `ext-keccak`)
- ❌ Выполнение внешних команд через `shell_exec()` (может быть отключено)
- ❌ Python и установка библиотек через pip
- ❌ Доступ к командной строке сервера

### Что поддерживает InfinityFree:
- ✅ PHP 7.4+ со стандартными расширениями
- ✅ Стандартные функции хеширования PHP (`hash()`)
- ✅ MySQL базы данных
- ✅ Статические файлы (HTML, CSS, JS)
- ✅ .htaccess файлы

## Рекомендуемые варианты для InfinityFree

### Вариант 1: PHP API с SHA3-512 (Рекомендуется для InfinityFree)

**Файл:** `api.php`

**Особенности:**
- ✅ Гарантированно работает на InfinityFree
- ✅ Использует стандартные функции PHP
- ✅ Быстрая работа
- ❌ Результат может отличаться от Lesta.ru (использует SHA3-512 вместо Keccak-512)

**Установка:**
1. Загрузите файл `api.php` в корневую папку вашего сайта
2. Убедитесь, что файл `.htaccess` также загружен
3. API будет доступен по адресу: `https://ваш-домен.infinityfreeapp.com/api.php`

### Вариант 2: Гибридный PHP+JavaScript (Для веб-интерфейса)

**Файлы:** `api_hybrid.php`, `keccak_calculator.html`

**Особенности:**
- ✅ 100% совместимость с Lesta.ru (использует JavaScript Keccak-512)
- ✅ Работает в браузере пользователя
- ❌ Не подходит для прямых API-запросов от стороннего софта
- ✅ Отлично для веб-интерфейса

**Установка:**
1. Загрузите `api_hybrid.php` и `keccak_calculator.html`
2. Откройте `keccak_calculator.html` в браузере для использования

### Вариант 3: Попытка использования Node.js (Может не работать)

**Файлы:** `api_node.php`, `pow_calculator.js`, `package.json`

**Особенности:**
- ✅ 100% совместимость с Lesta.ru (если работает)
- ❌ Скорее всего НЕ будет работать на InfinityFree
- ❌ Требует Node.js на сервере

**Проверка возможности:**
1. Загрузите все файлы
2. Попробуйте обратиться к `api_node.php`
3. Если получите ошибку "Node.js not available", используйте Вариант 1

## Пошаговая инструкция по установке

### Шаг 1: Подготовка файлов

Из архива проекта вам понадобятся следующие файлы для InfinityFree:

**Обязательные файлы:**
- `api.php` - основной API (SHA3-512)
- `index.html` - веб-интерфейс
- `.htaccess` - настройки сервера

**Дополнительные файлы (по желанию):**
- `keccak_calculator.html` - калькулятор с Keccak-512
- `api_hybrid.php` - гибридная версия
- `api_keccak.php` - попытка использования Keccak-512

### Шаг 2: Загрузка на хостинг

1. **Войдите в панель управления InfinityFree**
2. **Откройте File Manager или используйте FTP-клиент**
3. **Перейдите в папку `htdocs` (или `public_html`)**
4. **Загрузите файлы проекта**

### Шаг 3: Настройка .htaccess

Убедитесь, что файл `.htaccess` содержит правильные настройки:

```apache
# Настройки для хостинга InfinityFree
RewriteEngine On

# Разрешаем CORS для API запросов
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>

# Обработка preflight запросов
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Настройки безопасности
<Files "*.php">
    Order allow,deny
    Allow from all
</Files>

# Кэширование статических файлов
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
</IfModule>
```

### Шаг 4: Тестирование установки

1. **Откройте ваш сайт:** `https://ваш-домен.infinityfreeapp.com/`
2. **Проверьте веб-интерфейс:** должна открыться главная страница
3. **Протестируйте API:**

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"pow":{"algorithm":{"version":1,"resourse":"wgni","extension":""},"complexity":3,"timestamp":1751209794,"random_string":"bCXX2S4JiyogmA0J"}}' \
  https://ваш-домен.infinityfreeapp.com/api.php
```

### Шаг 5: Проверка работоспособности

**Ожидаемый ответ от API:**
```json
{
  "pow": 203,
  "hash": "000...",
  "algorithm": "sha3-512",
  "time_ms": 150,
  "server_execution": true
}
```

## Использование API на InfinityFree

### Endpoint: `/api.php`

**Метод:** POST  
**Content-Type:** application/json

**Пример запроса:**
```json
{
  "pow": {
    "algorithm": {
      "version": 1,
      "resourse": "wgni",
      "extension": ""
    },
    "complexity": 3,
    "timestamp": 1751209794,
    "random_string": "bCXX2S4JiyogmA0J"
  }
}
```

**Пример ответа:**
```json
{
  "pow": 203,
  "hash": "000a1b2c3d4e5f...",
  "string": "1:3:1751209794:wgni::bCXX2S4JiyogmA0J:203",
  "time_ms": 150,
  "algorithm": "sha3-512",
  "server_execution": true,
  "compatibility": "May differ from Lesta.ru (SHA3-512 vs Keccak-512)"
}
```

## Интеграция со сторонним софтом

### Пример на Python:

```python
import requests
import json

url = "https://ваш-домен.infinityfreeapp.com/api.php"

data = {
    "pow": {
        "algorithm": {
            "version": 1,
            "resourse": "wgni",
            "extension": ""
        },
        "complexity": 3,
        "timestamp": 1751209794,
        "random_string": "bCXX2S4JiyogmA0J"
    }
}

response = requests.post(url, json=data)
result = response.json()

print(f"POW: {result['pow']}")
```

### Пример на JavaScript:

```javascript
const url = "https://ваш-домен.infinityfreeapp.com/api.php";

const data = {
    pow: {
        algorithm: {
            version: 1,
            resourse: "wgni",
            extension: ""
        },
        complexity: 3,
        timestamp: 1751209794,
        random_string: "bCXX2S4JiyogmA0J"
    }
};

fetch(url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => {
    console.log('POW:', result.pow);
});
```

## Производительность и ограничения

### Ожидаемая производительность на InfinityFree:
- **Complexity 1:** ~1-10ms
- **Complexity 2:** ~10-50ms  
- **Complexity 3:** ~50-200ms
- **Complexity 4:** ~200-1000ms
- **Complexity 5+:** может превышать лимиты времени выполнения

### Ограничения InfinityFree:
- **Время выполнения скрипта:** обычно 30-60 секунд
- **Память:** ограничена
- **CPU:** ограничения на интенсивные вычисления
- **Количество запросов:** может быть ограничено

## Устранение проблем

### Проблема: API возвращает ошибку 500
**Решение:**
1. Проверьте логи ошибок в панели управления InfinityFree
2. Убедитесь, что все файлы загружены корректно
3. Проверьте права доступа к файлам (должны быть 644)

### Проблема: CORS ошибки
**Решение:**
1. Убедитесь, что файл `.htaccess` загружен
2. Проверьте настройки CORS в `.htaccess`

### Проблема: Медленная работа
**Решение:**
1. Уменьшите complexity в тестовых запросах
2. Рассмотрите переход на платный хостинг для высоких нагрузок

### Проблема: Результат отличается от Lesta.ru
**Объяснение:**
Это нормально для InfinityFree, так как используется SHA3-512 вместо Keccak-512. Для 100% совместимости требуется хостинг с поддержкой Node.js или специальных PHP-расширений.

## Альтернативы для 100% совместимости

Если вам критически важна 100% совместимость с Lesta.ru, рассмотрите:

1. **Платный хостинг с Node.js** (например, DigitalOcean, Heroku)
2. **VPS с возможностью установки Node.js**
3. **Специализированный PHP-хостинг с поддержкой расширений**

## Заключение

Данный проект успешно работает на InfinityFree с использованием PHP API (`api.php`). Хотя результаты могут отличаться от Lesta.ru из-за использования SHA3-512 вместо Keccak-512, алгоритм работает корректно и подходит для большинства задач.

Для критически важных приложений, требующих 100% совместимости, рекомендуется использовать платный хостинг с поддержкой Node.js.

