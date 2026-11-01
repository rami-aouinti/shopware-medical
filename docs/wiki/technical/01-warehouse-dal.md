# Atelier technique 02 — CRUD des entrepôts avec le DAL

## Objectif

L'atelier 01 a déclaré l'entité `medical_warehouse`. Dans cet atelier, nous utilisons le repository généré par Shopware pour effectuer les opérations CRUD sans écrire de SQL métier.

## Fichiers ajoutés ou modifiés

- `src/Service/WarehouseService.php` : service applicatif qui encapsule les opérations sur le repository DAL.
- `src/Command/WarehouseCrudCommand.php` : commande Symfony pour tester les opérations depuis le terminal.
- `src/Resources/config/services.xml` : injection du repository `medical_warehouse.repository` et enregistrement de la commande.

## Le repository généré par Shopware

Grâce au nom d'entité `medical_warehouse` et à la définition DAL enregistrée, Shopware expose le service :

```php
EntityRepository $warehouseRepository
```

Son identifiant Symfony est `medical_warehouse.repository`. Nous l'injectons dans `WarehouseService` : nous ne créons donc pas notre propre repository SQL.

## Les quatre opérations CRUD

### 1. Create — créer un entrepôt

```php
$this->warehouseRepository->create([
    [
        'id' => Uuid::randomHex(),
        'code' => 'DE-KOELN',
        'name' => 'Köln Central Warehouse',
        'countryCode' => 'DE',
        'isActive' => true,
    ],
], $context);
```

Le DAL attend les noms de propriétés PHP exposées par la définition : `countryCode` et `isActive`, et non les noms SQL `country_code` et `is_active`.

### 2. Read — rechercher un entrepôt

```php
$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('code', 'DE-KOELN'));
$criteria->setLimit(1);

$warehouse = $repository->search($criteria, $context)->first();
```

- `Criteria` décrit ce que nous voulons récupérer.
- `EqualsFilter` ajoute une condition d'égalité.
- `search()` exécute la recherche via le DAL.
- `first()` retourne la première entité ou `null`.

### 3. Update — modifier le statut

```php
$repository->update([
    [
        'id' => $warehouse->getId(),
        'isActive' => false,
    ],
], $context);
```

Pour modifier une entité, Shopware a besoin de son identifiant. Le service retrouve donc d'abord l'entrepôt par son code, puis transmet son ID à `update()`.

### 4. Delete — supprimer un entrepôt

```php
$repository->delete([
    ['id' => $warehouse->getId()],
], $context);
```

La suppression passe également par l'identifiant DAL. Dans un vrai système métier, il faudra décider si un entrepôt déjà référencé par des stocks ou des commandes doit être supprimé, désactivé ou archivé.

## Pourquoi Context ?

`Context` transporte le contexte d'exécution Shopware : notamment la langue, la version, la portée et les informations nécessaires au DAL. Les opérations de lecture et d'écriture reçoivent explicitement ce contexte.

Dans la commande pédagogique, nous utilisons :

```php
$context = Context::createDefaultContext();
```

Dans une requête Store API ou Administration API, Shopware fournit déjà un contexte adapté à la requête.

## Tester depuis Docker

Après avoir récupéré les changements Git :

```bash
cd ~/shopware-medical
git pull --ff-only origin master
docker compose exec web bin/console cache:clear
```

Créer un entrepôt :

```bash
docker compose exec web bin/console medical:inventory:warehouse create --code=DE-KOELN --name="Köln Central Warehouse" --country=DE
```

Lister les entrepôts :

```bash
docker compose exec web bin/console medical:inventory:warehouse list
```

Rechercher par code :

```bash
docker compose exec web bin/console medical:inventory:warehouse find --code=DE-KOELN
```

Désactiver puis réactiver :

```bash
docker compose exec web bin/console medical:inventory:warehouse deactivate --code=DE-KOELN
docker compose exec web bin/console medical:inventory:warehouse activate --code=DE-KOELN
```

Supprimer l'entrepôt de démonstration :

```bash
docker compose exec web bin/console medical:inventory:warehouse delete --code=DE-KOELN
```

La suppression est définitive pour cet enregistrement de démonstration. Ne lance cette dernière commande que lorsque tu veux réellement le supprimer.

## Ce qu'il faut retenir

| Élément | Rôle |
|---|---|
| `EntityRepository` | API DAL fournie par Shopware pour une entité |
| `Context` | Contexte Shopware utilisé par les lectures et écritures |
| `Criteria` | Décrit les critères de recherche |
| `EqualsFilter` | Filtre sur une valeur exacte |
| `search()` | Lit les entités |
| `create()` | Crée des entités |
| `update()` | Modifie des entités |
| `delete()` | Supprime des entités |

## Limites de cet atelier

La commande est un outil de démonstration et non une interface destinée aux utilisateurs finaux. Le service ne contient pas encore toutes les validations métier : unicité fonctionnelle du code, règles de suppression en présence de stocks, permissions et gestion d'erreurs métier plus fine seront traitées dans les ateliers suivants.

## Étape suivante

Ajouter une validation métier propre au domaine Warehouse, puis exposer la lecture et les opérations autorisées à travers une API adaptée à Shopware. Nous aborderons notamment les contraintes DAL, les associations et les permissions avant d'ouvrir les opérations d'écriture.
