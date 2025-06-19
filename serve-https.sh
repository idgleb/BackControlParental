#!/bin/bash

# 🌐  Iniciar ngrok
echo "🚀 Iniciando ngrok..."
pkill ngrok 2>/dev/null
nohup ngrok http 80 > /dev/null 2>&1 &
sleep 4

# 📡  Obtener URL pública
NGROK_URL=$(curl --silent http://127.0.0.1:4040/api/tunnels | jq -r '.tunnels[] | select(.proto == "https") | .public_url')

if [ -n "$NGROK_URL" ]; then
    echo "✅ Laravel ahora está accesible por HTTPS en:"
    echo "$NGROK_URL"

else
    echo "❌ No se pudo obtener la URL de ngrok."
fi
