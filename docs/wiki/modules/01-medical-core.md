# Module 01 — MedicalCore

## Mission
Fournir uniquement les composants techniques réellement partagés par plusieurs plugins. MedicalCore ne doit pas devenir un fourre-tout métier.

## Responsabilités
- Configuration commune et validation de configuration.
- Interfaces partagées lorsqu'un contrat est nécessaire.
- Types/valeurs communs stables, sans couplage excessif.
- Conventions de journalisation et corrélation des opérations.
- Helpers techniques réutilisables, si leur duplication est réellement évitée.

## Hors responsabilité
- Ne possède pas les entités Supplier, Inventory, Order ou Pricing.
- Ne contient pas toutes les règles métier.
- N'exécute pas arbitrairement les tâches des autres plugins.

## Conception
- Plugin Shopware installable et désinstallable.
- Services déclarés par Dependency Injection.
- Configuration via le système de plugin Shopware.
- Éviter les dépendances circulaires ; les plugins métier peuvent dépendre de Core, jamais l'inverse.
- N'ajouter un service partagé qu'après identification d'au moins deux consommateurs réels.

## Critères d'acceptation
- Le plugin s'installe, s'active, se désactive et se désinstalle proprement.
- Les services sont autowirés et testables.
- Une configuration invalide produit une erreur explicite.
- Aucune entité métier d'un autre module n'est créée par Core.
