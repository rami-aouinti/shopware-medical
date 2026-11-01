# 2. Architecture cible

## Vue d'ensemble

Shopware 6 fournit le socle e-commerce : catalogue, clients, panier, checkout, commandes, administration, DAL et API. Les fonctionnalités spécifiques sont livrées sous forme de plugins modulaires.

Décisions validées :
- Stock multi-entrepôts.
- Approbation conditionnelle des commandes B2B.
- API ERP simulée indépendante.

## Plugins cibles

| Plugin | Responsabilité |
|---|---|
| MedicalCore | Contrats et composants techniques réellement partagés |
| MedicalProductManagement | Extensions du catalogue et documents |
| MedicalSupplierManagement | Fournisseurs, contacts et offres fournisseurs |
| MedicalInventoryManagement | Entrepôts, inventaires, mouvements, réservations |
| MedicalB2BCustomerManagement | Organisations, utilisateurs et rôles |
| MedicalB2BPricing | Contrats et prix B2B |
| MedicalOrderManagement | Approbations et règles métier de commande |
| MedicalDeliveryManagement | Expéditions, colis et tracking |
| MedicalExternalOrders | Import de commandes externes |
| MedicalERPConnector | API ERP, mapping et synchronisation |
| MedicalNotificationCenter | Notifications métier |
| MedicalTaskManagement | Tâches métier et suivi des exécutions |
| MedicalReporting | Indicateurs et exports |

Le découpage est une cible pédagogique. Les frontières seront confirmées selon la cohésion métier et les dépendances réelles. Ne pas créer un plugin pour une simple classe utilitaire.

## Réutilisation du modèle Shopware

Réutiliser les entités natives quand elles couvrent le besoin : product, media, customer, customer_address, customer_group, order, order_line_item, order_delivery, currency, country, rule et promotion. Les extensions ne doivent pas dupliquer les fonctionnalités natives sans justification.

## Règles de dépendance

- Les contrôleurs délèguent aux services applicatifs.
- Les services métier ne dépendent pas des contrôleurs.
- Les appels HTTP externes passent par des clients dédiés.
- Les handlers Messenger appellent des services applicatifs réutilisables.
- Les événements Shopware servent à réagir au framework ; les règles complexes restent dans des services dédiés.
- Les migrations et définitions DAL appartiennent au plugin propriétaire des données.
- Les plugins métier peuvent dépendre de MedicalCore ; MedicalCore ne dépend pas des plugins métier.
- Éviter les dépendances circulaires.

## Stock multi-entrepôts

MedicalInventoryManagement est responsable du stock par emplacement. Le stock natif Shopware doit être alimenté comme projection ou intégré à une stratégie de disponibilité clairement définie. Une seule source de vérité gouverne les quantités. Le choix précis sera validé avant le branchement au checkout.

## Approbation B2B

La décision d'approbation est un état métier séparé des State Machines natives Shopware. Les règles déterminent si une commande requiert une approbation et quel rôle peut décider. Aucune commande en attente ne doit être transmise à l'ERP comme approuvée.

## Intégration ERP

Flux cible : Shopware → message de synchronisation → handler → client ERP → résultat journalisé. Les imports suivent le chemin inverse avec validation et mapping.

Points obligatoires : authentification, timeouts, validation des payloads, idempotence, retries bornés, journalisation sans secrets et file d'échec.

## Décisions à valider pendant les prototypes

- Stratégie de stock Shopware / stock multi-entrepôts.
- Moment de réservation : soumission ou approbation.
- Politique d'allocation entre entrepôts.
- Priorité des prix contractuels et prix par quantité.
- Politique d'appartenance d'un customer à une ou plusieurs organisations.
- Transport Messenger réellement disponible dans Docker.
- Contrat et authentification de l'API ERP simulée.
