# Module 03 — Supplier Management

## Mission
Gérer les fournisseurs, contacts et offres d'approvisionnement associées aux produits.

## Fonctionnalités
- Créer, modifier, activer et désactiver un fournisseur.
- Gérer plusieurs contacts.
- Associer un fournisseur à plusieurs produits.
- Conserver référence fournisseur, prix d'achat, devise, minimum et délai.
- Identifier un fournisseur préféré sans interdire les autres.
- Consulter l'historique et préparer l'intégration ERP.

## Entités
- Supplier : identité, coordonnées, TVA, adresse, statut.
- SupplierContact : coordonnées et fonction, relation N:1 vers Supplier.
- SupplierProduct : association fournisseur-produit avec référence, prix, devise, MOQ, lead time, preferred, active.

## Règles
- Référence fournisseur interne unique.
- Un produit peut avoir plusieurs fournisseurs.
- Un fournisseur inactif ne peut pas être sélectionné pour un nouvel approvisionnement.
- Les prix d'achat ont une devise et une période de validité si l'historique tarifaire est requis.
- La désactivation ne supprime pas les références historiques.

## Interfaces
- Administration avec recherche, filtres et détail fournisseur.
- DAL associations et Criteria.
- API d'import fournisseur ultérieure via ERP Connector.

## Critères d'acceptation
- Créer un fournisseur avec plusieurs contacts.
- Associer un produit avec une référence fournisseur et un prix d'achat.
- Rejeter une association dupliquée selon la contrainte retenue.
- Tester droits, validation et relations DAL.
