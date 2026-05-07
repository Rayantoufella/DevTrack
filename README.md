# DevTrack

Outil interne de gestion de projets et tâches pour startup. Le Team Lead crée les projets, invite son équipe, découpe le travail en tâches et suit l'avancement. Chaque developer voit ses tâches et met à jour leur statut.

Construit avec **Laravel 13** + **Breeze** + **Tailwind**.

---

## Stack

- PHP `^8.3`
- Laravel `^13.0`
- MySQL (XAMPP en local)
- Tailwind 3 + Alpine.js + Vite (Breeze)
- Sanctum (pour l'API)

---

## Installation

```bash
git clone <repo> devtrack
cd devtrack

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Édite `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=DevTrack
DB_USERNAME=root
DB_PASSWORD=
```

Crée la base puis migre + seed :

```bash
mysql -u root -e "CREATE DATABASE DevTrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
```

Lance les serveurs :

```bash
php artisan serve   # http://127.0.0.1:8000
npm run dev         # Vite (HMR sur http://localhost:5173)
```

> Pour aligner les versions PHP entre développeurs, `composer.json` pin la plateforme à PHP 8.3 (`config.platform.php`).

---

## Comptes de test (après seed)

10 utilisateurs factory sont créés. Tous ont le même password : **`password`**.

```
ohomenick@example.net  → lead de plusieurs projets
iterry@example.com     → developer
...
```

Liste : `mysql -u root DevTrack -e "SELECT name, email FROM users;"`

---

## Architecture

### Models
- `User` — `belongsToMany(Project)` (pivot `project_user`), `hasMany(Task)`
- `Project` — `belongsToMany(User)` avec pivot (`role`, `assigned_at`, `removed_at`), `hasMany(Task)`, `SoftDeletes`
  - Helpers : `isLead(User)`, `isMember(User)`
  - Mutator : `title` → `ucfirst()` automatique
- `Task` — `belongsTo(Project, User)`
  - Accessor : `status_label` → `À faire` / `En cours` / `Terminé`
  - Scope : `urgent()` → deadline < 48h ET status ≠ done

### Statuts de tâche
- `todo` (À faire)
- `in_progress` (En cours)
- `done` (Terminé)

### Priorités
- `low`, `medium`, `high`

### Rôles pivot
- `lead` — créateur du projet, accès complet
- `developer` — peut voir le projet et changer le statut de **ses** tâches uniquement

---

## Routes

### Web (sous `auth` middleware)

| Méthode | URI | Action |
|---|---|---|
| GET | `/projects` | Dashboard — projets de l'utilisateur avec compteurs |
| GET | `/projects/archives` | Liste des projets archivés du lead courant |
| POST | `/projects/{id}/restore` | Restaurer un projet (lead) |
| DELETE | `/projects/{id}/force` | Suppression définitive (lead, bonus) |
| GET, POST | `/projects` | CRUD projects (resource) |
| POST | `/projects/{id}/members` | Ajouter membre par email |
| DELETE | `/projects/{id}/members/{user}` | Retirer membre |
| GET, POST, PUT, DELETE | `/projects/{id}/tasks/...` | CRUD tasks (resource nested) |
| PATCH | `/projects/{id}/tasks/{id}/status` | Changer statut (developer assigné) |

### API (`routes/api.php`)

| Méthode | URI | Action |
|---|---|---|
| GET | `/api/projects/{project}/tasks` | Tâches du projet en JSON via `TaskResource` |

Test rapide :
```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/projects/1/tasks
```

Liste complète : `php artisan route:list`.

---

## Autorisation (Policies)

### `ProjectPolicy`
| Ability | Qui ? |
|---|---|
| `viewAny` | tout user authentifié |
| `view` | membres du projet |
| `create` | tout user authentifié |
| `update`, `delete`, `restore`, `forceDelete` | lead seulement |
| `manageMembers` | lead seulement |
| `createTask` | lead seulement |

### `TaskPolicy`
| Ability | Qui ? |
|---|---|
| `view` | membres du projet |
| `update`, `delete` | lead du projet |
| `updateStatus` | developer **assigné** à la tâche uniquement |

`@can` est utilisé dans toutes les vues, `$this->authorize(...)` dans tous les controllers, **zéro `abort(403)` manuel**.

---

## Form Requests

Toutes les validations passent par des `FormRequest` :

- `StoreProjectRequest`, `UpdateProjectRequest`
- `StoreTaskRequest`, `UpdateTaskRequest`, `UpdateTaskStatusRequest`
- `AddProjectMemberRequest`

`StoreTaskRequest` / `UpdateTaskRequest` valident en plus que le `user_id` assigné est bien **membre du projet** (via `Rule::exists` sur le pivot `project_user`).

---

## User Stories implémentées

- **US1** Inscription / Connexion / Déconnexion (Breeze)
- **US2** Dashboard avec compteurs `done/total`
- **US3** Créer un projet (devient lead)
- **US4** Modifier projet (lead)
- **US5** Archiver (soft delete)
- **US6** Restaurer
- **US7** Ajouter / retirer membre par email
- **US8** Liste des tâches avec indicateur d'urgence (En retard / Urgent < 48h / À temps / Terminé)
- **US9** Créer une tâche (lead, assignation à un membre)
- **US10** Modifier une tâche (lead)
- **US11** Changer le statut (developer assigné)
- **US12** Supprimer une tâche (lead)
- **US13** API `GET /api/projects/{id}/tasks` avec `TaskResource` + accessor `status_label`

### Bonus
- Suppression définitive depuis Archives (`forceDelete`)
- Mutator `title` (ucfirst) sur Project
- Local Scope `urgent()` sur Task

---

## Anti-N+1

`with()`, `withCount()` et `setRelation()` sont utilisés systématiquement :

- Dashboard : `withCount(['tasks', 'tasks as completed_tasks_count' => ...])`
- Project show : `load(['users', 'tasks.user'])` + `setRelation('project', $project)` sur les tâches pour éviter le N+1 dans les `@can`
- API : `load('tasks.user')`

Activer **Debugbar** (déjà installé en dev, `APP_DEBUG=true`) pour vérifier le nombre de requêtes par page.

---

## Workflow d'équipe (binôme)

`composer.json` est pinné à PHP 8.3 :
```json
"config": {
    "platform": {
        "php": "8.3.0"
    }
}
```

→ Composer résout les dépendances comme si tu étais sur PHP 8.3, même si tu tournes en 8.5 localement. **Personne ne peut introduire un package qui exige 8.4+** sans qu'on le sache.

Workflow :
1. Une seule personne lance `composer require <pkg>` / `composer update`.
2. Commit `composer.json` **et** `composer.lock` ensemble.
3. L'autre fait juste `composer install`.

---

## Stylage code

```bash
./vendor/bin/pint        # Laravel Pint (PSR-12 + conventions Laravel)
```

---

## Licence

MIT.
