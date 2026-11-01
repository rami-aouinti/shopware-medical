# MedicalSupply B2B Commerce

Plateforme e-commerce B2B de matériel médical construite avec Shopware 6 et PHP. Ce dépôt sert de projet fil rouge pour approfondir le développement backend Shopware, les intégrations ERP, les traitements asynchrones, la qualité logicielle et les performances.

> **Contexte pédagogique :** le domaine métier s'inspire de scénarios réalistes de distribution de matériel médical et de l'expérience acquise sur First Medical. Ce projet est une simulation technique indépendante ; il ne représente pas le système interne ni les données confidentielles d'une entreprise.

## Objectifs

- Concevoir des extensions Shopware maintenables, testables et découplées.
- Modéliser fournisseurs, produits, stocks, clients B2B, commandes et livraisons.
- Implémenter tarifs B2B, remises, validation des commandes et conditions de livraison.
- Intégrer un ERP externe via API, imports/exports et webhooks.
- Utiliser Messenger, tâches planifiées, retries, idempotence et gestion des erreurs.
- Tester, profiler et optimiser les composants développés.

## Périmètre fonctionnel cible

| Domaine | Fonctionnalités prévues |
|---|---|
| Catalogue | Informations médicales complémentaires, références fabricant, catégories |
| Fournisseurs | Fiches fournisseurs, références et synchronisation |
| Stocks | Disponibilité, seuils, mouvements et synchronisation |
| Clients B2B | Comptes professionnels, groupes, rôles et validation |
| Tarification | Prix par client/groupe, paliers et conditions commerciales |
| Commandes | Workflow, validation, historique et statuts |
| Livraisons | Dates, colis, tracking, tâches et notifications |
| Commandes externes | Import et suivi de commandes de canaux externes |
| ERP | Connecteur API, mapping, import/export et reprise sur erreur |
| Notifications | E-mails et événements métier |
| Reporting | Indicateurs opérationnels et historiques |

## Architecture envisagée

Le projet sera construit progressivement sous forme de plugins Shopware séparés par responsabilité. Les noms ci-dessous décrivent la cible et ne signifient pas que tous les plugins existent déjà.

- SupplierManagement
- ProductManagement
- InventoryManagement
- B2BCustomerManagement
- B2BPricing
- OrderManagement
- DeliveryManagement
- ExternalOrders
- ERPConnector
- NotificationCenter
- TaskManagement
- Reporting

Les extensions existantes du dépôt source fourni par le développeur, notamment LieferzeitenAdmin et ExternalOrders, serviront de références fonctionnelles lorsque leur code sera disponible dans ce dépôt. Elles ne sont pas présumées installées ici.

## Parcours de réalisation

1. Audit de l'environnement, de la version Shopware et de Docker.
2. Spécifications métier et architecture.
3. Structure du premier plugin et cycle de vie.
4. DAL : entités, définitions, repositories, associations et migrations.
5. Administration, contrôleurs, API et permissions ACL.
6. Logique métier B2B : catalogue, prix, panier, checkout et commandes.
7. Connecteur ERP : contrats, mapping, synchronisation et webhooks.
8. Asynchrone : Messenger, tâches planifiées, retries et dead-letter handling.
9. Tests : PHPUnit, intégration, analyse statique et qualité.
10. Performance, observabilité, documentation et préparation d'entretien.

Chaque fonctionnalité suivra le même cycle : besoin métier → conception → implémentation → persistance → interface/API → tests → optimisation → explication technique.

## Environnement et sécurité

L'environnement Docker et les versions exactes seront documentés après l'audit initial. Ne jamais committer de secrets, tokens, mots de passe ou fichiers .env contenant des valeurs réelles. Utiliser un fichier .env.example avec des valeurs fictives.

## Documentation

La documentation est organisée dans [docs/wiki](docs/wiki/README.md) : vision, architecture, domaines métier, méthode de travail, stratégie de tests et roadmap.

## Statut

Initialisation documentaire — le code fonctionnel sera ajouté étape par étape. Les décisions d'architecture seront révisées à partir de l'audit réel du dépôt et de l'environnement.
