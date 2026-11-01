# Atelier technique 01 — Entité Warehouse avec le DAL

## Objectif

La table SQL `medical_warehouse` est créée par la migration du plugin `MedicalInventory`. Cet atelier ajoute la couche Data Abstraction Layer (DAL) de Shopware afin que le framework puisse manipuler cette table avec ses mécanismes standards.

## Fichiers ajoutés

- `src/Core/Content/Warehouse/WarehouseDefinition.php` : déclare le nom de l'entité, sa classe PHP, sa collection et les champs DAL.
- `src/Core/Content/Warehouse/WarehouseEntity.php` : représente un entrepôt chargé par le DAL.
- `src/Core/Content/Warehouse/WarehouseCollection.php` : collection typée d'entités Warehouse.
- `src/Resources/config/services.xml` : enregistre la définition avec le tag `shopware.entity.definition`.

## Correspondance SQL / DAL / PHP

| Colonne SQL | Champ DAL | Propriété PHP | Type |
|---|---|---|---|
| `id` | `IdField` | `id` (hérité de Entity) | UUID binaire |
| `code` | `StringField` | `code` | string |
| `name` | `StringField` | `name` | string |
| `country_code` | `StringField` | `countryCode` | string |
| `is_active` | `BoolField` | `isActive` | bool |
| `created_at` | `CreatedAtField` | géré par Entity/DAL | datetime |
| `updated_at` | `UpdatedAtField` | géré par Entity/DAL | datetime nullable |

## Comment fonctionne l'enregistrement

1. Le plugin charge `Resources/config/services.xml`.
2. Le tag `shopware.entity.definition` signale la définition au système DAL.
3. Shopware enregistre l'entité sous le nom `medical_warehouse`.
4. Le DAL expose le repository correspondant, accessible par le service Symfony `medical_warehouse.repository`.

## Pourquoi trois classes ?

- La Definition décrit le schéma logique et les champs connus du DAL.
- L'Entity porte les données d'un seul entrepôt.
- La Collection représente plusieurs entrepôts avec un type d'entité garanti.

Le repository est fourni par Shopware : il n'est pas nécessaire d'écrire une classe repository SQL personnalisée pour les opérations CRUD standards.

## Chargement

Après l'ajout des services et de la définition, vider le cache Shopware :

```bash
docker compose exec web bin/console cache:clear
```

La migration existante n'est pas rejouée : cette étape ne modifie pas le schéma SQL.

## Périmètre de cette étape

Cette étape enregistre la définition DAL. Elle ne fournit pas encore d'API HTTP, d'écran Administration, de validation métier dédiée ni de service applicatif. Ces éléments seront ajoutés progressivement.

## Étape suivante

Créer un service applicatif qui injecte `EntityRepository` pour :

- créer un entrepôt ;
- rechercher un entrepôt par code ;
- lister les entrepôts avec `Criteria` ;
- modifier son statut actif ;
- supprimer un entrepôt.

Les exemples couvriront `Context`, `Criteria`, `EqualsFilter` et les opérations `search()`, `create()`, `update()` et `delete()`.
