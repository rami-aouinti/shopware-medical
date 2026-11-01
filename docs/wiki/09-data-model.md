# 9. Modèle de données détaillé — V1

## Conventions

- Les identifiants personnalisés utilisent le format UUID attendu par le DAL Shopware.
- Les entités personnalisées utilisent les migrations du plugin propriétaire.
- Les prix utilisent DECIMAL avec précision et devise explicite, jamais FLOAT.
- Les dates sont stockées selon les conventions Shopware/UTC et converties à l'affichage.
- Les champs techniques de traduction, versioning et audit sont ajoutés seulement quand le cas métier le nécessite.
- Les noms ci-dessous sont des noms logiques ; les noms physiques de tables seront normalisés dans les migrations.

## Références natives Shopware

| Concept | Référence native |
|---|---|
| Produit | product |
| Média | media |
| Client | customer |
| Adresse client | customer_address |
| Groupe client | customer_group |
| Commande | order |
| Ligne de commande | order_line_item |
| Livraison de commande | order_delivery |
| Règles | rule |
| Promotion | promotion |
| Devise | currency |
| Pays | country |

Ne pas recréer une entité native sans justification documentée.

## Supplier Management

### Supplier
Champs : id, supplier_number, name, email, phone, vat_id, street, zipcode, city, country_id, active, created_at, updated_at.
Contraintes : supplier_number unique ; statut actif/inactif ; historique conservé.

### SupplierContact
Champs : id, supplier_id, first_name, last_name, email, phone, position, is_primary.
Relation : Supplier 1 — N SupplierContact.

### SupplierProduct
Champs : id, supplier_id, product_id, supplier_product_number, purchase_price, currency_id, minimum_order_quantity, lead_time_days, preferred, active.
Relation : Supplier N — N Product via SupplierProduct.
Contrainte : unicité à définir sur supplier_id + product_id + éventuelle référence fournisseur selon le besoin réel.

## Product Management

### ProductExtension
Extension associée à product : manufacturer, medical_reference, package_quantity, package_unit, minimum_order_quantity, storage_conditions, requires_special_handling.
Décision à prendre : extension 1:1 ou champs personnalisés Shopware. Utiliser un custom field si l'information est simple et administrable ; créer une entité dédiée si elle possède son propre cycle de vie ou des relations.

### ProductDocument
Champs : id, product_id, media_id, document_type, title, created_at.
Relation : Product N — N Media si plusieurs documents et réutilisation sont requises ; sinon association 1:N à confirmer.

## Inventory Management

### Warehouse
Champs : id, code, name, street, zipcode, city, country_id, active, is_default.
Contraintes : code unique ; un seul entrepôt par défaut si cette règle est retenue.

### Inventory
Champs : id, warehouse_id, product_id, physical_quantity, reserved_quantity, minimum_quantity, version, updated_at.
Relations : Warehouse 1 — N Inventory ; Product 1 — N Inventory.
Contrainte : UNIQUE(warehouse_id, product_id).
La quantité disponible est calculée comme physical_quantity - reserved_quantity, avec prise en compte des éventuels statuts de stock indisponible.

### StockMovement
Champs : id, inventory_id, movement_type, quantity, reference_type, reference_id, reason, created_by, created_at.
Types : INBOUND, OUTBOUND, ADJUSTMENT, TRANSFER_IN, TRANSFER_OUT, RETURN.
Les mouvements validés sont immuables.

### StockReservation
Champs : id, inventory_id, order_id, product_id, quantity, status, expires_at, created_at, released_at.
Statuts : ACTIVE, CONSUMED, RELEASED, EXPIRED.
Relations vers order et product natifs. Les contraintes d'unicité/idempotence doivent empêcher la réservation répétée du même besoin.

## B2B Customer Management

### BusinessAccount
Champs : id, customer_number, company_name, vat_id, commercial_register, email, phone, status, credit_limit, payment_term_days, created_at.
Contrainte : customer_number unique. Statuts à définir : PENDING, ACTIVE, SUSPENDED.

### BusinessAccountUser
Champs : id, business_account_id, customer_id, role, active, created_at.
Relations : BusinessAccount 1 — N BusinessAccountUser ; Customer 1 — N associations selon la politique multi-organisation.
Contrainte : UNIQUE(business_account_id, customer_id).

### BusinessAddress
Champs : id, business_account_id, customer_address_id, address_type, label, active.
Relation : association entre organisation et adresse Shopware. Ne pas dupliquer les coordonnées si l'adresse native suffit.

## B2B Pricing

### PricingContract
Champs : id, business_account_id, contract_number, name, valid_from, valid_until, status, priority.
Contrainte : contract_number unique dans le périmètre défini.

### ContractPrice
Champs : id, pricing_contract_id, product_id, unit_price, currency_id, valid_from, valid_until.
Contrainte : éviter les périodes concurrentes ambiguës pour le même contrat et produit.

### QuantityPriceTier
Champs : id, product_id, minimum_quantity, maximum_quantity nullable, unit_price, currency_id.
Les bornes doivent être cohérentes et ne pas se chevaucher dans un même contexte tarifaire.

## Order Management

### OrderMetadata
Champs : id, order_id, business_account_id, created_by_customer_id, external_reference, source, approval_required, approval_status, requested_delivery_date, created_at.
Relation 1:1 avec order Shopware à confirmer par contrainte unique order_id.

### ApprovalRule
Champs : id, business_account_id, minimum_order_amount, maximum_order_amount nullable, requires_approval, approver_role, active.
Les conditions pourront évoluer vers un Rule Builder Shopware si la complexité le justifie.

### OrderApproval
Champs : id, order_id, approver_customer_id nullable, status, comment, requested_at, decided_at.
Statuts : PENDING, APPROVED, REJECTED, CANCELLED.
Conserver l'historique des décisions ; ne pas écraser les décisions précédentes sans audit.

## Delivery Management

### Carrier
Champs : id, code, name, tracking_url_template, active.
Contrainte : code unique.

### Shipment
Champs : id, order_id, order_delivery_id, warehouse_id, carrier_id, tracking_number, status, shipped_at, delivered_at.
Relation : Order 1 — N Shipment ; Warehouse 1 — N Shipment.

### ShipmentItem
Champs : id, shipment_id, order_line_item_id, quantity.
Une ligne peut être expédiée en plusieurs shipments ; contrôler le cumul.

### TrackingEvent
Champs : id, shipment_id, event_code, description, event_date, location nullable.
Historique append-only.

## External Orders

### ExternalOrder
Champs : id, source_system, external_order_number, external_customer_reference, order_id nullable, status, payload_hash, received_at, processed_at nullable, error_message nullable.
Contrainte : UNIQUE(source_system, external_order_number).

### ExternalOrderItem
Champs : id, external_order_id, external_product_reference, product_id nullable, quantity, external_unit_price.
Le prix externe est une donnée source à valider, pas une valeur automatiquement fiable.

### ImportLog
Champs : id, external_order_id, level, message, context JSON, created_at.
Ne pas stocker de secrets ou payloads personnels complets dans les logs.

## ERP Connector

### ErpSync
Champs : id, entity_type, entity_id, direction, status, external_id nullable, attempts, last_attempt_at nullable, next_retry_at nullable, created_at.
Directions : INBOUND, OUTBOUND. Statuts : PENDING, PROCESSING, SUCCESS, FAILED, RETRY_SCHEDULED.

### ErpSyncLog
Champs : id, erp_sync_id, endpoint, http_status nullable, duration_ms nullable, message, context JSON, created_at.
Éviter les tokens et données sensibles.

### OutboxMessage
Champs à confirmer au moment de l'implémentation : id, aggregate_type, aggregate_id, event_type, payload, occurred_at, published_at nullable, attempts, status.
Utiliser l'Outbox uniquement si la garantie de publication atomique est nécessaire ; définir la stratégie de nettoyage et d'idempotence.

## Notification Center

### Notification
Champs : id, recipient_customer_id, type, channel, subject, content, status, reference_type, reference_id, sent_at nullable, created_at.
Prévoir les statuts PENDING, SENT, FAILED, READ selon les canaux réellement implémentés.

## Task Management

### BusinessTask
Champs : id, task_type, title, description, status, priority, scheduled_at nullable, assigned_to nullable, created_at.

### TaskExecution
Champs : id, business_task_id, status, started_at nullable, finished_at nullable, error_message nullable.
Distinguer ces tâches métier des Scheduled Tasks natives de Shopware.

## Reporting

Aucune table dédiée n'est requise en V1. Les rapports consomment les données des domaines. Des projections ou tables d'agrégats ne seront ajoutées qu'après mesure des performances.

## Points à valider avant les migrations

1. Stratégie exacte de versioning et associations DAL.
2. Clés étrangères vers les entités Shopware et comportement à la suppression.
3. Politique de multi-organisation d'un customer.
4. Types entiers ou décimaux pour les quantités selon les unités vendues.
5. Stratégie de stock réservé et synchronisation avec le stock natif.
6. Politique de devise, TVA et arrondis.
7. Champs d'audit et rétention des logs.
