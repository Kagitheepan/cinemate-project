# Guide de déploiement de Cinemate sur Render

Ce guide vous explique pas-à-pas comment mettre en ligne l'application **Cinemate** sur [Render](https://render.com/). 

L'architecture de Cinemate se compose de :
1. **Frontend** : Application React (Vite) en TypeScript.
2. **Backend** : API Symfony (PHP 8.4) s'exécutant dans un conteneur Docker.
3. **Bases de données** : 
   - **MySQL** pour la gestion des utilisateurs.
   - **MongoDB** pour le stockage des films et métadonnées.

---

## 🛠️ Prérequis

Avant de commencer, assurez-vous d'avoir :
- Un compte **GitHub** avec le code de votre projet poussé sur un dépôt (public ou privé).
- Un compte **Render** connecté à votre compte GitHub.
- Vos clés d'API tierces :
  - **TMDB API Key**
  - **Streaming Availability API Key** (MovieOfTheNight)

---

## 💾 Étape 1 : Hébergement des Bases de Données

Render ne propose pas de service managé gratuit pour **MySQL** ou **MongoDB**. Nous vous conseillons d'utiliser des fournisseurs Cloud gratuits tiers pour ces bases, puis de lier les URLs de connexion à Render.

### 1. Base NoSQL : MongoDB (Recommandé : MongoDB Atlas)
1. Créez un compte gratuit sur [MongoDB Atlas](https://www.mongodb.com/cloud/atlas).
2. Créez un cluster gratuit (**M0 Shared**).
3. Dans l'onglet **Database Access**, créez un utilisateur avec les droits de lecture/écriture (ex: `cinemate_user` avec un mot de passe fort).
4. Dans **Network Access**, ajoutez l'adresse IP `0.0.0.0/0` pour autoriser les connexions depuis Render.
5. Cliquez sur **Connect** > **Drivers** pour obtenir votre chaîne de connexion (URI) :
   ```text
   mongodb+srv://cinemate_user:<password>@cluster0.xxxx.mongodb.net/?retryWrites=true&w=majority
   ```
   *(Notez cette URI pour l'étape du backend).*

### 2. Base SQL : MySQL (Plusieurs Options)
* **Option A : Fournisseur managé externe (Recommandé & Gratuit)**
  Utilisez un hébergeur comme [Aiven](https://aiven.io/) ou [Clever Cloud](https://www.clever-cloud.com/) qui proposent des instances MySQL gratuites ou à très bas coût. Récupérez la chaîne de connexion MySQL sous la forme :
  ```text
  mysql://user:password@host:port/database_name?serverVersion=8.0&charset=utf8mb4
  ```

* **Option B : Conteneur MySQL sur Render (Instance payante requise)**
  Si vous préférez tout centraliser sur Render, vous pouvez déployer MySQL en tant que **Web Service** privé ou public à partir de l'image Docker officielle `mysql:8.0`.
  > [!WARNING]
  > Pour éviter de perdre vos utilisateurs à chaque redémarrage, vous devez attacher un **Persistent Disk** (disque persistant), ce qui nécessite un plan payant (à partir de 7$/mois pour l'instance + le coût du disque).

### 🔌 3. Gestion et Migration avec TablePlus

> [!NOTE]
> **Clarification importante** : TablePlus est un client (logiciel d'administration de bases de données installé sur votre ordinateur). Il n'héberge pas de base de données en ligne. Votre base de données doit être créée chez un hébergeur cloud (comme Aiven, Clever Cloud ou MongoDB Atlas), et vous utiliserez TablePlus pour vous y connecter, importer vos données locales et gérer vos tables.

#### A. Connexion de TablePlus à vos bases de données en ligne

Une fois vos bases de données cloud créées (voir étapes ci-dessus) :

* **Pour votre base SQL (MySQL / MariaDB)** :
  1. Ouvrez TablePlus et cliquez sur **Create a new connection...** (ou utilisez le raccourci `Ctrl + N`).
  2. Sélectionnez **MySQL** dans la liste.
  3. Remplissez les champs avec les informations de votre base de données en ligne (Aiven ou Clever Cloud) :
     - **Host** : l'hôte fourni par votre hébergeur (ex: `mysql-1234a-my-project.aivencloud.com`).
     - **Port** : le numéro de port (souvent `3306` ou un port personnalisé comme `25712`).
     - **User** : votre nom d'utilisateur (ex: `avnadmin` ou le user créé).
     - **Password** : le mot de passe associé.
     - **Database** : le nom de votre base de données (ex: `cinemate_users`).
     - **SSL Mode** : Recommandé en production. Choisissez `REQUIRED` ou importez le certificat CA fourni par l'hébergeur si nécessaire.
  4. Cliquez sur **Test** pour valider les identifiants. Si le voyant passe au vert, cliquez sur **Connect** (et enregistrez la connexion).

* **Pour votre base NoSQL (MongoDB)** :
  * **Option A : Via TablePlus** :
    1. Ouvrez TablePlus, cliquez sur **Create a new connection...** et choisissez **MongoDB**.
    2. Sur MongoDB Atlas, récupérez votre chaîne de connexion (URI) du type :
       `mongodb+srv://cinemate_user:<password>@cluster0.xxxx.mongodb.net/cinemate`
    3. Dans TablePlus, cliquez sur le bouton **Import from URL** (en bas à gauche) et collez l'URI MongoDB Atlas.
    4. Cliquez sur **Connect**.
  * **Option B : Via l'extension MongoDB de votre IDE (Recommandé si vous utilisez VS Code / JetBrains)** :
    1. Installez/ouvrez l'extension MongoDB officielle dans votre IDE.
    2. Cliquez sur **Add Connection** / **Connect with Connection String**.
    3. Collez la même URI de connexion MongoDB Atlas (`mongodb+srv://...`) que vous avez créée pour votre base de production.
    4. Vous pourrez ainsi visualiser vos collections de production, exécuter des requêtes et lancer des scripts (comme votre fichier `playground-1.mongodb.js`) directement depuis votre éditeur de code. C'est idéal pour vérifier que vos données sont bien en place une fois le site en production.

#### B. Migration de vos données locales vers le Cloud via TablePlus

Pour transférer la structure et les données que vous aviez en local vers vos serveurs de production :

1. **Exporter votre base locale** :
   - Connectez-vous à votre base de données locale (ex: `cinemate_users`) avec TablePlus.
   - Faites un clic droit sur le nom de votre base de données dans la barre latérale ou allez dans le menu supérieur **File** > **Backup...** (ou `Ctrl + Shift + B`).
   - Sélectionnez votre base locale, puis cliquez sur **Start Backup**.
   - Enregistrez le fichier `.sql` généré sur votre ordinateur.
2. **Importer dans la base de production** :
   - Connectez-vous à votre base de données de production en ligne (Aiven / Clever Cloud) via TablePlus.
   - Allez dans le menu supérieur **File** > **Restore...** (ou `Ctrl + Shift + R`).
   - Choisissez le fichier `.sql` que vous venez d'exporter.
   - Cliquez sur **Start Restore**. TablePlus va exécuter toutes les requêtes SQL pour recréer vos tables et insérer vos utilisateurs/données.

---

## 🖥️ Étape 2 : Déploiement du Backend Symfony (Docker Web Service)

Comme Render ne supporte pas nativement PHP dans ses environnements managés par défaut, nous utilisons le fichier [Dockerfile](file:///c:/Users/steef/cinemate/backend/Dockerfile) présent dans le dossier backend.

### 1. Création du service sur Render
1. Sur le tableau de bord Render, cliquez sur **New +** puis **Web Service**.
2. Connectez votre dépôt GitHub.
3. Remplissez les champs de configuration suivants :
   - **Name** : `cinemate-backend`
   - **Root Directory** : `backend` *(Très important : cela indique à Render de se placer dans le dossier backend avant de compiler)*
   - **Runtime** : `Docker`
   - **Build Filter** : Laissez par défaut.
   - **Instance Type** : `Free` (ou supérieur si besoin).

### 2. Adaptation du démarrage pour la production (JWT et Migrations)
Puisque les clés de sécurité JWT (`/config/jwt/*.pem`) sont dans le fichier `.gitignore` et ne sont pas poussées sur GitHub, elles doivent être générées dynamiquement lors du déploiement. 

Pour cela, vous pouvez modifier l'instruction finale de démarrage dans votre [Dockerfile](file:///c:/Users/steef/cinemate/backend/Dockerfile) ou surcharger la commande de démarrage dans l'interface de Render.

La commande idéale à exécuter au démarrage du conteneur est :
```bash
composer install --no-interaction --optimize-autoloader && php bin/console lexik:jwt:generate-keypair --skip-if-exists && php bin/console doctrine:migrations:migrate --no-interaction && php -S 0.0.0.0:8000 -t public
```

> [!TIP]
> Le serveur de développement PHP (`php -S`) intégré par défaut dans le Dockerfile est mono-threadé et n'est pas recommandé pour de la production intensive. Pour un site en production avec du trafic réel, il est conseillé de modifier le Dockerfile pour utiliser une image basée sur **Apache** ou **Nginx + PHP-FPM** (par exemple `php:8.4-apache`).

### 3. Variables d'environnement du Backend
Dans l'onglet **Environment** de votre Web Service sur Render, ajoutez les variables suivantes :

| Clé | Valeur | Description |
| :--- | :--- | :--- |
| `APP_ENV` | `prod` | Active le mode production de Symfony (cache, performances). |
| `APP_SECRET` | *(Générez une chaîne aléatoire de 32 caractères)* | Clé de sécurité interne de Symfony. |
| `DATABASE_URL` | `mysql://...` | Votre chaîne de connexion MySQL (Aiven, Clever Cloud, etc.). |
| `MONGODB_URI` | `mongodb+srv://...` | Votre chaîne de connexion MongoDB Atlas. |
| `MONGODB_DB` | `cinemate` (ou nom personnalisé) | Le nom de la base de données MongoDB pour les films. |
| `JWT_PASSPHRASE` | *(Générez un mot de passe fort)* | Passphrase pour chiffrer les clés JWT. |
| `JWT_COOKIE_SECURE` | `true` | Obligatoire en production pour sécuriser le cookie JWT en HTTPS. |
| `TMDB_API_KEY` | *(Votre clé TMDB)* | Clé d'accès à l'API The Movie Database. |
| `STREAMING_API_KEY` | *(Votre clé MovieOfTheNight)* | Clé d'accès à l'API de streaming. |
| `CORS_ALLOW_ORIGIN` | `^https?://[a-zA-Z0-9-]+\.onrender\.com$` | Autorise votre futur frontend Render à interroger l'API. |

*Render va maintenant builder l'image Docker du backend et lancer le service sur le port standard défini.*

---

## 🎨 Étape 3 : Déploiement du Frontend React (Static Site)

Le frontend étant développé en React avec Vite, il peut être compilé sous forme de fichiers statiques (HTML/CSS/JS) et hébergé **gratuitement** sur le service de site statique de Render.

### 1. Création du service sur Render
1. Sur le tableau de bord Render, cliquez sur **New +** puis **Static Site**.
2. Connectez le même dépôt GitHub.
3. Configurez les champs suivants :
   - **Name** : `cinemate` (ou le nom de votre choix)
   - **Root Directory** : `frontend`
   - **Build Command** : `npm install && npm run build`
   - **Publish Directory** : `dist`
   - **Instance Type** : `Free` (les sites statiques sont toujours gratuits sur Render).

---

## 🔄 Étape 4 : Configuration du Proxy de l'API (Rewrite Rules)

En développement, Vite utilise un proxy interne (`proxy: { '/api': ... }` défini dans [vite.config.ts](file:///c:/Users/steef/cinemate/frontend/vite.config.ts)) pour rediriger les requêtes vers le backend sans rencontrer d'erreurs CORS. 

En production (site statique), ce proxy de développement n'existe plus. Render propose une fonctionnalité très puissante de **Redirects/Rewrites** pour recréer ce comportement directement au niveau des serveurs de Render.

### Configurer les règles de réécriture sur le Frontend (Proxy & React Router) :
1. Allez sur la page de votre service **Frontend (Static Site)** sur Render.
2. Dans le menu de gauche, cliquez sur **Redirects/Rewrites**.
3. Cliquez sur **Add Rule** pour créer la règle de l'API (très important de la mettre en premier) :
   - **Source** : `/api/*`
   - **Destination** : `https://cinemate-backend.onrender.com/api/*` *(Remplacez par l'URL réelle de votre Web Service backend)*
   - **Action** : `Rewrite`
4. Cliquez à nouveau sur **Add Rule** pour créer la règle de React Router (qui gère la navigation côté client) :
   - **Source** : `/*`
   - **Destination** : `/index.html`
   - **Action** : `Rewrite`
5. Enregistrez les règles.

Désormais, les appels vers `/api/` seront transférés de manière transparente vers votre backend Symfony (évitant les erreurs CORS), et toutes les autres pages (ex: `/profile`) fonctionneront sans renvoyer d'erreur 404 !

---

## 🚀 Étape 5 : Validation finale

1. Attendez que les builds du Backend et du Frontend soient au statut **Live** (vert).
2. Rendez-vous sur l'URL de votre frontend (ex: `https://cinemate.onrender.com`).
3. Testez les fonctionnalités clés :
   - Inscription / Connexion (vérifie la connexion MySQL et la génération du cookie JWT).
   - Recherche et affichage des films (vérifie la connexion MongoDB et les appels aux APIs TMDB/Streaming).
