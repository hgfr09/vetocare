---
description: Vetocare project rules
---
# Règles de l'Espace de Travail - Projet Vetocare

## Contexte du Projet
- **Nom :** Vetocare
- **Architecture :** Application découplée (Full-stack API).
- **Backend :** Symfony / API Platform (PHP OOP, Doctrine ORM).
- **Frontend :** React (Gestion d'état, Hooks personnalisés, Intégration API).

## Directives Techniques Spécifiques

### 1. Cohérence Frontend / Backend (Le Pont API)
- Reste ultra-vigilant sur la correspondance entre les entités API Platform et la consommation des données côté React.
- En cas de problème de requêtage, vérifie et valide systématiquement :
  - Les **groupes de sérialisation** (Denormalization / Serialization).
  - Les formats de données transférés (notamment les formats de date ou les relations IRI `/api/animals/1`).
  - Le respect des standards REST.

### 2. Bonnes Pratiques Backend (API Platform & Symfony)
- Favorise l'utilisation correcte des attributs de configuration d'API Platform (v3+).
- Alerte sur les performances de la base de données : signale les risques de requêtes **N+1** et suggère l'utilisation des jointures ou du `Eager Loading` via Doctrine si nécessaire.

### 3. Bonnes Pratiques Frontend (React)
- Surveille l'optimisation des composants pour éviter les **re-renders inutiles** (mauvaise gestion des dépendances dans les tableaux de `useEffect`, `useMemo`, ou `useCallback`).
- Encourage une séparation claire des responsabilités : l'utilisation de Custom Hooks dédiés pour isoler les appels API (`fetch`/`axios`) de la logique d'affichage des composants.

## Posture d'Accompagnement
- Aligne tes réponses sur le fichier de règles globales (Rôle de Mentor / Approche Ground-Up).
- Ne donne pas de correctif global immédiat : commence par analyser le comportement attendu côté API Platform par rapport à ce que React reçoit réellement.