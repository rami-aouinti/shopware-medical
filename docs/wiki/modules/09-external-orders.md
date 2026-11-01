# Module 09 — External Orders

## Mission
Importer dans Shopware des commandes reçues par téléphone (saisie opérateur), e-mail traité, partenaire ou canal externe via une interface structurée.

## Fonctionnalités
- Recevoir et valider un payload externe.
- Identifier le système source et son numéro de commande.
- Mapper client, produits, quantités et prix.
- Détecter doublons.
- Créer une commande Shopware après validation.
- Suivre import, erreurs et reprises.

## Entités
- ExternalOrder : source_system, external_order_number, customer_reference, order_id nullable, status, payload_hash, timestamps, error.
- ExternalOrderItem : référence produit externe, product_id nullable, quantity, external_unit_price.
- ImportLog : niveau, message, contexte JSON, date.

## Règles
- UNIQUE(source_system, external_order_number).
- Validation stricte des références et quantités.
- Les prix entrants ne sont pas fiables sans validation.
- Idempotence sur réception et création de commande.
- Conserver la provenance de la commande.

## Interfaces
- Endpoint d'import authentifié (contrat à définir).
- Service applicatif d'import indépendant du contrôleur.
- Traitement asynchrone pour les imports volumineux.

## Critères d'acceptation
- Import valide crée une seule commande Shopware.
- Réception répétée retourne le résultat déjà créé.
- Produit ou client inconnu donne une erreur exploitable et journalisée.
- Tests d'idempotence, validation, autorisation et mapping.
