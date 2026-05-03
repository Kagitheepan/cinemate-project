# Plan de Correction des Données de Durée (Runtime)

L'intégration de l'API OMDb n'a pas montré de résultats probants car la base de données contient probablement des "restes" des anciennes données (durées à 120 par défaut) et car les titres français (TMDB) correspondent mal aux recherches OMDb (souvent en anglais).

## Problèmes identifiés
1. **Titres non concordants** : L'API OMDb fonctionne beaucoup mieux avec les titres originaux (souvent anglais). Actuellement, nous lui envoyons les titres français.
2. **Données existantes "polluées"** : Les films déjà importés avec une durée de `120` ne sont pas forcément mis à jour car le script ne détecte pas qu'il s'agit d'une donnée falsifiée.
3. **Persistance des 120** : Le backend ou le frontend utilisent peut-être encore des valeurs par défaut si la donnée en base est `NULL` ou `0`.

## Actions Correctives Immédiates

### Étape 1 : Nettoyage SQL des données falsifiées
Je vais exécuter une commande pour remettre à `NULL` toutes les durées égales à `120` ou `0` dans la base de données. Cela forcera le script d'importation à considérer ces films comme "incomplets".

### Étape 2 : Amélioration du Matching OMDb (Original Title)
Je vais modifier le `TmdbService` et le `ImportMoviesCommand` pour :
1. Récupérer le `original_title` (généralement en anglais) via TMDB.
2. Utiliser ce titre original pour interroger OMDb, ce qui garantit un taux de succès proche de 100%.

### Étape 3 : Script de "Forçage" d'Importation
Je vais modifier la logique de `ImportMoviesCommand` pour qu'elle mette à jour systématiquement la durée si celle-ci est absente, même pour les films déjà présents en base.

### Étape 4 : Validation par Logs
Je vais ajouter des messages plus clairs dans la console pour voir exactement quelle API (TMDB ou OMDB) fournit la donnée pour chaque film.

## Procédure d'exécution
Une fois les modifications appliquées, vous devrez lancer :
```bash
# 1. Mise à jour de la base pour autoriser les changements
docker exec cinemate-back php bin/console doctrine:schema:update --force

# 2. Relancement de l'import forcé (sur 10 pages pour un catalogue large)
docker exec cinemate-back php bin/console app:import-movies --pages=10
```
