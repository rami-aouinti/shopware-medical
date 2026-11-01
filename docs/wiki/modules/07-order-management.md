# Module 07 — Order Management

## Mission
Étendre le cycle de commande Shopware avec les règles d'approbation B2B, les références métier et les contrôles de commande.

## Fonctionnalités
- Créer une commande à partir du checkout Shopware.
- Associer organisation et utilisateur créateur.
- Évaluer les règles d'approbation.
- Demander, approuver ou refuser une commande.
- Historiser les décisions.
- Déclencher réservation et synchronisation selon les transitions.
- Gérer annulation et libération des réservations.

## Données
- Réutiliser order, order_line_item, order_transaction et les State Machines Shopware.
- OrderMetadata : order, organisation, créateur, référence externe, source, approval_required, approval_status, date demandée.
- ApprovalRule : organisation, seuils, rôle approbateur, actif.
- OrderApproval : commande, approbateur, statut, commentaire, dates.

## Règles
- Ne pas dupliquer la commande native.
- État de validation B2B distinct de l'état natif Shopware.
- Commande soumise à approbation non transmise comme approuvée à l'ERP.
- Seul un approbateur autorisé peut décider.
- Historiser les décisions.
- Définir précisément quand le stock est réservé : à la soumission, à l'approbation ou à une étape configurable. La V1 devra retenir une seule politique documentée.

## Critères d'acceptation
- Une commande sous le seuil suit le parcours automatique.
- Une commande au-dessus du seuil attend l'approbation.
- Un PURCHASER ne peut pas approuver s'il ne possède pas ce droit.
- Le refus déclenche la politique définie pour la commande et ses réservations.
- Tests de transitions, droits, transactions et événements.
