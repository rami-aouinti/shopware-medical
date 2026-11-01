# Module 06 — B2B Pricing

## Mission
Calculer les prix applicables selon le produit, l'organisation, le contrat, la quantité, la devise et les règles Shopware.

## Fonctionnalités
- Prix contractuel par client/organisation et produit.
- Prix par quantité.
- Dates de validité.
- Priorité entre contrats et tarifs.
- Promotions et règles Shopware compatibles.
- Conservation de l'instantané tarifaire dans la commande.

## Entités
- PricingContract : organisation, numéro, validité, statut, priorité.
- ContractPrice : contrat, produit, prix unitaire, devise, période.
- QuantityPriceTier : produit, quantité min/max, prix, devise.

## Ordre de calcul
La priorité exacte doit être décidée et testée. Proposition initiale : prix contractuel valide et spécifique > prix par quantité applicable > prix du groupe/client > prix standard Shopware. Les promotions et règles de cumul suivent ensuite une politique explicitement définie.

## Règles
- Calcul côté serveur.
- Contrat actif et période valide.
- Devise et précision décimale obligatoires.
- Paliers sans chevauchement ambigu.
- Prix et taxes appliqués conservés dans la commande.
- Ne pas contourner le calculateur de prix Shopware sans comprendre son pipeline.

## Intégration Shopware
Étudier Rule Builder, prix avancés, promotions, calcul du panier et événements/collectors adaptés à la version installée. Choisir la meilleure extension après un prototype.

## Critères d'acceptation
- Deux organisations obtiennent des prix différents pour le même produit.
- Un contrat expiré n'est pas appliqué.
- Le palier de quantité correct est sélectionné.
- Le prix reste inchangé sur une commande historique après modification du contrat.
- Tests de priorité, arrondis, taxes, devise et cumul de promotions.
