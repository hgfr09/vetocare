# Vetocare - Portail de Gestion pour Clinique Vétérinaire

**Vetocare** est une solution de gestion centralisée (MVP) conçue pour moderniser les processus métiers d'une clinique vétérinaire en remplaçant les flux manuels par une infrastructure digitale robuste.

## Stack Technique

Le projet repose sur les technologies suivantes :
*   **Backend** : Symfony 8.1 / API Platform 4.3 — en cours de développement.
*   **Frontend** : React + TypeScript + Vite — développement à venir.
*   **Base de données** : MySQL (Développement & Test).
*   **Sécurité** : Authentification JWT via `LexikJWTAuthenticationBundle`.

### 1. Prérequis
*   PHP 8.4 ou supérieur.
*   Composer.
*   Serveur MySQL local (via XAMPP, WAMP ou installation directe).
*   Symfony CLI (recommandé pour le serveur local et HTTPS).

### 2. Lancement de l'environnement
```bash
# Installer les dépendances backend
composer install

# Générer les clés pour l'authentification JWT
php bin/console lexik:jwt:generate-keypair
```

### 3. Configuration de la base de données
Éditez votre fichier `.env.local` pour configurer votre accès MySQL
`DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/vetocare?serverVersion=8.0.32"`

**Puis exécutez les commandes suivantes :**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load 
```

### 4. Démarrage du serveur
`symfony serve -d`
L'API sera accessible sur https://127.0.0.1:8000/api

### 5. Tests
La suite de tests fonctionnels utilise `ApiTestCase` et `Zenstruck Foundry`. L'isolation des tests est assurée par `dama/doctrine-test-bundle`.

***Lancer la suite de tests***
`php bin/phpunit`