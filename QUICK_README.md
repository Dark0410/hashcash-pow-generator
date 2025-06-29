# 🔐 Hashcash POW Generator

Веб-проект для генерации параметра `pow` (Proof of Work) на основе алгоритма Hashcash, совместимый с системой аутентификации Lesta.ru.

## 🚀 Возможности

- **REST API** для автоматической генерации POW
- **Веб-интерфейс** для тестирования и отладки
- **Высокая производительность** - оптимизирован для множественных запросов
- **Готов к деплою** на InfinityFree хостинг

## 📁 Структура проекта

```
pow_project/
├── index.html          # Веб-интерфейс
├── api.php            # REST API endpoint
├── .htaccess          # Конфигурация Apache
└── README.md          # Полная документация
```

## 🛠 Быстрый старт

### Локальное тестирование
```bash
# Запуск PHP сервера
php -S localhost:8080

# Тестирование API
curl -X POST -H "Content-Type: application/json" \
  -d '{"pow":{"algorithm":{"version":1,"resourse":"wgni","extension":""},"complexity":3,"timestamp":1751209794,"random_string":"test123"}}' \
  http://localhost:8080/api.php
```

### Деплой на InfinityFree
1. Зарегистрируйтесь на [InfinityFree](https://infinityfree.net/)
2. Создайте новый сайт
3. Загрузите все файлы в папку `htdocs`
4. Готово! API доступен по адресу `https://your-domain.infinityfreeapp.com/api.php`

## 📖 API Документация

### Endpoint
```
POST /api.php
Content-Type: application/json
```

### Запрос
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

### Ответ
```json
{
  "pow": 291
}
```

## 🔧 Технические требования

- **PHP**: 7.4+
- **Расширения**: hash, json
- **Веб-сервер**: Apache с mod_rewrite

## 📊 Алгоритм

Проект реализует алгоритм Hashcash v1:
1. Формирует строку: `version:complexity:timestamp:resource:extension:random_string:counter`
2. Вычисляет **Keccak-512** хеш (для 100% совместимости с Lesta.ru)
3. Проверяет количество ведущих нулей согласно `complexity`
4. Возвращает найденное значение `counter`

**Важное замечание о расхождении с Lesta.ru:**

Изначально было обнаружено расхождение в генерации `pow`. После детального анализа выяснилось, что **Lesta.ru использует алгоритм хеширования Keccak-512, а не стандартный SHA3-512**. Это ключевое различие, так как эти два алгоритма дают разные хеши для одних и тех же входных данных.

- **Мой проект (PHP-версия `api.php`) изначально использовал SHA3-512**, что приводило к другому `pow`.
- **Lesta.ru использует Keccak-512**, что дает `pow=13051` для примера.

**Решение:**

В проекте теперь доступны:
- **`api.php`**: PHP-версия, использующая SHA3-512 (может отличаться от Lesta.ru, но работает корректно).
- **`api_keccak.php`**: PHP-версия, которая пытается использовать Keccak-512 (если доступно на хостинге) с fallback на SHA3-512.
- **`pow_generator.py`**: Python-версия, использующая Keccak-512, которая **обеспечивает 100% совместимость с Lesta.ru**.

**Рекомендуется использовать Python-версию (`pow_generator.py`) для точного соответствия Lesta.ru, если ваш хостинг поддерживает Python и необходимые библиотеки.**

## 🎯 Использование с Lesta.ru

```javascript
// 1. Получить challenge данные
const challenge = await fetch('https://lesta.ru/id/signin/challenge/?type=pow', {
  headers: { 'Referer': 'https://lesta.ru/id/signin/' }
}).then(r => r.json());

// 2. Сгенерировать POW
const powResult = await fetch('https://your-domain.infinityfreeapp.com/api.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(challenge)
}).then(r => r.json());

// 3. Использовать в форме входа
const loginData = {
  login: 'your@email.com',
  password: 'password',
  pow: powResult.pow,
  // ... другие поля
};
```

## 📈 Производительность

- **Complexity 3**: ~0.1-1 секунда
- **Complexity 5**: ~1-10 секунд  
- **Complexity 10**: ~1-60 секунд

## 🔒 Безопасность

- CORS настройки для кросс-доменных запросов
- Валидация всех входных параметров
- Лимиты выполнения для предотвращения DoS
- Безопасные HTTP заголовки

## 📝 Лицензия

MIT License - свободное использование в коммерческих и некоммерческих проектах.

## 🤝 Поддержка

Полная документация доступна в файле `README.md` в корне проекта.

---

⭐ **Поставьте звезду, если проект оказался полезным!**

