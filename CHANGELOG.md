# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-03-19

### Ajouté
- Migration complète vers PHP
- Intégration de l'API Brickset
  - Récupération des thèmes
  - Récupération des sets
  - Gestion des erreurs
- Intégration de l'API Rebrickable
  - Récupération des thèmes
  - Récupération des sets
  - Statistiques détaillées
- Intégration de Supabase
  - Stockage des données
  - Suivi des mises à jour
  - Statistiques en temps réel
- Interface utilisateur
  - Dashboard moderne et responsive
  - Affichage du statut des APIs
  - Temps de réponse
  - Statistiques détaillées
  - Mise à jour automatique toutes les 30 secondes

### Modifié
- Refonte complète de l'architecture
- Passage de TypeScript/Node.js à PHP
- Amélioration de la gestion des erreurs
- Optimisation des performances

### Supprimé
- Ancien code TypeScript
- Configuration Node.js
- Tests Jest
- Ancienne documentation 