# Invoice Parser

Application Symfony qui importe des fichiers de lignes de factures (JSON, CSV) en base de
données. Chaque format de fichier est géré par un parser dédié ; les montants sont stockés
en unités mineures entières (centimes) avec leur devise.

## Prérequis

- Docker
- Docker Compose

## Installation

1. Cloner le projet et lancer les containers.

   ```bash
   docker compose up -d --build
   ```

2. Installer les dépendances PHP.

   ```bash
   docker compose exec app composer install
   ```

3. Créer le schéma de la base.

   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. Créer la base de test (utilisée par les tests fonctionnels).

   ```bash
   docker compose exec app php bin/console doctrine:database:create --env=test --if-not-exists
   docker compose exec app php bin/console doctrine:migrations:migrate --env=test --no-interaction
   ```

## Utilisation

Importer les fichiers du dossier `data/` (par défaut) :

```bash
docker compose exec app php bin/console app:parse
```

Importer un fichier ou un dossier précis :

```bash
docker compose exec app php bin/console app:parse chemin/vers/fichier.csv
```

La commande affiche le résultat fichier par fichier (✓/✗ avec le détail de l'erreur) et
sort en code d'échec dès qu'un fichier n'a pas pu être importé.

## Tests

```bash
docker compose exec app php vendor/bin/phpunit
```

La suite comprend des tests unitaires (parsers, value object `Money`, importer) et des
tests fonctionnels qui exécutent la commande complète contre la base de test — chaque test
tourne dans une transaction rollbackée, la base ressort vierge.
