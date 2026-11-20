#!/bin/sh
set -e

echo ">> Installation des dépendances Composer..."
composer install --no-interaction --optimize-autoloader

echo ">> Génération des clés JWT si nécessaires..."
if [ ! -f config/jwt/private.pem ]; then
    php bin/console lexik:jwt:generate-keypair --no-interaction
fi

echo ">> Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo ">> Nettoyage du cache..."
php bin/console cache:clear

echo ">> Démarrage du serveur PHP..."
exec php -S 0.0.0.0:8000 -t public
