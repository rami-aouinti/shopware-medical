# Atelier technique 03 — Validation métier des entrepôts

## Objectif

L'atelier 02 a rendu les opérations CRUD disponibles via le repository DAL. Nous ajoutons maintenant une validation applicative pour empêcher l'enregistrement de données incohérentes.

L'objectif architectural est important : la commande CLI ne doit pas être l'unique endroit où les règles sont contrôlées. Le service applicatif doit garantir ces règles quel que soit son appelant (commande, contrôleur API ou autre service).

## Règles de validation V1

| Champ | Règle | Normalisation |
|---|---|---|
| `code` | 2 à 64 caractères : lettres majuscules, chiffres, `_` et `-` | trim + majuscules |
| `name` | obligatoire, maximum 255 caractères | trim |
| `countryCode` | exactement deux lettres | trim + majuscules |
| unicité du code | un seul entrepôt par code | comparaison après normalisation |

Exemples de codes acceptés : `DE-KOELN`, `FR-PARIS_01`, `WH001`.

Cette première validation vérifie le format de deux lettres du pays. Elle ne vérifie pas encore que le code correspond à un pays réellement enregistré dans Shopware. Nous traiterons la relation avec les entités Country dans un atelier consacré aux associations.

## Où placer les validations ?

La commande Symfony reçoit les options du terminal, mais elle ne porte pas les règles métier. Elle transmet les valeurs à `WarehouseService`.

Le service appelle `WarehouseValidator`, qui :
1. nettoie les espaces superflus ;
2. normalise le code et le pays en majuscules ;
3. contrôle le format et les longueurs ;
4. retourne les données normalisées ou lève une `InvalidArgumentException`.

Le service vérifie ensuite si le code existe déjà avant de créer l'entrepôt.

## Pourquoi conserver aussi l'index UNIQUE SQL ?

La migration initiale crée déjà :

```sql
UNIQUE KEY `uniq.medical_warehouse.code` (`code`)
```

La vérification dans le service donne un message métier compréhensible. Mais deux requêtes concurrentes pourraient toutes les deux vérifier l'absence du code avant d'essayer de l'insérer.

L'index unique en base reste donc la protection finale contre les doublons. La validation applicative et la contrainte SQL sont complémentaires.

## Fichiers

- `src/Service/WarehouseValidator.php` : validation et normalisation des données.
- `src/Service/WarehouseService.php` : applique le validateur et contrôle l'unicité avant création.
- `src/Command/WarehouseCrudCommand.php` : transmet les entrées au service et affiche les erreurs.
- `src/Resources/config/services.xml` : enregistre et injecte le validateur.

## Mise à jour locale

Récupérer les commits :

```bash
cd ~/shopware-medical
git pull --ff-only origin master
docker compose exec web bin/console cache:clear
```

## Scénarios de test

### 1. Vérifier les entrepôts existants

L'entrepôt de démonstration `DE-KOELN` doit rester présent :

```bash
docker compose exec web bin/console medical:inventory:warehouse find --code=DE-KOELN
```

### 2. Créer avec des espaces et des minuscules

```bash
docker compose exec web bin/console medical:inventory:warehouse create --code=" fr-paris_01 " --name="  Paris Warehouse  " --country="fr"
```

Résultat attendu : code `FR-PARIS_01`, nom `Paris Warehouse`, pays `FR`.

### 3. Refuser un code invalide

```bash
docker compose exec web bin/console medical:inventory:warehouse create --code="DE KOELN" --name="Invalid Warehouse" --country=DE
```

Résultat attendu : message d'erreur indiquant le format accepté du code. Aucun enregistrement ne doit être créé.

### 4. Refuser un nom vide

```bash
docker compose exec web bin/console medical:inventory:warehouse create --code=DE-EMPTY --name="   " --country=DE
```

Résultat attendu : message indiquant que le nom est obligatoire. Aucun enregistrement ne doit être créé.

### 5. Refuser un pays au mauvais format

```bash
docker compose exec web bin/console medical:inventory:warehouse create --code=DE-INVALID --name="Invalid Country" --country=DEU
```

Résultat attendu : message indiquant que le code pays doit contenir exactement deux lettres.

### 6. Refuser un doublon, même avec une casse différente

```bash
docker compose exec web bin/console medical:inventory:warehouse create --code=de-koeln --name="Duplicate Köln" --country=DE
```

Résultat attendu : le service normalise le code en `DE-KOELN`, détecte l'entrepôt existant et refuse la création.

### 7. Vérifier le résultat

```bash
docker compose exec web bin/console medical:inventory:warehouse list
```

L'entrepôt `FR-PARIS_01` doit apparaître. Les scénarios invalides ne doivent pas créer de lignes.

## Points importants à retenir

- Une validation placée uniquement dans un contrôleur ou une commande peut être contournée par un autre appelant.
- Le service applicatif est un bon point central pour les règles d'écriture de ce cas d'usage.
- Normaliser avant de vérifier l'unicité évite les doublons liés à la casse et aux espaces.
- La validation applicative améliore le message d'erreur ; la contrainte SQL garantit l'intégrité même en cas de concurrence.
- La validation du format d'un code pays n'est pas équivalente à la vérification de son existence dans le référentiel Shopware.

## Limites et prochaines étapes

La commande CLI reste un outil pédagogique. Les erreurs de contraintes SQL concurrentes ne sont pas encore converties en erreurs métier dédiées. Les validations seront également à réutiliser lors de l'ajout d'une API.

La suite logique sera d'étudier les associations DAL, notamment entre un entrepôt et ses stocks, puis entre un fournisseur et ses références fournisseur.
