# Module 02 — Product Management

## Mission
Étendre le catalogue Shopware avec les informations spécifiques à la distribution de matériel médical.

## Utilisateurs
Product Manager, administrateur Shopware, acheteur B2B (consultation).

## Fonctionnalités
- Gérer référence fabricant et référence métier.
- Définir unité de vente et conditionnement.
- Définir quantité minimale de commande.
- Gérer informations de stockage et manipulation.
- Associer fiches techniques et documents à la médiathèque Shopware.
- Rechercher/filtrer les produits selon les attributs métier.
- Contrôler activation et visibilité du produit.

## Données
- Réutiliser product, product_category, product_media et media.
- Extension potentielle : manufacturer, medical_reference, package_quantity, package_unit, minimum_order_quantity, storage_conditions, requires_special_handling.
- ProductDocument : lien produit/média, type et titre si les custom fields ne suffisent pas.

## Règles
- Un produit inactif n'est pas commandable.
- Les quantités doivent respecter l'unité et le conditionnement.
- Les documents doivent respecter les autorisations de consultation.
- Ne pas dupliquer les propriétés natives ou custom fields existants.

## Interfaces
- Administration Shopware pour la gestion.
- DAL repository pour lecture/écriture.
- Store API/catalogue pour exposition contrôlée des données.

## Critères d'acceptation
- Un administrateur peut créer un produit et renseigner les attributs métier.
- Un document peut être associé et consulté par un utilisateur autorisé.
- Les validations rejettent les conditionnements incohérents.
- Tests : définition DAL, validation, associations, ACL et lecture Store API.
