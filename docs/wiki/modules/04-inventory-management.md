# Module 04 — Inventory Management

## Mission
Gérer les quantités physiques, réservées et disponibles par produit et par entrepôt.

## Fonctionnalités
- Créer et administrer plusieurs entrepôts.
- Enregistrer réception, sortie, correction et retour.
- Transférer des quantités entre entrepôts.
- Consulter disponibilité par emplacement et total agrégé.
- Définir seuils d'alerte.
- Réserver, consommer, libérer et expirer une réservation.
- Tracer chaque opération et son origine.

## Entités
- Warehouse : code, nom, adresse, statut.
- Inventory : warehouse, product, physical_quantity, reserved_quantity, minimum_quantity, version.
- StockMovement : inventaire, type, quantité, référence, motif, acteur, date.
- StockReservation : inventaire, commande, produit, quantité, statut, expiration.

## Règles critiques
- UNIQUE(warehouse_id, product_id).
- Disponible = physique - réservé (et autres indisponibilités modélisées).
- Toute mutation et son mouvement historique sont atomiques.
- Mouvements validés immuables ; correction par mouvement compensatoire.
- Transfert : TRANSFER_OUT + TRANSFER_IN dans une transaction.
- Réservation atomique et protégée contre concurrence.
- Définir la stratégie d'allocation entre entrepôts avant le branchement au checkout.

## Intégration Shopware
Le stock natif Shopware et le stock multi-entrepôts doivent avoir une relation explicite. Décider si le stock Shopware est une projection agrégée pour le storefront ou si une autre stratégie de disponibilité est retenue. Une seule source de vérité doit gouverner les quantités.

## Critères d'acceptation
- Réception de 50 sur un stock de 100 donne 150 et un mouvement INBOUND.
- Transfert de 20 de Köln vers Düsseldorf met à jour les deux inventaires et crée deux mouvements.
- Deux réservations concurrentes ne peuvent pas dépasser la disponibilité.
- Annulation/libération restaure la disponibilité sans supprimer l'historique.
- Tests transactionnels, concurrence, idempotence et intégrité.
