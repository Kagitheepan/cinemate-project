# Dossier de Projet : Cinemate

Ce dossier contient les éléments requis pour la présentation du projet, réalisé dans le cadre de la période en entreprise. Il est structuré pour répondre aux exigences académiques ou de certification.

## 1. Liste des Compétences Mises en Œuvre

### Backend (Développement Serveur)
Le backend est développé en **PHP** avec le framework **Symfony**, suivant une architecture MVC et API-oriented.
*   **Technologies & Outils** :
    *   **Symfony 7.4 (Dernière version)** : Utilisation des composants Kernel, Console, Security, HttpClient.
    *   **PHP 8.3** : Syntaxe moderne, typage fort, attributs natifs.
    *   **Bases de Données (Hybride)** :
        *   **MongoDB (NoSQL)** : Gestion des documents flexibles afin de reccueillir les logs des utilisateurs (avec `doctrine/mongodb-odm`).
        *   **MySQL/MariaDB (SQL)** : Gestion relationnelle des Utilisateurs et Authentification ainsi que l'affichage des films et séries qui sont récupérés via l'API TMDB puis intégrer à la base de données (avec `doctrine/orm` & `migrations`).
    *   **API Platform / REST** : Exposition des données via des endpoints REST sécurisés.
    *   **Commandes CLI** : Automatisation de l'import de données externes (TMDB).

### Frontend (Développement Interface)
Le frontend est une Application réactive, développée en **React**, **TypeScript** et **Tailwind CSS**.
*   **Technologies & Outils** :
    *   **React 19** : Framework UI moderne.
    *   **Vite** : Build tool rapide pour le développement.
    *   **TypeScript** : Typage statique pour la robustesse du code client.
    *   **Tailwind CSS 4** : Framework utilitaire pour le styling réactif et le design system.
    *   **React Router DOM** : Gestion de la navigation client-side.
    *   **Axios** : Communication asynchrone avec l'API Backend.
    *   **dnd-kit** : Implémentation de fonctionnalités de Drag & Drop (glisser-déposer).
    *   **Lucide React** : Intégration d'icônes SVG optimisées.

### Infrastructure & DevOps
*   **Docker & Docker Compose** : Conteneurisation de l'ensemble de la stack (PHP/Symfony, MongoDB, Node/React) pour un environnement de développement reproductible.
*   **Git** : Gestion de versions et collaboration.
*   **Composer & NPM** : Gestion des dépendances PHP et JavaScript.

---

## 2. Cahier des Charges et Expression des Besoins

### Contexte
L’application répond à un besoin courant : choisir rapidement un film adapté au temps réellement disponible. Beaucoup d’utilisateurs disposent de créneaux limités (pause, soirée courte, transports) et perdent du temps à parcourir des catalogues sans savoir si un film rentre dans leur planning.

L’application permet de filtrer et recommander des films en fonction de la durée disponible, tout en tenant compte de critères complémentaires (genre, humeur, popularité, plateformes). Elle vise à optimiser le temps de décision, améliorer l’expérience utilisateur et réduire la frustration liée au choix excessif sur les plateformes de streaming.
### Besoins Fonctionnels
1.  **Ingestion et Gestion des Données** :
    *   Concevoir un système d'import automatique depuis l'API **The Movie Database (TMDB)**.
    *   Stocker les informations détaillées des films (titre, synopsis, casting, genres) de manière performante.
    *   Mettre à jour régulièrement la base de données locale via des commandes planifiées (`app:import-movies`).

2.  **Expérience Utilisateur (UX/UI)** :
    *   Offrir une interface de navigation fluide.
    *   Permettre la recherche de films par titre, critères ou temps de visionnage.
    *   Afficher les détails complets d'une œuvre (casting, équipe technique).
    *   Intégrer des fonctionnalités interactives (ex: constitution de listes par glisser-déposer).

3.  **Sécurité et Architecture** :
    *   Sécuriser l'accès à l'API via une authentification **JWT (JSON Web Token)**.
    *   Séparer clairement la logique métier (Backend) de l'interface (Frontend).
    *   Assurer la persistance et l'intégrité des données via une stratégie de base de données hybride (SQL pour les users, NoSQL pour le catalogue).

### Contraintes Techniques
*   Respect des standards de code modernes (PSR pour PHP, ESLint pour JS/TS).
*   Optimisation des performances (Batch processing pour l'import, Lazy loading pour le frontend).
*   Déploiement conteneurisé.

---

## 3. Présentation de l'Entreprise et du Service

> *Cette section doit être complétée avec les informations spécifiques à votre structure d'accueil.*

*   **Identité de l'Entreprise** :
    *   **Nom** : [À COMPLÉTER - Ex: Digital Agency SAS, Service Informatique de X...]
    *   **Secteur d'activité** : [À COMPLÉTER - Ex: E-commerce, Développement Web, Services Numériques]
    *   **Taille** : [À COMPLÉTER - Ex: PME de 50 personnes, ETI...]

*   **Le Service / L'Équipe** :
    *   Le projet a été réalisé au sein du service [Nom du service - Ex: R&D, Pôle Web].
    *   L'équipe est composée de [Nombre] collaborateurs (Développeurs, Chef de projet, Lead Dev).
    *   **Méthodologie de travail** : [Ex: Agile/Scrum avec daily meetings et sprints de 2 semaines].

*   **Rôle et Missions** :
    *   En tant que [Stagiaire / Alternant], la mission principale a consisté à concevoir et développer la stack complète de l'application Cinemate, de la modélisation de la base de données à l'intégration frontend.
