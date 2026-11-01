# 2. Architecture cible

## Vue d'ensemble

Shopware 6 fournit le socle e-commerce : catalogue, clients, panier, checkout, commandes, administration, DAL et API. Les fonctionnalités spécifiques sont livrées sous forme de plugins.

## Découpage proposé

| Plugin | Responsabilité |
|---|---|
| SupplierManagement | Fournisseurs et références fournisseur |
| ProductManagement | Données métier complémentaires au catalogue |
| InventoryManagement | Disponibilité, seuils et mouvements |
| B2BCustomerManagement | Comptes professionnels et validation |
| B2BPricing | Conditions tarifaires B2B |
| OrderManagement | Workflow de validation et règles de commande |
| DeliveryManagement | Expéditions, tracking, tâches et notifications |
| ExternalOrders | Commandes issues de canaux externes |
| ERPConnector | Adaptation et synchronisation ERP |
| NotificationCenter | Notifications métier |
| Reporting | Indicateurs et exports |

Ce découpage est une cible pédagogique. Nous éviterons de créer artificiellement un plugin pour chaque petite classe : les frontières seront validées par la cohésion métier et les dépendances.

## Règles de dépendance

- Les contrôleurs délèguent aux services applicatifs.
- Les services métier ne dépendent pas des contrôleurs.
- Les appels HTTP externes passent par des clients dédiés.
- Les handlers Messenger appellent des services applicatifs réutilisables.
- Les événements Shopware servent à réagir au framework ; les règles complexes restent dans des services dédiés.
- Les migrations et définitions DAL appartiennent au plugin propriétaire des données.

## Intégration ERP

Flux cible : Shopware → message de synchronisation → handler → client ERP → résultat journalisé. Les imports suivent le chemin inverse avec validation et mapping.

Points obligatoires : authentification, timeouts, pagination, validation des payloads, idempotence, retries bornés, journalisation sans secrets et file d'échec.

## Décisions à prendre pendant l'audit

- Version exacte de Shopware et PHP.
- Mode d'installation et services Docker.
- Stratégie de tests disponible.
- API Admin ou Store API selon le cas d'usage.
- Transport Messenger et infrastructure réellement disponible.
