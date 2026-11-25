# Matrice des Scénarios de Tests Automatisés — Vetocare

Cette matrice recense l'ensemble des scénarios de tests automatisés (exécutés via PHPUnit & Zenstruck Browser) couvrant le pare-feu, l'authentification JWT, les règles métiers et le contrôle d'accès basé sur les rôles (RBAC).

---

## 1. Sécurité Globale & Authentification (`SecurityTest.php`)

Le pare-feu Symfony bloque par défaut toute requête anonyme sur le préfixe `/api/*`. L'authentification s'effectue via l'obtention d'un jeton JWT sur la route dédiée.

| Route | Méthode | Authentification | Condition / Payload | Code HTTP Attendu | Classe de Test |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `/api/login_check` | `POST` | Anonyme | Identifiants valides (`email` + `password`) | `200 OK` (retourne `token`) | `SecurityTest` |
| `/api/login_check` | `POST` | Anonyme | Identifiants invalides | `401 Unauthorized` | `SecurityTest` |
| `/api/animals` | `GET` | Anonyme | Tentative d'accès sans header `Authorization` | `401 Unauthorized` | `SecurityTest` |
| `/api/consultations` | `POST` | Anonyme | Tentative d'accès sans header `Authorization` | `401 Unauthorized` | `SecurityTest` |

---

## 2. Gestion des Animaux (`AnimalTest.php`)

| Route | Méthode | Rôle Requis | Scénario / Payload | Code HTTP Attendu | Assertion Spécifique |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `/api/animals` | `POST` | `ROLE_VETO` | Payload valide (`name`, `species`, `dateOfBirth`, `ownerName`) | `201 Created` | Contient les données enregistrées |
| `/api/animals` | `POST` | `ROLE_VETO` | Champ obligatoire manquant (`name`, `species`, `ownerName`) | `422 Unprocessable` | Violation sur le champ concerné |
| `/api/animals` | `POST` | `ROLE_VETO` | `dateOfBirth` absente / nulle | `422 Unprocessable` | Violation `dateOfBirth` |
| `/api/animals` | `POST` | `ROLE_VETO` | `dateOfBirth` dans le futur (`tomorrow`) | `422 Unprocessable` | Violation `dateOfBirth` |
| `/api/animals` | `POST` | `ROLE_VETO` | `ownerName` trop court (< 3 caractères) | `422 Unprocessable` | Violation `ownerName` |
| `/api/animals` | `GET` | `ROLE_VETO` | Consultation de la liste | `200 OK` | `totalItems` >= 1 |
| `/api/animals/{id}` | `GET` | `ROLE_VETO` | Consultation d'une fiche existante | `200 OK` | Données de l'animal |
| `/api/animals/{id}` | `GET` | `ROLE_VETO` | Identifiant inexistant (ex: `/api/animals/99999`) | `404 Not Found` | - |
| `/api/animals/{id}` | `PATCH` | `ROLE_VETO` | Modification partielle valide (`name`) | `200 OK` | Nom mis à jour |
| `/api/animals/{id}` | `PATCH` | `ROLE_VETO` | Modification avec données invalides (`ownerName` = "Ab") | `422 Unprocessable` | Violation `ownerName` |
| `/api/animals/{id}` | `DELETE` | `ROLE_VETO` | Tentative de suppression d'un animal | `403 Forbidden` | Interdit aux vétérinaires |
| `/api/animals/{id}` | `DELETE` | `ROLE_ADMIN` | Suppression d'un animal **sans consultation** | `204 No Content` | Ressource supprimée |
| `/api/animals/{id}` | `DELETE` | `ROLE_ADMIN` | Suppression d'un animal **avec historique de consultations** | `409 Conflict` (ou `422`) | Refusé : protection de l'historique médical |

---

## 3. Gestion des Consultations (`ConsultationTest.php`)

| Route | Méthode | Rôle Requis | Scénario / Payload | Code HTTP Attendu | Assertion Spécifique |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `/api/consultations` | `POST` | `ROLE_VETO` | Consultation valide (Lien `animal`, `date`, `reason`, `diagnosis`) | `201 Created` | `veterinarian` injecté automatiquement (`UserAwareProcessor`) |
| `/api/consultations` | `POST` | `ROLE_VETO` | Validation : `date` dans le futur (`> now`) | `422 Unprocessable` | Violation `LessThanOrEqual('now')` |
| `/api/consultations` | `POST` | `ROLE_VETO` | Validation croisée : `date` < `animal.dateOfBirth` | `422 Unprocessable` | Violation d'intégrité temporelle |
| `/api/consultations` | `POST` | `ROLE_VETO` | Champs obligatoires manquants (`animal`, `reason`, `diagnosis`) | `422 Unprocessable` | Violation sur les champs concernés |
| `/api/consultations` | `GET` | `ROLE_VETO` | Liste globale des consultations | `200 OK` | Collection retournée |
| `/api/users/{userId}/consultations` | `GET` | `ROLE_VETO` | Consultations d'un vétérinaire spécifique (Sous-ressource) | `200 OK` | Filtre automatique sur le praticien |
| `/api/consultations` | `GET` | `ROLE_VETO` | Filtrage par date, nom ou ID d'animal (`?animal.name=...`) | `200 OK` | Collection filtrée selon critères |
| `/api/consultations/{id}` | `GET` | `ROLE_VETO` | Détail d'une consultation | `200 OK` | Détails + relation animal/veto |
| `/api/consultations/{id}` | `GET` | `ROLE_VETO` | Consultation inexistante | `404 Not Found` | - |
| `/api/consultations/{id}` | `PATCH` | `ROLE_VETO` | Mise à jour du diagnostic ou traitement | `200 OK` | Données modifiées |
| `/api/consultations/{id}` | `DELETE` | `ROLE_VETO` | Tentative de suppression | `403 Forbidden` | Interdit aux vétérinaires |
| `/api/consultations/{id}` | `DELETE` | `ROLE_ADMIN` | Suppression d'une consultation | `204 No Content` | Ressource supprimée |

---

## 4. Gestion des Utilisateurs / Véto (`UserTest.php`)

| Route | Méthode | Rôle Requis | Scénario / Payload | Code HTTP Attendu | Assertion Spécifique |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `/api/users` | `POST` | `ROLE_VETO` | Tentative de création d'un compte utilisateur | `403 Forbidden` | Réservé à l'Admin |
| `/api/users` | `POST` | `ROLE_ADMIN` | Création d'un compte Vétérinaire valide (`email`, `password`, `roles`) | `201 Created` | Compte créé |
| `/api/users` | `POST` | `ROLE_ADMIN` | Email déjà existant dans la base (`UniqueEntity`) | `422 Unprocessable` | Violation d'unicité |
| `/api/users` | `POST` | `ROLE_ADMIN` | Payload invalide (Email invalide, mot de passe absent ou < 6 car.) | `422 Unprocessable` | Violation sur le champ concerné |
| `/api/users` | `GET` | `ROLE_ADMIN` | Liste des utilisateurs / vétérinaires | `200 OK` | Collection complète (`totalItems` incluant l'admin) |
| `/api/users` | `GET` | `ROLE_VETO` | Tentative d'accès à la liste globale des utilisateurs | `403 Forbidden` | Accès réservé à l'Admin |
| `/api/users/{id}` | `GET` | `ROLE_VETO` | Consultation de son propre profil | `200 OK` | Données du compte connecté |
| `/api/users/{id}` | `GET` | `ROLE_VETO` | Tentative de consultation du profil d'un confrère | `403 Forbidden` | Interdit de consulter le profil d'autrui |
| `/api/users/{id}` | `PATCH` | `ROLE_VETO` | Modification de son propre profil (`email`) | `200 OK` | Données personnelles mises à jour |
| `/api/users/{id}` | `PATCH` | `ROLE_VETO` | Tentative de modification du profil d'un confrère | `403 Forbidden` | Interdit de modifier le profil d'autrui |
| `/api/users/{id}` | `PATCH` | `ROLE_ADMIN` | Modification de n'importe quel profil par le gérant | `200 OK` | Compte mis à jour par l'Admin |
| `/api/users/{id}` | `DELETE` | `ROLE_VETO` | Tentative de suppression d'un compte par un vétérinaire | `403 Forbidden` | Interdit aux vétérinaires |
| `/api/users/{id}` | `DELETE` | `ROLE_ADMIN` | Suppression d'un utilisateur **sans consultation effectuée** | `204 No Content` | Compte supprimé |
| `/api/users/{id}` | `DELETE` | `ROLE_ADMIN` | Suppression d'un utilisateur **ayant effectué des consultations** | `409 Conflict` (ou `422`) | Refusé : conservation de la traçabilité médicale |
