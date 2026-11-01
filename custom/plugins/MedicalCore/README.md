# MedicalCore

Premier plugin du projet MedicalSupply B2B Commerce.

## Responsabilité

MedicalCore contient uniquement les composants techniques réellement partagés. Il ne doit pas devenir un conteneur de règles métier appartenant aux autres plugins.

## Première implémentation

- Classe principale Shopware : `MedicalCore\MedicalCore`.
- Service : `ProjectInfoProvider`.
- Commande Symfony : `medical:core:info`.
- Déclaration des services : `src/Resources/config/services.xml`.

## Installation locale

Depuis la racine du projet :

```bash
composer dump-autoload
bin/console plugin:refresh
bin/console plugin:install --activate MedicalCore
bin/console cache:clear
bin/console medical:core:info
```

Dans Docker, exécuter les commandes via le service web, par exemple :

```bash
docker compose exec web composer dump-autoload
docker compose exec web bin/console plugin:refresh
docker compose exec web bin/console plugin:install --activate MedicalCore
docker compose exec web bin/console cache:clear
docker compose exec web bin/console medical:core:info
```

Ne pas considérer le plugin comme installé avant d'avoir exécuté et vérifié ces commandes dans l'environnement local.
