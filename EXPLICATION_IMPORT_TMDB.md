# Documentation Technique : Pipeline d'Ingestion TMDB vers MongoDB

Ce document détaille l'architecture technique mise en place pour synchroniser les données de l'API TheMovieDB (TMDB) avec la base de données locale MongoDB au sein de l'application Symfony.

## 1. Architecture Infrastructurelle (Docker)

Le projet repose sur une architecture conteneurisée via `docker-compose` assurant l'isolation des services :

*   **Service Backend (`cinemate-back`)** : Conteneur PHP avec l'extension `mongodb` activée. Il héberge le kernel Symfony.
*   **Service Base de Données (`cinemate-mongo`)** : Instance officielle MongoDB 6.0.
*   **Réseau** : Les deux services communiquent via le réseau privé docker `default`.
*   **Configuration** :
    *   L'URI de connexion est injectée dynamiquement via la variable d'environnement `MONGODB_URI` (`mongodb://root:root@mongo-db:27017`) définie dans le `docker-compose.yml`.
    *   La base de données cible est définie par `MONGODB_DB=cinemate_movies`.

## 2. Couche de Persistance (Doctrine ODM)

Nous utilisons **Doctrine MongoDB ODM** (Object Document Mapper) pour faire l'abstraction entre les objets PHP et les documents BSON.

### Mapping Object-Document
Le fichier `src/Document/Movie.php` agit comme l'entité centrale.
*   **Attributes PHP 8** : Utilisation des attributs natifs (ex: `#[MongoDB\Document]`) au lieu des annotations ou XML.
*   **Typage fort** : Chaque propriété PHP est mappée à un type BSON précis (`string`, `int`, `collection`, `date`).
*   **Indexation** :
    ```php
    #[MongoDB\Field(type: 'int')]
    #[MongoDB\Index(unique: true)]
    private ?int $tmdbId = null;
    ```
    Une contrainte d'unicité est appliquée sur le `tmdbId`. Cela garantit l'intégrité des données au niveau de la base, empêchant physiquement l'insertion de doublons. Cette contrainte est matérialisée par la commande `doctrine:mongodb:schema:update`.

## 3. Couche Service (Communication Externe)

La logique de communication avec l'API tierce est encapsulée dans `src/Service/TmdbService.php`.

*   **Client HTTP** : Utilisation du composant `Symfony\Contracts\HttpClient\HttpClientInterface` pour effectuer des requêtes REST synchrones.
*   **Dependency Injection** :
    *   La clé API est injectée via l'attribut `#[Autowire('%env(TMDB_API_KEY)%')]`, assurant que le code ne contient aucun secret en dur.
*   **Endpoints Consommés** :
    *   `GET /genre/movie/list` : Mapping ID -> Label des genres.
    *   `GET /movie/popular` : Récupération paginée des films.
    *   `GET /movie/{id}/credits` : Récupération du casting et de l'équipe technique.

## 4. Couche CLI (Exécution)

L'importation est pilotée par une commande Symfony Console : `src/Command/ImportMoviesCommand.php`.

### Logique d'Exécution (`execute()`)
1.  **Initialisation** : Injection du `TmdbService` et du `DocumentManager` via le constructeur.
2.  **Mapping Préliminaire** : Récupération one-shot des genres pour éviter les appels N+1 lors du traitement des films.
3.  **Batch Processing** :
    *   Itération sur le nombre de pages demandées.
    *   Pour chaque film, vérification de l'existence via `$repository->findOneBy(['tmdbId' => $id])`.
    *   **Hydratation** : Création d'une nouvelle instance `Movie` ou mise à jour de l'existante.
    *   **Persistance** : Appel à `$dm->persist($movie)` qui place l'objet dans l'Unit of Work.
4.  **Transaction / Flush** :
    *   Appel à `$dm->flush()` à la fin de chaque page. Cela déclenche l'écriture (bulk write) vers MongoDB, optimisant les I/O par rapport à un flush par entrée.

## 5. Flux de Données

```mermaid
graph LR
    A[CLI Command] -->|Request Page N| B(TmdbService)
    B -->|HTTP GET| C{TMDB API}
    C -->|JSON Response| B
    B -->|Array Data| A
    A -->|Hydrate| D[Movie Document]
    D -->|Persist| E[UnitOfWork]
    E -->|Flush| F[(MongoDB)]
```

## Commandes Utiles

*   **Mise à jour du Schéma (Index)** :
    `php bin/console doctrine:mongodb:schema:update`
*   **Lancement de l'import** :
    `php bin/console app:import-movies --pages=20`
