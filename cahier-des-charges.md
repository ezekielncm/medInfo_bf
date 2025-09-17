# Cahier des charges technique — Réalisation **MedInfo** avec **Laravel + Jetstream**

Document opérationnel, étape par étape, pour transformer la conception actuelle en une application Laravel prête pour production. Chaque étape indique actions précises, commandes, exemples de code, critères d’acceptation et points de vigilance. Rédaction claire, technique et actionable.

---

# Phase 0 — Pré-requis et environnement

1. Installer les outils système nécessaires

   * PHP 8.1+ avec extensions `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` / `imagick`.
   * Composer.
   * Node.js 18+ et npm / pnpm.
   * MySQL / MariaDB 10.4+.
   * Redis (sessions, cache, queues) recommandé.
2. Créer les comptes système et répertoires

   * utilisateur deployer, dossier `/var/www/medinfo`.
   * droits limités; pas de secrets dans le dépôt.
3. Fichiers d’environnement

   * `.env.example` avec clés listées : `DB_DSN`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_KEY`, `JWT_SECRET`, `REDIS_URL`, `MAIL_DSN`, `AWS_*` ou `S3_*`.
4. Critère d’acceptation

   * `composer install` passe sans erreur.
   * `php -v` affiche version attendue.
   * Base MySQL créée et accessible via PDO.

---

# Phase 1 — Initialisation du projet Laravel + Jetstream + RBAC

1. Créer projet Laravel

   ```bash
   composer create-project laravel/laravel medinfo
   cd medinfo
   ```
2. Installer Jetstream (Livewire)

   ```bash
   composer require laravel/jetstream
   php artisan jetstream:install livewire
   npm install
   npm run build
   php artisan migrate
   ```

   * Jetstream fournit : registration, login, reset password, email verification, 2FA, profile.
3. Installer RBAC Spatie

   ```bash
   composer require spatie/laravel-permission
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan migrate
   ```

   * Ajouter trait `HasRoles` dans `App\Models\User`.
4. Installer Sanctum pour API tokens (Jetstream inclut Sanctum)

   * Configurer `config/sanctum.php` pour CSRF et SPA.
5. Critère d’acceptation

   * Inscription, connexion, reset password fonctionnels en UI.
   * Roles tables créées; `php artisan tinker` permet `\Spatie\Permission\Models\Role::create(['name'=>'admin'])`.

---

# Phase 2 — Schéma de données et migrations

Créer migrations Laravel pour toutes les entités. Exemple synthétique ; ajouter champs d’audit `created_at`, `updated_at`.

1. `users` (fichier Jetstream déjà présent)

   * garder `name`, `email`, `password`, `email_verified_at`.
2. `patients`

   * `id`, `user_id` FK `users.id`, `national_id` unique, `dob`, `address`, `phone`, timestamps.
3. `doctor_patient` (pivot)

   * `doctor_id` FK `users.id`, `patient_id` FK `patients.id`, `assigned_at`.
4. `consultations`

   * `id`, `patient_id` FK, `doctor_id` FK `users.id`, `date`, `reason`, `diagnosis`, timestamps.
5. `prescriptions`

   * `id`, `consultation_id` FK, `medicine`, `dosage`, `duration`, `notes`.
6. `medical_records`

   * `id`, `patient_id` FK, `doctor_id` FK `users.id` (nullable), `type`, `description`, timestamps.
7. `lab_results`

   * `id`, `patient_id` FK, `performed_by`, `test_name`, `result`, `result_date`, timestamps.
8. `audit_logs`

   * `id`, `user_id` FK nullable, `action` VARCHAR, `details` JSON nullable, `ip_address`, `user_agent`, `http_status` smallint, timestamps.
9. Migration helpers

   * utiliser `foreignId()->constrained()->onDelete('cascade')` et `->onDelete('set null')` pour colonnes appropriées.
10. Critère d’acceptation

    * `php artisan migrate` exécute toutes les migrations; tables présentes et FK valides.

---

# Phase 3 — Modèles Eloquent et relations

Implémenter modèles avec relations et protections.

1. `App\Models\User`

   * `use HasRoles;`
   * relations : `patients()` `belongsToMany(Patient::class,'doctor_patient','doctor_id','patient_id')`, `medicalRecords()` `hasMany(MedicalRecord::class,'doctor_id')`, `consultations()` `hasMany(Consultation::class,'doctor_id')`.
   * `$fillable = ['name','email','password'];` `$hidden = ['password','remember_token'];`
2. `App\Models\Patient`

   * `user() belongsTo(User::class)`, `doctors() belongsToMany(User::class,'doctor_patient','patient_id','doctor_id')`, `medicalRecords() hasMany(MedicalRecord::class)`.
3. `App\Models\Consultation`, `Prescription`, `MedicalRecord`, `LabResult`, `AuditLog`

   * définir `$fillable` et `$casts` (`details` cast JSON), indexer colonnes fréquemment requêtées.
4. Critère d’acceptation

   * Relations testées en tinker : charger patient avec `Patient::with('doctors','user')->find(1)` renvoie données attendues.

---

# Phase 4 — Policies, Gates, Middlewares, validation

Sécuriser accès métier via Policies, middlewares et validation centralisée.

1. Policies

   * `php artisan make:policy PatientPolicy --model=Patient`
   * Methods : `view`, `viewAny`, `create`, `update`, `delete`, `assignDoctor`.
   * Règles : `view` => patient lui-même, docteur assigné, admin.
2. Register policies in `AuthServiceProvider`.
3. Middleware role

   * utiliser Spatie trait `role` middleware ou créer `RoleMiddleware` custom à usage simple.
4. Global middlewares

   * `logger` : log chaque requête sensible dans `audit_logs` avec `action = http_request` et details `{ method, path, user_id }`. Filtrer assets.
   * `throttle` : limiter tentatives login ; config Redis rate limiter.
   * `methodOverride` non nécessaire ; Laravel gère `_method`.
5. Validation

   * Utiliser `FormRequest` classes pour validation réutilisable. Exemple `StorePatientRequest`.
6. Event listeners

   * écouter `Illuminate\Auth\Events\Login`, `Failed`, `Logout` et logger via listeners qui enregistrent `audit_logs`.
7. Critère d’acceptation

   * Tests unitaires Policies passent ; accès interdit retourne 403.

---

# Phase 5 — Contrôleurs API et routes

Créer API REST versionnée `/api/v1/` avec contrôleurs explicitement testés.

1. Installer Sanctum pour API tokens ; config `sanctum` cookie domain si SPA.
2. Routes `routes/api.php` structure :

   * group middleware `auth:sanctum` pour endpoints privés.
   * endpoints publics limités (ex: `GET /api/v1/public/info`).
3. Contrôleurs à générer :

   * `Api\PatientsController` : `index`, `show`, `store`, `update`, `destroy`. Utiliser Policy `authorize`.
   * `Api\DoctorsController` : `index`, `assignPatient`, `unassignPatient`.
   * `Api\ConsultationsController` : `store`, `show`, `update`.
   * `Api\PrescriptionsController` : nested under consultations.
   * `Api\LabResultsController` : create by lab role, show for patient/doctor.
   * `Api\AuditLogsController` : admin only, filters and pagination.
4. Responses

   * Standardiser responses JSON via base `Controller::success/error/paginated`.
5. Exemple route

   ```php
   Route::prefix('v1')->group(function () {
       Route::apiResource('patients', PatientsController::class);
       Route::post('doctors/{doctor}/patients/{patient}', [DoctorsController::class,'assignPatient']);
   });
   ```
6. Critère d’acceptation

   * Chaque endpoint a tests d’intégration qui valident autorisations, validations, et réponses JSON.

---

# Phase 6 — Authentification avancée, mot de passe et sécurité

1. Password reset

   * Jetstream fournit reset flows. Vérifier `config/auth.php` mail settings.
2. Two-Factor Authentication

   * Activer 2FA via Jetstream si requis.
3. Rate limiting

   * Configurer `RateLimiter::for('login',...)` dans `App\Providers\RouteServiceProvider`.
4. Session hardening

   * `.env` : `SESSION_SECURE_COOKIE=true`, `SESSION_DRIVER=redis` si Redis utilisé.
   * `config/session.php` : `same_site` => `strict`.
5. Cryptage des champs sensibles

   * implémenter chiffrement application-level pour champs critiques : `Crypt::encryptString()` pour diagnostics sensibles.
   * gérer clés via `.env` et AWS KMS recommandé.
6. TLS / HTTP headers

   * config Nginx pour HSTS, CSP, X-Frame-Options, X-Content-Type-Options.
7. Critère d’acceptation

   * Attaques CSRF stoppées par Jetstream; rate limiter empêche bruteforce.

---

# Phase 7 — Fichiers, stockage et attachments

1. Stockage fichiers

   * config filesystem `s3` pour production; local disk pour dev.
   * table `attachments` : `id, model_type, model_id, path, mime, size, uploaded_by`.
2. Scan virus

   * pipeline upload asynchrone : stocker en quarantine, envoyer job pour scan ClamAV.
3. Permissions

   * URLs signées pour accès temporel via `Storage::temporaryUrl()`.
4. Critère d’acceptation

   * Upload d’un document génère un enregistrement attachment; accès via URL signé valide.

---

# Phase 8 — Notifications, files async, queues

1. Configurer queue driver Redis.
2. Créer jobs pour : envoi d’emails, génération PDF, traitement d’uploads, envoi SMS via provider.
3. Supervisor systemd config pour workers.
4. Notifications

   * Laravel Notifications pour email/SMS ; channels configurés via `.env`.
5. Critère d’acceptation

   * Job d’envoi d’email fonctionne avec `php artisan queue:work`; tâches réessai gérées.

---

# Phase 9 — PWA et mode offline

1. Intégrer service worker

   * utiliser package `laravel-pwa` ou config manuelle avec Workbox.
2. Cache shell et routes publiques essentielles.
3. Offline writes

   * stocker actions locales dans IndexedDB; point d’API `/api/v1/sync` accepte batch; chaque record inclut `client_generated_uuid` pour idempotence.
4. Conflits

   * stratégie : si conflit détecté serveur retourne `409 Conflict` avec payload `server_version` et `client_version`; front affiche UI de résolution.
5. Critère d’acceptation

   * App shell charge offline; enregistrement local replay après reconnect.

---

# Phase 10 — Tests, qualité, sécurité

1. Tests unitaires

   * PHPUnit / Pest ; coverage pour services et modèles clés.
2. Tests d’intégration

   * API tests avec database sqlite in-memory ou test DB.
3. Tests E2E

   * Cypress pour flows critiques : login, create consultation, add record.
4. Static analysis & lint

   * `phpstan` niveau 7+, `php-cs-fixer` PSR-12.
5. Security scans

   * `composer audit`, dépendances vulnérables corrigées.
6. Critère d’acceptation

   * Pipeline CI passe toutes les étapes sans erreurs.

---

# Phase 11 — CI/CD, déploiement, infra

1. CI pipeline GitHub Actions

   * steps: checkout, setup-php, composer install, npm ci, npm run build, phpstan, tests, deploy artifact.
   * snippet minimal `/.github/workflows/ci.yml`:

     ```yaml
     name: CI
     on: [push, pull_request]
     jobs:
       test:
         runs-on: ubuntu-latest
         steps:
           - uses: actions/checkout@v4
           - name: Setup PHP
             uses: shivammathur/setup-php@v2
             with: php-version: '8.1'
           - run: composer install --no-interaction --prefer-dist
           - run: php artisan test
     ```
2. Déploiement

   * Build artifacts sur CI, deploy via rsync/ssh ou via Forge/Envoyer.
   * Config Nginx + php-fpm pool.
   * Exécuter `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache` dans deploy hook.
3. Supervisor

   * config systemd / supervisor pour `php artisan queue:work --sleep=3 --tries=3`.
4. Critère d’acceptation

   * Deploy en staging automatisé via pipeline; healthcheck `/health` retour 200.

---

# Phase 12 — Sauvegardes, monitoring, observabilité

1. Backups

   * Installer `spatie/laravel-backup`. Configurer S3 destination chiffrée.
   * Cron job quotidien ; garder rétention 30 jours.
2. Logs & error tracking

   * Intégrer Sentry `sentry/sentry-laravel`.
   * Envoyer `APP_ENV` et `release` tags.
3. Metrics

   * Exporter métriques via Prometheus exporter pour PHP-FPM; créer dashboards Grafana.
4. Audit logs

   * `audit_logs` table indexée et export CSV via admin UI.
5. Critère d’acceptation

   * Backups accessibles; restauration testée et documentée; alertes en place pour erreurs critiques.

---

# Phase 13 — Migration des données existantes

1. Exporter données actuelles (CSV/SQL) depuis le legacy system.
2. Ecrire scripts Laravel artisan command `php artisan medinfo:import --file=...` qui :

   * nettoient doublons, valident `national_id`, lient users ↔ patients, créent logs d’import.
3. Validation post-import par contrôles automatisés.
4. Critère d’acceptation

   * 100% des enregistrements importés; tests d’intégrité passés.

---

# Phase 14 — Documentation, runbook, formation

1. Documentation développeur

   * README, architecture, diagrammes, commandes de dev.
2. Documentation API

   * OpenAPI YAML généré et hébergé via Swagger UI en staging.
3. Runbook opérateur

   * procédure de restore DB, escalade incidents, rotation clés.
4. Formation

   * sessions pro pour admins et médecins; guides utilisateurs simples.
5. Critère d’acceptation

   * Doc accessible; équipe opérationnelle capable d’effectuer restore test.

---

# Phase 15 — Sécurité renforcée pour mise en production

1. Activer HSTS, CSP configuration, `X-Frame-Options: DENY`.
2. Exiger HTTPS ; redirection forcée.
3. DB user avec restrictions ; backups chiffrés.
4. KMS pour clé d’application sensible ; rotatation clé planifiée.
5. Pentest avant mise en prod ; corriger findings majeurs.
6. Critère d’acceptation

   * Rapport pentest avec findings résolus ou plan d’action validé.

---

# Annexes techniques (snippets et patterns)

### Exemple listener login -> audit

```php
// app/Listeners/LogSuccessfulLogin.php
public function handle(\Illuminate\Auth\Events\Login $event) {
    \App\Models\AuditLog::create([
        'user_id' => $event->user->id,
        'action'  => 'login_success',
        'details' => ['ip' => request()->ip()],
    ]);
}
```

### Exemple middleware logger

```php
public function handle($request, Closure $next) {
    $response = $next($request);
    if (!preg_match('/\.(js|css|png|jpg|svg)$/', $request->path())) {
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action'  => 'http_request',
            'details' => ['method'=>$request->method(),'path'=>$request->path()],
            'http_status' => $response->status()
        ]);
    }
    return $response;
}
```

### `.env.example` minimum

```
APP_NAME=MedInfo
APP_ENV=production
APP_KEY=base64:...
APP_URL=https://medinfo.example.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medinfo
DB_USERNAME=medinfo
DB_PASSWORD=secret
REDIS_HOST=127.0.0.1
MAIL_DSN=smtp://user:pass@smtp.provider:587
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=af-south-1
AWS_BUCKET=
```

---

# Livrables finaux et critères de réception

1. Code source complet dans repository Git.
2. Migrations et seeders pour initialiser admin/roles/demo data.
3. API documentée en OpenAPI accessible en staging.
4. CI pipeline fonctionnel avec tests.
5. Environnements `staging` et `production` avec deploy automatisé.
6. Backups automatiques testés; monitoring et alerting actifs.
7. Documentation opérationnelle et manuels utilisateurs.

---

# Points de vigilance et recommandations fortes

* Choisir Redis pour sessions et rate limiter afin d’assurer scalabilité et cohérence.
* Gérer chiffrement des données sensibles via KMS; ne pas stocker clés dans le repo.
* Prévoir mécanisme d’archivage des consultations anciennes pour garder tables performantes.
* Effectuer audit sécurité externe avant mise en production.

---

Si tu veux, je génère immédiatement :

* les **migrations Laravel** complètes pour toutes les tables listées ;
* les **modèles Eloquent** avec `$fillable`, casts et relations ;
* le **squelette complet des contrôleurs API** avec FormRequests et Policies ;
* le **workflow GitHub Actions** prêt.

Dis-moi quelle génération tu souhaites que je fournisse en premier et je fournis les fichiers prêts à coller.
