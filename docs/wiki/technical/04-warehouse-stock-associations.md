# Atelier technique 04 — Associations du DAL : Warehouse et Product

## Objectif

Dans les ateliers précédents, `Warehouse` était une entité autonome. Un système d'inventaire réel doit toutefois savoir quels produits sont stockés dans chaque entrepôt.

Nous introduisons une entité intermédiaire `WarehouseStock` :

- un entrepôt possède plusieurs lignes de stock ;
- chaque ligne de stock appartient à un seul entrepôt ;
- chaque ligne de stock référence un produit Shopware ;
- une ligne conserve la quantité physique et la quantité réservée.

## Modèle relationnel

```text
Warehouse (1) ───────── (N) WarehouseStock (N) ───────── (1) Product
```

Exemple métier :

| Entrepôt | Produit | Quantité | Réservée | Disponible |
|---|---|---:|---:|---:|
| DE-KOELN | MASK-FFP2 | 500 | 35 | 465 |
| FR-PARIS_01 | MASK-FFP2 | 200 | 20 | 180 |

La quantité disponible est calculée : `quantity - reservedQuantity`. Elle n'est pas stockée séparément afin d'éviter de maintenir deux valeurs qui pourraient diverger.

## Pourquoi une entité intermédiaire ?

Une relation directe ManyToMany entre Warehouse et Product ne suffirait pas. La relation porte elle-même des informations métier : quantité, quantité réservée et potentiellement plus tard emplacement, seuil de réapprovisionnement ou date du dernier inventaire.

Dans ce cas, `WarehouseStock` est une vraie entité métier, et non une simple table de liaison technique.

## Les trois concepts DAL utilisés

### 1. OneToManyAssociationField

Dans `WarehouseDefinition` :

```php
new OneToManyAssociationField(
    'stocks',
    WarehouseStockDefinition::class,
    'warehouse_id'
)
```

Cela indique qu'un entrepôt peut être associé à plusieurs lignes de stock. La clé étrangère se trouve du côté `medical_warehouse_stock`.

L'entité `WarehouseEntity` expose donc une propriété `stocks` de type `WarehouseStockCollection`.

### 2. FkField

Dans `WarehouseStockDefinition` :

```php
new FkField('warehouse_id', 'warehouseId', WarehouseDefinition::class)
new FkField('product_id', 'productId', ProductDefinition::class)
```

Un `FkField` décrit la valeur de clé étrangère stockée dans la table et la définition de l'entité référencée.

### 3. ManyToOneAssociationField

Toujours dans `WarehouseStockDefinition` :

```php
new ManyToOneAssociationField(
    'warehouse',
    'warehouse_id',
    WarehouseDefinition::class,
    'id',
    false
)
```

La ligne de stock peut ainsi charger son entrepôt. La même logique est utilisée pour l'association vers le produit.

Le dernier argument `false` désactive le chargement automatique de l'association. On la charge explicitement avec `Criteria::addAssociation()` lorsqu'on en a besoin.

## Pourquoi ReferenceVersionField pour Product ?

Les produits Shopware sont versionnés. Une référence vers un produit doit donc identifier non seulement son `product_id`, mais aussi son `product_version_id`.

La définition utilise :

```php
(new ReferenceVersionField(ProductDefinition::class))
    ->addFlags(new Required())
```

La migration crée les deux colonnes et une clé étrangère composite vers `product(id, version_id)`.

Ne supprime pas cette référence de version : elle fait partie du modèle de données des entités versionnées de Shopware.

## Contraintes SQL importantes

La migration crée :

- une clé étrangère vers `medical_warehouse`, avec suppression en cascade ;
- une clé étrangère composite vers `product` ;
- une contrainte UNIQUE sur `warehouse_id + product_id + product_version_id`, pour empêcher deux lignes de stock pour le même produit dans le même entrepôt ;
- une contrainte CHECK garantissant que la quantité réservée ne dépasse pas la quantité physique.

La règle de cohérence est donc :

```text
0 <= reservedQuantity <= quantity
availableQuantity = quantity - reservedQuantity
```

La validation applicative des quantités sera ajoutée dans le service de gestion du stock. Les contraintes de base de données restent une protection supplémentaire.

## Fichiers ajoutés ou modifiés

- `src/Migration/Migration1793491260CreateWarehouseStockTable.php`
- `src/Core/Content/WarehouseStock/WarehouseStockEntity.php`
- `src/Core/Content/WarehouseStock/WarehouseStockCollection.php`
- `src/Core/Content/WarehouseStock/WarehouseStockDefinition.php`
- `src/Core/Content/Warehouse/WarehouseEntity.php`
- `src/Core/Content/Warehouse/WarehouseDefinition.php`
- `src/Resources/config/services.xml`

## Mise à jour locale

```bash
cd ~/shopware-medical
git pull --ff-only origin master
docker compose exec web composer dump-autoload
docker compose exec web bin/console plugin:refresh
docker compose exec web bin/console database:migrate --all
docker compose exec web bin/console cache:clear
```

## Vérifications de cette première partie

1. La migration doit créer la table `medical_warehouse_stock`.
2. La table doit contenir `warehouse_id`, `product_id` et `product_version_id`.
3. La commande suivante doit toujours fonctionner :

```bash
docker compose exec web bin/console medical:inventory:warehouse list
```

4. Le conteneur Symfony doit compiler les définitions sans erreur après le nettoyage du cache.

Cette première partie met en place le modèle relationnel DAL. La prochaine étape pratique consiste à écrire un service et une commande CLI pour créer une ligne de stock à partir du code d'entrepôt et du numéro de produit, puis à charger les associations avec `Criteria::addAssociation('stocks.product')`.
