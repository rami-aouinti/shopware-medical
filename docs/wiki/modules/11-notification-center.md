# Module 11 — Notification Center

## Mission
Centraliser les notifications déclenchées par les événements métier.

## Canaux
- E-mail en première priorité.
- Notification interne ensuite.
- SMS comme extension future avec fournisseur simulé ou sandbox.

## Fonctionnalités
- Choisir destinataire et canal selon l'événement.
- Rendre un template.
- Envoyer de manière asynchrone.
- Suivre statut, tentatives et erreur.
- Éviter les notifications dupliquées.
- Consulter l'historique autorisé.

## Entité Notification
Champs : recipient_customer_id, type, channel, subject, content, status, reference_type, reference_id, sent_at, created_at. Ajouter une clé de déduplication si les événements peuvent être rejoués.

## Règles
- Les notifications ne bloquent pas le checkout.
- Le destinataire est déterminé côté serveur.
- Les contenus ne contiennent pas de données sensibles inutiles.
- Un retry ne doit pas générer plusieurs envois non souhaités.

## Critères d'acceptation
- Une approbation déclenche la notification attendue.
- Un événement rejoué ne produit pas de doublon selon la clé retenue.
- Les erreurs d'envoi sont visibles et relançables.
- Tests du template, du routage et du handler asynchrone.
