# 3. Domaines métier

Ce document donne une vue synthétique. Le [modèle de données détaillé](09-data-model.md) contient les champs et contraintes. Les [spécifications de modules](modules/README.md) détaillent les comportements.

## Fournisseurs
Supplier possède des contacts et propose des produits via SupplierProduct. L'association porte la référence fournisseur, le prix d'achat, la devise, le minimum et le délai.

## Produits
Le produit Shopware reste le catalogue de référence. MedicalProductManagement ajoute les données spécifiques : référence fabricant, unité de vente, conditionnement, stockage et documents techniques. Les custom fields sont préférés pour les attributs simples ; une entité DAL est utilisée si le cycle de vie ou les associations le justifient.

## Stocks multi-entrepôts
Warehouse contient des Inventory par produit. Chaque Inventory suit quantités physiques, réservées et seuils. StockMovement conserve l'historique immuable ; StockReservation représente les quantités affectées aux commandes. Les transferts sont atomiques et historisés.

## Organisations B2B
BusinessAccount représente l'organisation ; BusinessAccountUser relie des customers Shopware avec des rôles. BusinessAddress référence les customer_address natives. La politique multi-organisation doit être décidée avant la migration.

## Tarification
PricingContract appartient à une organisation. ContractPrice définit le prix d'un produit pour une période. QuantityPriceTier définit les paliers. La priorité entre prix contractuel, quantité et prix standard doit être déterministe.

## Commandes et approbation conditionnelle
La commande Shopware reste la référence transactionnelle. OrderMetadata porte le contexte B2B ; ApprovalRule détermine si une validation est nécessaire ; OrderApproval historise les décisions. L'état d'approbation est distinct de l'état natif de commande.

## Livraisons
Shipment représente un colis ou une expédition et peut être lié à order_delivery. ShipmentItem associe les quantités expédiées aux lignes de commande. TrackingEvent conserve les changements de suivi.

## Commandes externes
ExternalOrder conserve source et numéro externe, les lignes importées et les logs. L'unicité source_system + external_order_number protège contre les doublons.

## ERP simulé
ErpSync et ErpSyncLog suivent les échanges. OutboxMessage sera ajouté si nécessaire pour fiabiliser la publication atomique. L'API ERP est une application indépendante avec son propre stockage.

## Notifications, tâches et reporting
Notification trace les messages métier. BusinessTask et TaskExecution suivent les tâches opérationnelles, sans remplacer Scheduled Tasks Shopware. Reporting commence par des services de lecture, sans tables d'agrégats prématurées.

## Questions à trancher avant implémentation
- Politique de stock natif et allocation multi-entrepôts.
- Moment de réservation et durée d'expiration.
- Appartenance d'un customer à plusieurs organisations.
- Priorité des prix et cumul des remises.
- Politique de paiement, TVA et crédit.
- Transitions exactes des commandes et approbations.
