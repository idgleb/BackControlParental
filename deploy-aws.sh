#!/bin/bash

echo "🚀 Iniciando despliegue a AWS..."

# 1. Compilar assets para producción
echo "📦 Compilando assets..."
npm run build

if [ $? -ne 0 ]; then
    echo "❌ Error compilando assets"
    exit 1
fi

# 2. Agregar cambios
echo "📝 Agregando cambios..."
git add .
git add -f public/build/

# 3. Commit (pedir mensaje)
echo "💬 Ingresa el mensaje del commit:"
read commit_message

if [ -z "$commit_message" ]; then
    commit_message="Update: Deploy to AWS with compiled assets"
fi

git commit -m "$commit_message"

if [ $? -ne 0 ]; then
    echo "❌ Error en commit (puede que no haya cambios)"
    echo "🔍 Verificando estado..."
    git status
    exit 1
fi

# 4. Push a GitHub
echo "🌐 Desplegando a AWS via GitHub Actions..."
git push origin main

if [ $? -eq 0 ]; then
    echo "✅ ¡Despliegue exitoso!"
    echo "🔗 Ve el progreso en: https://github.com/idgleb/BackControlParental/actions"
    echo "🌐 App disponible en: https://goooglee.online"
else
    echo "❌ Error en el push"
    exit 1
fi 