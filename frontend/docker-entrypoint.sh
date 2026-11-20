#!/bin/sh
set -e

echo ">> Installation des dépendances npm..."
npm install

echo ">> Démarrage du serveur Vite..."
exec npm run dev -- --host 0.0.0.0
