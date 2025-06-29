#!/usr/bin/env python3
"""
Hashcash POW Generator - Python версия с поддержкой Keccak-512
100% совместимость с Lesta.ru
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from Crypto.Hash import keccak
import json

app = Flask(__name__)
CORS(app)  # Разрешаем CORS для всех доменов

def pow_keccak(version, complexity, timestamp, resource, extension, random_string):
    """
    Генерирует POW используя Keccak-512 (100% совместимость с Lesta.ru)
    """
    # Формируем базовую строку
    var7 = f"{version}:{complexity}:{timestamp}:{resource}:{extension}:{random_string}:"
    zero_quantity = "0" * complexity
    
    # Итерируем от 1 до 1,000,000
    for i in range(1, 1000000):
        var10 = f"{var7}{i}"
        
        # Используем Keccak-512
        keccak_hash = keccak.new(digest_bits=512)
        keccak_hash.update(var10.encode())
        result = keccak_hash.hexdigest()
        
        # Проверяем ведущие нули
        if result.startswith(zero_quantity):
            return i
    
    return None

@app.route('/api', methods=['POST'])
def generate_pow():
    """
    API endpoint для генерации POW
    """
    try:
        # Получаем JSON данные
        data = request.get_json()
        
        if not data or 'pow' not in data:
            return jsonify({'error': 'Invalid JSON data'}), 400
        
        pow_data = data['pow']
        
        # Извлекаем параметры
        version = pow_data.get('algorithm', {}).get('version', 1)
        complexity = int(pow_data.get('complexity', 0))
        timestamp = int(pow_data.get('timestamp', 0))
        resource = pow_data.get('algorithm', {}).get('resourse', '')
        extension = pow_data.get('algorithm', {}).get('extension', '')
        random_string = pow_data.get('random_string', '')
        
        # Валидация
        if complexity <= 0 or timestamp <= 0 or not random_string:
            return jsonify({'error': 'Missing required parameters'}), 400
        
        # Генерируем POW
        result = pow_keccak(version, complexity, timestamp, resource, extension, random_string)
        
        if result is None:
            return jsonify({'error': 'POW not found within range'}), 403
        
        return jsonify({
            'pow': result,
            'algorithm_used': 'keccak-512',
            'compatibility': 'lesta.ru'
        })
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api', methods=['GET'])
def api_info():
    """
    Информация об API
    """
    return jsonify({
        'name': 'Hashcash POW Generator (Python/Keccak)',
        'version': '1.0',
        'description': 'API for generating Proof of Work (POW) values using Keccak-512 algorithm',
        'algorithm': 'keccak-512',
        'compatibility': '100% compatible with Lesta.ru',
        'usage': {
            'method': 'POST',
            'endpoint': '/api',
            'content_type': 'application/json',
            'body': {
                'pow': {
                    'algorithm': {
                        'version': 1,
                        'resourse': 'wgni',
                        'extension': ''
                    },
                    'complexity': 3,
                    'timestamp': 1751209794,
                    'random_string': 'bCXX2S4JiyogmA0J'
                }
            }
        }
    })

@app.route('/')
def index():
    """
    Главная страница
    """
    return """
    <h1>Hashcash POW Generator (Python/Keccak)</h1>
    <p>100% совместимость с Lesta.ru</p>
    <p>API endpoint: <code>/api</code></p>
    <p>Алгоритм: Keccak-512</p>
    """

if __name__ == '__main__':
    # Для разработки
    app.run(host='0.0.0.0', port=5000, debug=True)

