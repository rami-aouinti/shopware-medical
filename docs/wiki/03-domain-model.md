# 3. Domaines métier

## Fournisseur

Un fournisseur possède une identité, des coordonnées, un statut et des références liées aux produits. Un produit peut être proposé par plusieurs fournisseurs.

## Produit médical

Le catalogue Shopware reste la source des données e-commerce standard. Le plugin métier peut ajouter des attributs comme la référence fabricant, la référence fournisseur, l'unité de vente, les informations de conditionnement et les documents techniques. Les champs exacts seront décidés avant la migration.

## Stock

Le stock métier peut nécessiter des seuils, des mouvements, des réservations et une source externe. Il faudra distinguer le stock Shopware affiché au client de la quantité réellement disponible dans l'ERP.

## Client B2B et tarification

Un compte professionnel peut être associé à un groupe, des conditions de paiement et des règles tarifaires. Les prix doivent être calculés côté serveur et ne jamais dépendre d'une valeur fournie par le navigateur.

## Commande et livraison

La commande Shopware est la référence transactionnelle e-commerce. Les données de livraison étendues, colis, numéros de suivi, synchronisations et tâches logistiques seront ajoutées sans dupliquer inutilement les entités natives.

## Commande externe

Une commande provenant d'un marketplace ou d'un partenaire doit conserver son identifiant externe, son canal d'origine, son statut de synchronisation et son historique. Les imports doivent être idempotents.

## Questions métier à trancher

- Un produit peut-il avoir plusieurs fournisseurs actifs ?
- Comment arbitrer plusieurs sources de stock ?
- Quels clients doivent faire valider leurs commandes ?
- Les prix sont-ils nets HT, TTC, ou dépendants du groupe de clients ?
- Quelles transitions de commande sont autorisées ?
- Quelle application est maître pour chaque donnée ?
