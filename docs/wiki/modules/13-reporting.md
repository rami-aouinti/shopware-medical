# Module 13 — Reporting

## Mission
Fournir des indicateurs opérationnels à partir des données des modules métier.

## Rapports V1
- Ventes : chiffre d'affaires, commandes, panier moyen.
- Stock : quantités physiques, réservées, disponibles et seuils.
- Fournisseurs : volume d'achat, délais et références.
- Clients : activité et volume de commandes.
- Livraison : expéditions, délais et retards.
- Intégration : synchronisations en attente, réussies et échouées.

## Conception
- Commencer par des services de lecture et requêtes DAL/SQL ciblées.
- Filtrer selon les permissions et l'organisation.
- Éviter les requêtes N+1 et charger uniquement les colonnes nécessaires.
- Ajouter index, agrégats ou projections seulement après mesure.
- Définir la période, fuseau horaire et règles de calcul de chaque KPI.

## Entités
Aucune entité dédiée requise initialement. Les tables d'agrégats éventuelles seront ajoutées après preuve d'un besoin de performance.

## Critères d'acceptation
- Les chiffres sont reproductibles et définis sans ambiguïté.
- Les utilisateurs ne voient que les données autorisées.
- Les filtres temporels et pagination fonctionnent.
- Tests de calcul, autorisation et performances sur jeux de données représentatifs.
