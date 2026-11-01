# Module 05 — B2B Customer Management

## Mission
Représenter les organisations clientes et les utilisateurs Shopware qui agissent pour leur compte.

## Fonctionnalités
- Créer et vérifier une organisation professionnelle.
- Activer, suspendre ou refuser un compte.
- Rattacher plusieurs utilisateurs.
- Définir les rôles ADMIN, PURCHASER, APPROVER et VIEWER.
- Associer des adresses de facturation et livraison.
- Définir conditions de paiement et limite de crédit.
- Isoler les données entre organisations.

## Entités
- BusinessAccount : customer_number, company_name, vat_id, commercial_register, contacts, status, credit_limit, payment_term_days.
- BusinessAccountUser : business_account_id, customer_id, role, active.
- BusinessAddress : business_account_id, customer_address_id, address_type, label, active.

## Intégration Shopware
Réutiliser customer, customer_address et customer_group. BusinessAccount est une organisation métier, pas un remplacement de customer. La politique autorisant un customer à appartenir à une ou plusieurs organisations sera tranchée avant migration.

## Règles
- L'organisation doit être active pour les achats.
- Chaque opération vérifie l'appartenance côté serveur.
- Un rôle ne donne des droits que dans son organisation.
- Un identifiant d'organisation envoyé par le navigateur n'est jamais une preuve d'accès.

## Critères d'acceptation
- Créer une organisation et y rattacher plusieurs customers.
- Appliquer les droits selon le rôle.
- Empêcher un utilisateur de lire une commande d'une autre organisation.
- Tester création, suspension, changement de rôle, adresses et accès inter-organisation.
