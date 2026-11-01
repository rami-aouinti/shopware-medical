# Module 12 — Task Management

## Mission
Orchestrer les tâches métier et rendre leurs exécutions observables.

## Fonctionnalités
- Définir une tâche métier.
- Programmer une exécution.
- Suivre état, priorité, dates et résultat.
- Relancer une tâche échouée selon politique.
- Consulter historique d'exécution.

## Entités
- BusinessTask : type, titre, description, statut, priorité, date planifiée, assignation.
- TaskExecution : tâche, statut, début, fin, erreur.

## Intégration Shopware/Symfony
Utiliser Scheduled Tasks pour les déclenchements périodiques et Messenger pour le travail asynchrone lorsque pertinent. Les tâches métier persistées ne remplacent pas le scheduler natif.

## Règles
- Une exécution possède un identifiant et un statut.
- Les tâches rejouables doivent être idempotentes.
- Une tâche ne doit pas être exécutée simultanément si elle n'est pas conçue pour cela.
- Les retries sont bornés et observables.

## Critères d'acceptation
- Une tâche planifiée est déclenchée et enregistrée.
- Son résultat SUCCESS ou FAILED est consultable.
- Une exécution simultanée non autorisée est empêchée.
- Tests de planification, verrouillage, reprise et logs.
