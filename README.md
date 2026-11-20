# ComMob - Communication Mobile

Application web d'aide à la communication destinée aux personnes en situation de handicap (moteur, cognitif, troubles de la parole). ComMob permet de générer des phrases contextualisées à l'aide de pictogrammes et de les faire lire à voix haute via synthèse vocale.

## Stack technique

| Couche | Technologies |
|--------|-------------|
| Frontend | Vue 3, TypeScript, Vite, Pinia, Vue Router, Lucide Icons |
| Backend | PHP 8.3, Symfony, Doctrine ORM, Lexik JWT |
| Base de données | PostgreSQL 16 |
| Infrastructure | Docker Compose |
| Email (dev) | Mailpit |

## Prérequis

- [Docker](https://www.docker.com/) et Docker Compose

## Configuration des secrets

Les secrets ne sont **jamais** versionnés. Le fichier `backend/.env` ne contient que des valeurs vides ou de dev.
Avant le premier lancement, créer `backend/.env.local` (ignoré par git) :

```dotenv
APP_SECRET=<chaîne aléatoire, ex: openssl rand -hex 16>
GROQ_API_KEY="<clé API Groq>"
ID_client="<Google OAuth client ID>"
secret_client="<Google OAuth client secret>"
JWT_PASSPHRASE=<passphrase des clés JWT>
mdp_appli="<mot de passe d'application Gmail>"
```

## Lancement

Une seule commande suffit pour lancer l'ensemble de l'application :

```bash
docker compose up --build -d
```

Tout est automatisé au démarrage des conteneurs :
- Installation des dépendances (Composer + npm)
- Migrations de la base de données
- Génération des clés JWT
- Lancement des serveurs

### Accéder à l'application

| Service | URL |
|---------|-----|
| Application | http://localhost:5173 |
| API Backend | http://localhost:8080 |
| Mailpit (emails de dev) | http://localhost:8025 |

## Commandes utiles

```bash
# Démarrer les conteneurs
docker compose up -d

# Arrêter les conteneurs
docker compose down

# Voir les logs du backend
docker logs commob_backend --tail 50

# Voir les logs du frontend
docker logs commob_frontend --tail 50

# Exécuter une commande Symfony
docker exec commob_backend php bin/console <commande>

# Vider le cache Symfony
docker exec commob_backend php bin/console cache:clear
```

## Structure du projet

```
ComMob/
├── backend/                # API Symfony (PHP 8.3)
│   ├── config/             # Configuration Symfony (routes, sécurité, JWT, CORS)
│   ├── migrations/         # Migrations Doctrine
│   ├── src/
│   │   ├── Controller/     # Contrôleurs API (Auth, Context, Phrase)
│   │   ├── Entity/         # Entités Doctrine (User, OtpCode)
│   │   └── Repository/     # Repositories Doctrine
│   ├── Dockerfile
│   └── docker-entrypoint.sh
├── frontend/               # Application Vue.js
│   ├── src/
│   │   ├── assets/         # Ressources statiques (logo SVG)
│   │   ├── components/     # Composants Vue réutilisables
│   │   ├── composables/    # Logique réutilisable (TTS, focus, clavier)
│   │   ├── layouts/        # Layouts (Main, Auth)
│   │   ├── services/       # Appels API (auth, context)
│   │   ├── stores/         # Pinia stores (auth, accessibilité)
│   │   ├── styles/         # CSS global (variables, reset, focus)
│   │   ├── types/          # Types TypeScript
│   │   └── views/          # Pages principales
│   ├── dockerfile
│   └── docker-entrypoint.sh
├── docker-compose.yml
├── .env                    # Variables d'environnement partagées
└── README.md
```

## Auteurs

- Jérémy Monguin ([@jermgn](https://github.com/jermgn))
- Soufiane Saidi ([@saidijr10](https://github.com/saidijr10))
