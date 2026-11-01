# 8. Règles métier

Les règles ci-dessous constituent la version 1. Elles seront référencées par identifiant dans les issues et les tests.

## Catalogue et fournisseurs

- BR-001 : un produit doit être actif et disponible à la vente pour être ajouté à une commande.
- BR-002 : un produit peut être associé à plusieurs fournisseurs.
- BR-003 : une association produit-fournisseur active conserve la référence fournisseur, le prix d'achat, la devise et le délai.
- BR-004 : la désactivation d'un fournisseur ne supprime pas l'historique d'achat.

## Stock multi-entrepôts

- BR-010 : chaque inventaire est unique pour le couple entrepôt-produit.
- BR-011 : quantité disponible = quantité physique - quantité réservée - éventuelles quantités indisponibles explicitement modélisées.
- BR-012 : chaque modification de quantité physique ou réservée crée un mouvement historique append-only et met à jour l'inventaire dans une même transaction.
- BR-013 : les mouvements validés ne sont pas modifiés ni supprimés ; une correction crée un mouvement compensatoire.
- BR-014 : un transfert crée une sortie et une entrée liées à une même opération.
- BR-015 : une réservation ne peut pas rendre le stock disponible négatif ; une annulation libère uniquement la quantité non expédiée.
- BR-016 : la réservation est atomique, protégée contre les mises à jour concurrentes et incluse dans la transaction de persistance de la commande.
- BR-017 : l'allocation ignore les entrepôts inactifs ; elle privilégie un entrepôt pouvant satisfaire toute la ligne de commande, choisi par priorité croissante, puis par stock disponible décroissant ; si aucun entrepôt ne suffit seul, la quantité est répartie selon le même ordre ; si le stock cumulé est insuffisant, aucune réservation n'est créée.
- BR-018 : le stock multi-entrepôts est la source de vérité pour les produits gérés par MedicalInventory ; les produits sans ligne de stock MedicalInventory restent gérés par le stock natif Shopware. Le stock natif agrégé reste cohérent avec les réservations de commande pour les produits en mode closeout.

## Comptes B2B et accès

- BR-020 : un utilisateur doit être actif et rattaché à une organisation active pour agir au nom de celle-ci.
- BR-021 : un utilisateur ne peut agir que dans le périmètre de son organisation.
- BR-022 : les rôles ADMIN, PURCHASER, APPROVER et VIEWER déterminent les opérations autorisées.
- BR-023 : l'appartenance à une organisation est vérifiée côté serveur sur chaque opération sensible.

## Prix et contrats

- BR-030 : les prix sont calculés côté serveur ; les montants envoyés par le navigateur ne sont jamais considérés comme fiables.
- BR-031 : un contrat doit être actif et dans sa période de validité pour être appliqué.
- BR-032 : une commande conserve un instantané des prix, taxes, devise et remises appliqués au moment de sa création.
- BR-033 : la priorité entre prix contractuel, prix par quantité, prix client et prix standard est déterministe et testée.
- BR-034 : les règles de cumul des remises sont explicites ; aucun cumul implicite n'est autorisé.

## Commandes et approbations

- BR-040 : une commande Shopware reste l'enregistrement e-commerce de référence.
- BR-041 : la nécessité d'approbation dépend des règles actives de l'organisation et du contexte de la commande.
- BR-042 : l'état d'approbation B2B est distinct de l'état natif de la commande Shopware.
- BR-043 : une commande en attente d'approbation ne peut pas être envoyée à l'ERP comme commande approuvée.
- BR-044 : seul un utilisateur autorisé peut approuver ou refuser une commande.
- BR-045 : une décision d'approbation est historisée avec l'acteur, la date et un commentaire éventuel.
- BR-046 : les transitions de commande et d'approbation sont validées côté serveur.

## Commandes externes et ERP

- BR-050 : une commande externe est identifiée de manière unique par source_system + external_order_number.
- BR-051 : les traitements d'import et d'export sont idempotents.
- BR-052 : les appels externes utilisent des timeouts et une stratégie de retry bornée.
- BR-053 : les erreurs techniques et métier sont distinguées.
- BR-054 : les logs ne contiennent ni secrets ni données personnelles inutiles.
- BR-055 : un échec ERP ne doit pas provoquer la création de commandes dupliquées lors d'une nouvelle tentative.
- BR-056 : l'API ERP simulée est indépendante du processus Shopware et possède son propre stockage de test.

## Livraison, notification et tâches

- BR-060 : une commande peut être répartie entre plusieurs entrepôts et livrée en plusieurs expéditions.
- BR-061 : la somme des quantités expédiées par allocation ne peut pas dépasser la quantité réservée ; chaque expédition consomme une réservation et diminue le stock physique dans la même transaction.
- BR-062 : les événements de tracking sont historisés en append-only ; une expédition peut passer de SHIPPED à DELIVERED et les quantités partielles restent traçables.
- BR-063 : une notification peut être rejouée sans dupliquer l'effet métier.
- BR-064 : chaque tâche planifiée conserve son statut et ses informations d'exécution.

## Audit et sécurité

- BR-070 : les actions sensibles sont journalisées avec acteur, date, type d'action et référence métier.
- BR-071 : les contrôles d'autorisation sont effectués côté serveur, jamais uniquement dans l'interface.
- BR-072 : les migrations doivent préserver l'intégrité référentielle et être compatibles avec le cycle de vie des plugins.
