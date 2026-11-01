# 1. Vision et périmètre

## Vision

Construire une plateforme B2B de distribution de matériel médical avec Shopware 6. Le projet couvre les problématiques du backend e-commerce : catalogue, clients professionnels, tarification, commandes, livraison, intégration ERP, traitements asynchrones et qualité.

## Profils

- Acheteur professionnel : consulte le catalogue, voit ses conditions commerciales et passe commande.
- Gestionnaire commercial : gère les comptes, les conditions tarifaires et les commandes à valider.
- Gestionnaire logistique : suit les disponibilités, prépare les expéditions et traite les exceptions.
- Administrateur : configure les intégrations, les droits et les paramètres.

## Principes

- Shopware reste le cœur e-commerce.
- Les systèmes externes sont intégrés derrière des services dédiés.
- Les opérations longues ou réessayables sont asynchrones.
- Toute synchronisation doit être observable, traçable et conçue pour éviter les doublons.
- Les règles métier ne doivent pas être dispersées dans les contrôleurs.
- Les tests accompagnent chaque fonctionnalité.

## Hors périmètre initial

- Données réelles de patients ou données de santé.
- Paiements réels et informations bancaires.
- Connexion à un ERP de production.
- Déploiement en production.

Les intégrations seront simulées ou réalisées contre des environnements de test.
