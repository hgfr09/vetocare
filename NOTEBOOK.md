### 📂 Structure initiale du Notebook **Vetocare**

#### 1. 🎯 Le Brief Client & Scope (Jalon 1)

* **Nom du projet :** Vetocare
* **Objectif :** Gestion des patients (animaux) et consultations pour une clinique vétérinaire.
* **Rôles utilisateurs définis :**
* `ROLE_VETO` : Lecture/Écriture/Modification des animaux et consultations.
* `ROLE_ADMIN` : Droits Veto + Droit exclusif de suppression (`DELETE`) et gestion des utilisateurs.


#### 2. 🗺️ La Feuille de Route de l'Agence (Suivi des Jalons)

* [x] **Jalon 1 :** Conception & Cahier des charges
* [x] **Jalon 2 :** Modélisation & Initialisation de l'API
* [ ] **Jalon 3 :** Sécurisation JWT & Rôles
* [ ] **Jalon 4 :** Architecture Front React (Axios en 3 couches)
* [ ] **Jalon 5 :** Intégration UX & Formulaires (422/401)
* [ ] **Jalon 6 :** CI/CD & Déploiement

#### 3. 🏗️ Schéma de la Base de Données (À compléter au Jalon 2)

*(Ici, on listera les tables, leurs champs et les relations : OneToMany, ManyToOne, etc.)*

#### 4. 🔏 Règles d'Architecture & Bonnes Pratiques (Ton pense-bête)

* **Côté API :** Déclarer explicitement la sécurité sur chaque route d'entité. Valider les données entrantes (Asserts Symfony).
* **Côté Front :** * Architecture en 3 couches : `Composant JSX` ➔ `Service dédié` ➔ `Client HTTP centralisé (Axios)`.
* **Règle d'or :** Pas de requêtes `fetch` isolées ou d'en-têtes éparpillés, tout passe par le client Axios et ses intercepteurs.


---

### 🛠️ Comment on va l'utiliser "au fur et à mesure" ?

À chaque fois que tu vas franchir une étape ou résoudre un bug complexe, on viendra enrichir ce Notebook. On y ajoutera :

1. **Le dictionnaire des Endpoints API** (ex: `POST /api/animals` - accessible par `ROLE_VETO`).
2. **Les commandes utiles du projet** (les commandes Docker, les migrations Symfony, le lancement de React) pour que tu puisses relancer ton projet en 2 secondes si tu changes de PC ou après une pause.
3. **Le Journal des Décisions (ADR - Architecture Decision Records) :** Une section très pro où on écrit pourquoi on a choisi telle méthode plutôt qu'une autre (par exemple, pourquoi on utilise Axios plutôt que Fetch).