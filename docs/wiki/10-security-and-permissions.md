# 10. Sécurité et permissions

## Principes

- Refuser par défaut et accorder uniquement les permissions nécessaires.
- Contrôler chaque accès métier côté serveur.
- Ne jamais faire confiance aux identifiants, prix, rôles ou montants fournis par le navigateur.
- Ne jamais committer de secrets, tokens, mots de passe ou données réelles.
- Journaliser les opérations sensibles sans exposer les données confidentielles.
- Utiliser les ACL de l'administration Shopware et des contrôles d'accès adaptés au Store API.

## Rôles métier cibles

| Rôle | Catalogue | Stock | Fournisseurs | Commandes | Approbation | Reporting |
|---|---|---|---|---|---|---|
| Super Admin | Tout | Tout | Tout | Tout | Tout | Tout |
| Product Manager | Gérer | Lire | Lire | Lire | — | Lire |
| Supplier Manager | Lire | Lire | Gérer | Lire | — | Lire |
| Warehouse Manager | Lire | Gérer | Lire | Lire | — | Lire |
| Purchasing Manager | Lire | Lire | Gérer | Lire | Selon délégation | Lire |
| B2B Admin | Catalogue autorisé | Lire disponibilité | — | Voir organisation | Selon règle | Organisation |
| B2B Purchaser | Catalogue autorisé | Lire disponibilité | — | Créer et voir ses commandes | — | Organisation |
| B2B Approver | Catalogue autorisé | Lire disponibilité | — | Voir commandes à approuver | Approuver/refuser | Organisation |
| B2B Viewer | Catalogue autorisé | Lire disponibilité | — | Lecture seule | — | Organisation |
| Logistics Manager | Lire | Lire | — | Lire | — | Livraison |

Ce tableau est une matrice fonctionnelle. Les permissions techniques seront traduites en ACL et contrôles applicatifs au moment de chaque plugin.

## Isolation des organisations

- Déterminer l'organisation depuis l'identité authentifiée et son association serveur.
- Vérifier l'appartenance avant de lire ou modifier une commande, un contrat, une adresse ou une notification.
- Ne jamais accepter un business_account_id fourni par le client comme preuve d'autorisation.
- Tester les accès inter-organisations négatifs (IDOR).
- Contrôler les exports et les filtres de recherche selon le même périmètre.

## Administration Shopware

- Déclarer des privilèges ACL dédiés par domaine.
- Cacher une action dans l'interface ne remplace pas le contrôle backend.
- Vérifier les droits dans les contrôleurs et services applicatifs.
- Prévoir des rôles d'administration distincts pour catalogue, stock, fournisseurs et reporting.

## API et intégrations

- Authentifier les appels de l'ERP simulé.
- Valider les schémas de payloads et limiter tailles et formats.
- Utiliser des timeouts, une politique de retry et des limites de débit raisonnables.
- Protéger contre les replays avec clés d'idempotence.
- Ne pas exposer de stack traces en production.
- Masquer les tokens, secrets et données sensibles dans les logs.

## Données et audit

- Ne stocker aucune donnée patient.
- Minimiser les données personnelles de contact.
- Définir une durée de rétention pour logs et notifications.
- Tracer les décisions d'approbation, corrections de stock et opérations administratives.
- Protéger les exports et fichiers téléchargeables par autorisation et URL signées à durée limitée si nécessaire.

## Tests de sécurité attendus

- Un utilisateur d'une organisation ne peut pas consulter une commande d'une autre organisation.
- Un PURCHASER ne peut pas approuver sa propre commande si la politique l'interdit.
- Un VIEWER ne peut pas créer ou modifier une commande.
- Un utilisateur non authentifié ne peut pas appeler les endpoints privés.
- Une requête ERP sans authentification ou signature valide est rejetée.
- Les logs ne contiennent pas de secret.
