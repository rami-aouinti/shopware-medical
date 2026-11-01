# Module 08 — Delivery Management

## Mission
Suivre la préparation et l'expédition des commandes, y compris les livraisons partielles.

## Fonctionnalités
- Gérer transporteurs et modèles d'URL de tracking.
- Créer une ou plusieurs expéditions par commande.
- Associer les quantités expédiées aux lignes Shopware.
- Enregistrer départ, livraison et incidents.
- Importer les événements de tracking.
- Notifier les utilisateurs des changements.

## Entités
- Carrier : code, nom, tracking_url_template, active.
- Shipment : order, order_delivery, warehouse, carrier, tracking_number, status, shipped_at, delivered_at.
- ShipmentItem : shipment, order_line_item, quantity.
- TrackingEvent : shipment, event_code, description, event_date, location.

## Règles
- Une commande peut être répartie en plusieurs expéditions.
- Le cumul expédié d'une ligne ne dépasse pas la quantité commandée.
- Chaque événement de suivi est historisé.
- Les transitions d'expédition sont validées.
- Ne pas dupliquer les données déjà disponibles dans order_delivery si l'entité native couvre le besoin.

## Critères d'acceptation
- Une commande de 100 unités peut être expédiée en deux lots de 60 et 40.
- Le tracking de chaque colis est indépendant.
- Un cumul de 101 unités expédiées est rejeté.
- Les événements de tracking déclenchent une notification sans doublon.
