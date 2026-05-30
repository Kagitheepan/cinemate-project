# Plan de Tests Backend (PHPUnit)

Voici l'analyse des classes avec une couverture insuffisante, regroupées par responsabilité au sein de votre architecture Symfony, avec les scénarios clés à tester pour chacune.

## 1. Les Services (Appels API Externes) 🌐
*Classes : `OmdbService` (0%), `StreamingAvailabilityService` (0%), `TmdbService` (0%)*

Ces classes gèrent la communication avec des API tierces. Les tests ne doivent pas faire de vraies requêtes HTTP, il faudra "mocker" (simuler) le `HttpClient`.

**Scénarios à tester :**
- **Cas passant (200 OK) :** Simuler une réponse JSON valide de l'API externe et vérifier que le service la parse correctement pour renvoyer le format attendu par votre application.
- **Erreur API (404 Not Found) :** Vérifier le comportement quand un film n'existe pas (le service doit renvoyer `null` ou lancer une exception spécifique).
- **Erreur Serveur (500) ou Timeout :** S'assurer que si l'API externe crash, l'application gère l'erreur gracieusement sans faire planter tout le backend.

## 2. Les Controllers (Endpoints de l'API) 🚦
*Classes : `AuthController` (0%), `NotificationController` (0%), `PrivacyController` (0%), `ProfileController` (0%), `MovieController` (30%)*

**Scénarios à tester :**
- **Authentification & Sécurité :** Vérifier que les routes protégées (ex: `ProfileController`) renvoient bien une erreur `401 Unauthorized` si aucun token JWT n'est fourni.
- **Comportements normaux (200 OK) :** 
  - `AuthController` : Connexion réussie, validation des identifiants invalides (401).
  - `NotificationController` : Récupérer la liste des notifications, marquer une notification comme lue.
  - `ProfileController` : Mise à jour des informations de l'utilisateur, changement de mot de passe.
  - `MovieController` (à compléter) : Vérifier spécifiquement l'endpoint `/recommendations/for-you` qui contient beaucoup de logique métier (calcul du score, filtrage des genres favoris).
- **Gestion des erreurs (400, 404) :** Envoyer des données JSON mal formées ou demander une ressource qui n'existe pas.

## 3. Les Commandes CLI (Tâches de fond) ⌨️
*Classes : `AnalyzePlatformsCommand`, `CreateUserCommand`, `FixAllocineCommand`, `FixPlatformsCommand`, `FixTrailersCommand`, `ImportMoviesCommand`, `LoadMoviesCommand`, `SendRemindersCommand` (toutes à 0%)*

Ces commandes sont généralement appelées via la console (`php bin/console`). 

**Scénarios à tester (via le `CommandTester` de Symfony) :**
- **Exécution normale :** Lancer la commande avec les arguments valides et vérifier que la sortie console (output) contient le message de succès.
- **Impact en Base de Données :** Par exemple, pour `CreateUserCommand`, vérifier qu'après l'exécution de la commande, l'utilisateur a bien été créé en base de données avec le bon hash de mot de passe.
- **Arguments manquants/invalides :** Vérifier que la commande gère les erreurs ou pose des questions interactives si on oublie des arguments.

## 4. L'Écouteur d'Événements (Event Subscribers) 🎧
*Classe : `LoginSubscriber` (0%)*

**Scénarios à tester :**
- **Déclenchement au login :** Mocker l'événement de connexion de Symfony (`LoginSuccessEvent`) et vérifier que le Subscriber intercepte l'événement.
- **Impact métier :** Vérifier que le Subscriber met bien à jour la date de `last_login` de l'utilisateur ou crée bien une entrée dans le `ConnectionLog`.

## 5. Les Repositories (Requêtes BDD) 🗄️
*Classes : `MovieRepository` (0%), `NotificationRepository` (0%), `UserRepository` (0%)*

*Note : On ne teste généralement pas les méthodes natives de Doctrine (`find`, `findAll`). On ne teste que vos méthodes personnalisées (QueryBuilders).*

**Scénarios à tester :**
- **Méthodes de recherche spécifiques :** Si vous avez des méthodes comme `findUnreadNotificationsForUser()` ou `findMoviesByGenres()`, il faut instancier une base SQLite en mémoire, insérer un jeu de données (Fixtures), appeler la méthode, et vérifier qu'elle renvoie exactement les bons résultats.

## 6. Les Entités & Documents (Modèles de données) 📦
*Classes : `ConnectionLog` (0%), `CookieConsent` (0%), `Notification` (0%), `UserWatchlist` (50%), `UserAgenda` (58%), `Movie` (70%), `User` (74%), `Genre` (75%), `Platform` (75%)*

**Scénarios à tester :**
- **Getters / Setters simples :** S'assurer que chaque propriété peut être modifiée et lue.
- **Logique interne (si présente) :** Par exemple, si `UserAgenda` a une méthode pour vérifier si l'événement est passé (`isPast()`), tester cette méthode spécifiquement.
- **Collections :** Vérifier le bon fonctionnement des relations `add...` et `remove...` (ce que nous avons commencé à corriger).
