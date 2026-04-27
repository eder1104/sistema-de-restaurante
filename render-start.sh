#!/bin/bash
# Procesar colas en segundo plano
php artisan queue:work &

# Iniciar servidor web de Laravel en el puerto que pide Render
php artisan serve --host=0.0.0.0 --port=$PORT
