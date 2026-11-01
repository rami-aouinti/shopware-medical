# 4. Méthode de travail

## Cycle d'une fonctionnalité

1. Décrire le besoin et les critères d'acceptation.
2. Identifier les concepts Shopware concernés.
3. Définir les responsabilités et les dépendances.
4. Concevoir les données et les migrations.
5. Implémenter les services, contrôleurs, subscribers ou handlers nécessaires.
6. Ajouter l'administration ou l'API si nécessaire.
7. Tester les cas nominaux, limites et erreurs.
8. Vérifier permissions, sécurité et logs.
9. Mesurer les performances pertinentes.
10. Documenter les choix et les compromis.

## Git

- Une issue par fonctionnalité ou tâche cohérente.
- Une branche par issue, par exemple feature/12-supplier-management.
- Commits petits et explicites.
- Pull request avec description, tests exécutés et captures si l'administration change.
- Ne pas mélanger refactoring non lié et fonctionnalité.

## Definition of Done

- Les critères d'acceptation sont remplis.
- Le code suit les conventions du projet.
- Les validations et permissions sont en place.
- Les tests pertinents passent.
- Aucun secret ni donnée sensible n'est commité.
- Les migrations sont réversibles lorsque cela est possible.
- La documentation est mise à jour.
