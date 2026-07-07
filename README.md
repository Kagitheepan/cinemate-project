# CinéMate

## À propos

**CinéMate** est une application web de gestion de films qui permet aux utilisateurs de découvrir, organiser et suivre les films qu'ils souhaitent voir ou qu'ils ont déjà vus. L'application offre des fonctionnalités de watchlist, d'agenda, de recommandations personnalisées et de gestion de profil.

## Table des matières

- [À propos](#à-propos)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Contribution](#contribution)
- [Construit avec](#construit-avec)
- [Documentation](#documentation)
- [Gestion des versions](#gestion-des-versions)
- [Licence](#licence)

---

## Prérequis

| Outil | Version | Documentation |
|-------|---------|---------------|
| [Docker](https://docs.docker.com/get-docker/) | ≥ 20.x | [docs.docker.com](https://docs.docker.com/) |
| [Docker Compose](https://docs.docker.com/compose/install/) | ≥ 2.x | [docs.docker.com/compose](https://docs.docker.com/compose/) |
| [Git](https://git-scm.com/) | ≥ 2.x | [git-scm.com/doc](https://git-scm.com/doc) |

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/Kagitheepan/cinemate-project.git
cd cinemate-project
```

### 2. Lancer l'application

```bash
docker compose up --build
```

Les services démarrent sur les ports suivants :

| Service | Conteneur | Port |
|---------|-----------|------|
| Backend (Symfony) | `cinemate-back` | [localhost:8000](http://localhost:8000) |
| Frontend (React) | `cinemate-front` | [localhost:5173](http://localhost:5173) |
| MySQL | `cinemate-mysql` | `3306` |
| MongoDB | `cinemate-mongo` | `27018` |
| phpMyAdmin | `cinemate-pma` | [localhost:8080](http://localhost:8080) |

---

## Utilisation

### Lancement en mode développement

```bash
docker compose up          # Premier plan
docker compose up -d       # Arrière-plan
docker compose logs -f backend   # Logs d'un service
```

### Exécution des tests

#### Tests backend (PHPUnit)

```bash
docker exec cinemate-back php bin/console doctrine:database:create --env=test --if-not-exists
docker exec cinemate-back php bin/console doctrine:schema:update --force --env=test
docker exec cinemate-back php bin/console doctrine:fixtures:load --env=test -n

docker exec cinemate-back php bin/phpunit -c phpunit.xml.dist --coverage-text
```

#### Tests frontend (Jest)

```bash
cd frontend
npm test                   # Lancer les tests
npm run test:coverage      # Avec couverture de code
```

### Linting & Build

```bash
cd frontend
npm run lint               # Vérification du code
npm run build              # Build de production
```

---

## Contribution

### Workflow

```bash
# 1. Créer une branche
git checkout main && git pull origin main
git checkout -b feature/nom-de-la-fonctionnalite

# 2. Développer et commiter
git add .
git commit -m "feat: description de la fonctionnalité"

# 3. Pousser et ouvrir une Pull Request
git push origin feature/nom-de-la-fonctionnalite
```

La CI (GitHub Actions) exécutera automatiquement les tests avant le merge.

### Conventions de commits

Nous utilisons [Conventional Commits](https://www.conventionalcommits.org/) : `feat:`, `fix:`, `docs:`, `style:`, `refactor:`, `test:`, `chore:`

---

## Construit avec

### Langages & Frameworks

| Technologie | Description | Documentation |
|-------------|-------------|---------------|
| [React](https://react.dev/) 19 | Bibliothèque UI JavaScript | [react.dev](https://react.dev/) |
| [TypeScript](https://www.typescriptlang.org/) 5.9 | Superset typé de JavaScript | [typescriptlang.org](https://www.typescriptlang.org/docs/) |
| [Vite](https://vite.dev/) 7 | Outil de build frontend | [vite.dev](https://vite.dev/guide/) |
| [Tailwind CSS](https://tailwindcss.com/) 4 | Framework CSS utility-first | [tailwindcss.com](https://tailwindcss.com/docs/) |
| [Symfony](https://symfony.com/) 7 | Framework PHP | [symfony.com](https://symfony.com/doc/current/index.html) |
| [Doctrine ORM](https://www.doctrine-project.org/) | ORM pour MySQL | [doctrine-project.org](https://www.doctrine-project.org/projects/orm.html) |
| [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/mongodb-odm.html) | ODM pour MongoDB | [doctrine-project.org](https://www.doctrine-project.org/projects/mongodb-odm.html) |
| [MySQL](https://www.mysql.com/) 8.0 | BDD relationnelle (utilisateurs) | [dev.mysql.com](https://dev.mysql.com/doc/) |
| [MongoDB](https://www.mongodb.com/) 6.0 | BDD NoSQL (films) | [mongodb.com](https://www.mongodb.com/docs/) |

### Outils

#### CI

[GitHub Actions](https://docs.github.com/en/actions) — Pipeline CI déclenché sur `push` et `pull_request` vers `main`. Deux jobs parallèles :

- **Frontend** : `npm ci` → Tests Jest → Build
- **Backend** : Composer install → MySQL + MongoDB → Tests PHPUnit

#### Déploiement

[Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/) — Orchestration de 5 conteneurs (backend PHP 8.4-Apache, frontend Node 20, MySQL 8.0, MongoDB 6.0, phpMyAdmin). Données persistées via volumes nommés.

---

## Documentation

- [Symfony](https://symfony.com/doc/current/index.html) — Framework backend
- [React](https://react.dev/learn) — Bibliothèque frontend
- [Docker Compose](https://docs.docker.com/compose/) — Orchestration des services
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html) — ORM MySQL
- [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/index.html) — ODM MongoDB

---

## Gestion des versions

Afin de maintenir un cycle de publication clair et de favoriser la rétrocompatibilité, la dénomination des versions suit la spécification décrite par la [Gestion sémantique de version](https://semver.org/lang/fr/).

Les versions disponibles ainsi que les journaux décrivant les changements apportés sont disponibles depuis la [page des Releases](https://github.com/Kagitheepan/cinemate-project/releases).

---

## Licence

Voir le fichier [LICENSE](LICENSE) du dépôt.
