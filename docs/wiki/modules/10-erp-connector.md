# Module 10 — ERP Connector

## Mission
Fournir une frontière d'intégration stable entre Shopware et une API ERP simulée indépendante.

## Fonctionnalités
- Client HTTP dédié avec authentification.
- Contrats de payload et mapping.
- Synchronisation entrante et sortante.
- Corrélation et journalisation des opérations.
- Timeouts, retry borné, backoff et traitement des erreurs.
- Idempotence et détection de doublons.
- Endpoint de santé pour l'ERP simulé en environnement de développement.

## Entités
- ErpSync : entity_type, entity_id, direction, status, external_id, attempts, dates.
- ErpSyncLog : endpoint, HTTP status, duration, message, contexte expurgé.
- OutboxMessage : à introduire si nécessaire pour garantir publication atomique.

## API ERP simulée
Application indépendante avec stockage séparé. Première surface API à définir : health, produits, stocks, clients et commandes. Elle doit permettre de simuler succès, erreurs 4xx/5xx, délais et répétition de requêtes.

## Règles
- Le checkout ne dépend pas d'un appel synchrone ERP.
- Les messages sont idempotents.
- Retry uniquement pour erreurs temporaires ; les erreurs métier permanentes vont en échec explicite.
- Limiter le nombre de tentatives et prévoir une file d'échec.
- Ne jamais journaliser les secrets.
- Le statut ERP est distinct du statut de commande Shopware.

## Critères d'acceptation
- Commande approuvée synchronisée avec identifiant ERP conservé.
- Panne temporaire puis retry aboutit sans doublon.
- Erreur métier non retentée indéfiniment.
- Tests du client HTTP, du handler Messenger, de l'idempotence et de la reprise.
