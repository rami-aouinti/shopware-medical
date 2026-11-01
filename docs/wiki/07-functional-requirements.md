# 7. Spécifications fonctionnelles

## 7.1 Contexte et objectif

MedicalSupply B2B Commerce est une plateforme pédagogique indépendante de distribution de matériel médical. Shopware 6 reste le socle e-commerce. Le système gère des organisations clientes, plusieurs entrepôts, des conditions tarifaires B2B, des validations conditionnelles, des commandes externes et un ERP simulé.

Aucune donnée patient ou donnée médicale personnelle n'est dans le périmètre.

## 7.2 Acteurs

| Acteur | Besoins principaux |
|---|---|
| Administrateur Shopware | Configurer le catalogue, les plugins et les accès |
| Gestionnaire catalogue | Gérer produits, catégories et documents |
| Gestionnaire fournisseurs | Gérer fournisseurs et références d'achat |
| Gestionnaire d'entrepôt | Gérer entrepôts, stocks, mouvements et transferts |
| Administrateur B2B | Gérer une organisation et ses utilisateurs |
| Acheteur B2B | Consulter les produits et créer des commandes |
| Approbateur B2B | Approuver ou refuser les commandes soumises |
| Logisticien | Préparer les expéditions et suivre les colis |
| Système externe | Échanger commandes, produits et disponibilités via API |

## 7.3 Exigences fonctionnelles

| ID | Exigence | Critère de résultat |
|---|---|---|
| FR-001 | Créer et maintenir un produit | Le produit est enregistré dans le catalogue Shopware |
| FR-002 | Associer plusieurs fournisseurs à un produit | Chaque association conserve référence, prix d'achat et délai |
| FR-003 | Créer plusieurs entrepôts | Chaque entrepôt possède un code unique |
| FR-004 | Enregistrer une entrée/sortie de stock | Quantité et mouvement historique sont cohérents |
| FR-005 | Transférer du stock | Sortie et entrée sont atomiques et traçables |
| FR-006 | Consulter la disponibilité | La disponibilité est calculée selon la politique de stock définie |
| FR-007 | Créer un compte professionnel | Une organisation B2B est identifiable et activable |
| FR-008 | Rattacher plusieurs utilisateurs | Chaque utilisateur possède un rôle dans l'organisation |
| FR-009 | Définir une règle d'approbation | La règle s'applique au périmètre de l'organisation |
| FR-010 | Définir un contrat tarifaire | Contrat, validité et prix sont consultables |
| FR-011 | Calculer un prix B2B | Le prix est calculé côté serveur selon les règles actives |
| FR-012 | Passer une commande | Une commande Shopware conserve les prix et adresses au moment de l'achat |
| FR-013 | Déclencher une approbation conditionnelle | Une demande est créée si les règles l'exigent |
| FR-014 | Réserver le stock | La réservation ne permet pas de sur-réserver une quantité |
| FR-015 | Préparer une expédition partielle ou complète | Les quantités expédiées sont reliées aux lignes de commande |
| FR-016 | Importer une commande externe | Le même identifiant externe ne crée pas plusieurs commandes |
| FR-017 | Synchroniser avec l'ERP simulé | Les échanges sont authentifiés, journalisés et relançables |
| FR-018 | Envoyer une notification | Le destinataire et le canal sont déterminés par une règle métier |
| FR-019 | Exécuter une tâche planifiée | L'exécution et son résultat sont traçables |
| FR-020 | Consulter des rapports | Les indicateurs reposent sur des données persistées cohérentes |

## 7.4 Scénarios d'acceptation

### Stock entrant
Étant donné un inventaire de 100 unités, quand un réceptionnaire enregistre 50 unités, alors le stock physique devient 150 et un mouvement INBOUND de 50 est créé dans la même transaction.

### Transfert
Étant donné 100 unités à Köln et 30 à Düsseldorf, quand 20 unités sont transférées, alors Köln possède 80, Düsseldorf 50 et les mouvements TRANSFER_OUT et TRANSFER_IN sont enregistrés atomiquement.

### Approbation
Étant donné une règle exigeant l'approbation au-dessus de 2 000 EUR, quand un acheteur soumet une commande de 3 000 EUR, alors une approbation PENDING est créée et aucun message de commande approuvée n'est envoyé à l'ERP avant la décision.

### ERP
Étant donné une commande approuvée, quand le service ERP accepte la demande, alors l'identifiant ERP est conservé et la synchronisation passe à SUCCESS. Une répétition de la même requête ne crée pas un second ordre ERP.

### Import externe
Étant donné une commande du système PARTNER avec le numéro EXT-100, quand le même payload est reçu deux fois, alors une seule commande Shopware est créée et le second traitement retrouve le résultat existant.

## 7.5 Hors périmètre initial

- Données patient, dossiers médicaux ou prescriptions.
- Dispositifs médicaux connectés et télémétrie.
- Paiement réel ou transmission réelle à un ERP de production.
- Comptabilité complète et facturation légale.
- Gestion réglementaire certifiée des dispositifs médicaux.

## 7.6 Hypothèses à confirmer avant les fonctionnalités concernées

- Les prix sont stockés avec précision décimale et une devise explicite.
- Les règles de TVA et l'affichage HT/TTC suivent la configuration Shopware.
- Les durées de réservation et les stratégies d'allocation multi-entrepôts seront définies avant le checkout.
- Les rôles et seuils d'approbation seront configurables et non codés en dur.
