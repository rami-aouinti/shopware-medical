# 5. Stratégie de tests et qualité

## Niveaux

- Unitaires : calculs tarifaires, règles de validation, mapping et services métier isolés.
- Intégration : DAL, repositories, migrations, transactions et interactions avec Shopware.
- Fonctionnels : endpoints, permissions, validation des requêtes et réponses.
- Asynchrones : handlers Messenger, retries, idempotence et messages invalides.

## Cas indispensables

- Données absentes ou invalides.
- Ressource externe indisponible ou lente.
- Réponse HTTP inattendue.
- Message traité deux fois.
- Conflit de mise à jour concurrente.
- Utilisateur sans permission.
- Import partiellement réussi.
- Échec après plusieurs tentatives.

## Qualité

La configuration exacte sera choisie après audit. Cibles : PHPUnit, PHPStan ou équivalent, formatage automatisé, analyse des dépendances et CI. Les tests doivent être reproductibles et ne pas appeler des services de production.
