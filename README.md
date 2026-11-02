# MedicalSupply B2B Commerce

Plateforme e-commerce B2B de matériel médical construite avec Shopware 6 et PHP. Ce dépôt sert de projet fil rouge pour approfondir le développement backend Shopware, les intégrations ERP, les traitements asynchrones, la qualité logicielle et les performances.

> **Contexte pédagogique :** le domaine métier s'inspire de scénarios réalistes de distribution de matériel médical et de l'expérience acquise sur First Medical. Ce projet est une simulation technique indépendante ; il ne représente pas le système interne ni les données confidentielles d'une entreprise.

## Décisions métier V1

- Gestion de plusieurs entrepôts avec stock indépendant.
- Validation conditionnelle des commandes selon les règles de l'organisation, le rôle et le contexte.
- API ERP simulée, indépendante de Shopware.
- Shopware reste le socle e-commerce et la commande native reste la référence transactionnelle.
- Aucun patient ni aucune donnée médicale personnelle dans le périmètre.

## Objectifs

- Concevoir des extensions Shopware maintenables, testables et découplées.
- Modéliser fournisseurs, produits, stocks, clients B2B, commandes et livraisons.
- Implémenter tarifs B2B, remises, validation des commandes et conditions de livraison.
- Intégrer un ERP simulé via API, imports/exports et webhooks.
- Utiliser Messenger, tâches planifiées, retries, idempotence et gestion des erreurs.
- Tester, profiler et optimiser les composants développés.

## Périmètre fonctionnel cible

| Domaine | Fonctionnalités prévues |
|---|---|
| Catalogue | Informations métier complémentaires, références fabricant, conditionnement, documents |
| Fournisseurs | Fiches fournisseurs, contacts, références et conditions d'achat |
| Stocks | Multi-entrepôts, disponibilités, réservations, mouvements et transferts |
| Clients B2B | Organisations, utilisateurs, rôles, adresses et conditions de paiement |
| Tarification | Contrats, prix par quantité, règles et remises |
| Commandes | Workflow, approbation conditionnelle, historique et statuts |
| Livraisons | Expéditions partielles, colis, tracking et événements |
| Commandes externes | Import idempotent de commandes de canaux externes |
| ERP | API simulée, mapping, synchronisation, retries et journalisation |
| Notifications | E-mails et notifications liées aux événements métier |
| Tâches | Planification, exécution, reprise et historique |
| Reporting | Indicateurs ventes, stocks, fournisseurs, clients et livraisons |

## Architecture envisagée

Le projet sera construit progressivement sous forme de plugins Shopware séparés par responsabilité. Les noms ci-dessous décrivent la cible et ne signifient pas que tous les plugins existent déjà.

- MedicalCore
- MedicalProductManagement
- MedicalSupplierManagement
- MedicalInventoryManagement
- MedicalB2BCustomerManagement
- MedicalB2BPricing
- MedicalOrderManagement
- MedicalDeliveryManagement
- MedicalExternalOrders
- MedicalERPConnector
- MedicalNotificationCenter
- MedicalTaskManagement
- MedicalReporting

Nous éviterons les dépendances circulaires et ne placerons dans MedicalCore que les contrats ou composants réellement partagés.

## Parcours de réalisation

1. Audit de l'environnement et Docker.
2. Spécifications métier, modèle de données et sécurité.
3. MedicalCore et cycle de vie du premier plugin.
4. DAL, entités personnalisées, associations et migrations.
5. Administration, contrôleurs, API et ACL.
6. Catalogue, fournisseurs et inventaire multi-entrepôts.
7. Clients B2B, prix et approbation conditionnelle.
8. Commandes, livraisons et commandes externes.
9. API ERP simulée et synchronisation asynchrone.
10. Notifications, tâches, tests, sécurité et performances.

Chaque fonctionnalité suit le cycle : besoin métier → conception → implémentation → persistance → interface/API → tests → optimisation → explication technique.

## Environnement et sécurité

Les versions exactes et les services Docker sont documentés à partir de l'audit réel. Ne jamais committer de secrets, tokens, mots de passe ou fichiers .env contenant des valeurs réelles. Utiliser un fichier .env.example avec des valeurs fictives et conserver les secrets uniquement localement.

## Documentation

- [Index de la documentation](docs/wiki/README.md)
- [Exigences fonctionnelles](docs/wiki/07-functional-requirements.md)
- [Règles métier](docs/wiki/08-business-rules.md)
- [Modèle de données détaillé](docs/wiki/09-data-model.md)
- [Sécurité et permissions](docs/wiki/10-security-and-permissions.md)
- [Spécifications détaillées des 13 modules](docs/wiki/modules/README.md)

## Statut

Phase de spécifications V1 documentée. Les documents décrivent les décisions et la cible de conception ; les plugins métier seront implémentés progressivement et validés par des tests.

## Observability

The independent Docker observability stack (Grafana, Prometheus, Loki, Grafana Alloy, OpenTelemetry Collector, Tempo, cAdvisor and Node Exporter) is documented in [monitoring/README.md](monitoring/README.md). It uses [compose.monitoring.yaml](compose.monitoring.yaml) as an overlay and leaves the Shopware CLI-managed [compose.yaml](compose.yaml) untouched. Create the local `.env.monitoring` file before starting the stack.
