# Brikocode Framework

> Framework PHP minimaliste à saveur ivoirienne.
> Zéro dépendance externe · PHP 8.1+ · Architecture propre

---

## Table des matières

1. [Installation](#installation)
2. [Architecture](#architecture)
3. [Configuration](#configuration)
4. [Routing](#routing)
5. [Request](#request)
6. [Response](#response)
7. [Middleware](#middleware)
8. [Base de données — database](#base-de-données--database)
9. [Offline-First](#offline-first)
10. [Mode Low Bandwidth](#mode-low-bandwidth)
11. [SMS — sms](#sms--sms)
12. [Logging](#logging)
13. [CLI — console](#cli--console)
14. [Helpers globaux](#helpers-globaux)
15. [Générateurs de code](#générateurs-de-code)
16. [Licence](#licence)

---

## Installation

```bash
git clone https://github.com/brikocode/framework mon-projet
cd mon-projet
composer install
cp .env.example .env
```

Démarrer le serveur de développement :

```bash
php briko feu
# → http://localhost:8000
```

---

## Architecture

```
brikocode/
│
├── foundation/                     Noyau applicatif
│   ├── App.php               Bootstrap : charge .env, boot Logger, instancie Kernel
│   ├── Container.php         Conteneur d'injection de dépendances
│   ├── Env.php               Parseur .env natif
│   ├── Logger.php            Système de logs structurés (+ LogChannel)
│   └── helpers.php           Fonctions globales : env(), db(), sms(), logger(), base_path()
│
├── http/                    Couche HTTP
│   ├── Kernel.php            Reçoit la requête, dispatche, envoie la réponse
│   ├── Request.php           Abstraction de la requête HTTP
│   ├── Response.php          Envoi de réponse JSON/HTML avec gzip optionnel
│   └── Middleware/
│       ├── MiddlewareInterface.php
│       ├── Pipeline.php      Exécute la chaîne de middlewares
│       ├── Guard.php         Exemple de middleware d'authentification
│       ├── OfflineFirst.php  Cache GET + file d'attente writes hors ligne
│       ├── LowBandwidth.php  Gzip + sélection de champs + strip nulls
│       └── HttpLogger.php    Log automatique de chaque requête HTTP
│
├── routing/               Routeur  (itinéraire = chemin)
│   └── Router.php            Routes dynamiques, tous verbes HTTP, middleware par route
│
├── database/                  Base de données
│   ├── Connection.php        Connexion PDO singleton (MySQL, PostgreSQL, SQLite)
│   ├── QueryBuilder.php      Query Builder fluide
│   ├── DB.php                Facade statique
│   ├── OfflineQueue.php      File d'attente JSON pour requêtes hors ligne
│   └── ResponseCache.php     Cache fichier des réponses GET
│
├── sms/                   SMS
│   ├── SMS.php               Facade statique (envoi + OTP)
│   ├── SmsMessage.php        Constructeur de message fluide
│   ├── SmsResult.php         Objet résultat d'un envoi
│   ├── OtpManager.php        Génération/vérification OTP sans base de données
│   └── Drivers/
│       ├── SmsDriverInterface.php
│       ├── AbstractDriver.php    Client HTTP partagé (cURL / fgc fallback)
│       ├── AfricasTalkingDriver.php  Africa's Talking (recommandé Afrique)
│       ├── TwilioDriver.php          Twilio (international)
│       ├── HttpDriver.php            HTTP générique (Orange CI, MTN, Moov…)
│       └── LogDriver.php             Développement — SMS dans les logs
│
├── console/                   CLI
│   └── Console.php           Toutes les commandes briko
│
├── app/                  Ton application
│   ├── routes.php            Définition des routes
│   ├── controllers/          Tes controllers
│   └── models/               Tes models
│
├── storage/
│   ├── logs/                 Fichiers de log journaliers
│   ├── cache/                Cache des réponses GET
│   ├── queue/offline.json    File d'attente hors ligne
│   └── otp/                  Codes OTP temporaires
│
├── public/
│   └── index.php             Point d'entrée HTTP
├── briko                     Point d'entrée CLI
├── composer.json
└── .env.example
```

---

## Configuration

Copie `.env.example` en `.env` et remplis les valeurs :

```dotenv
# Application
APP_NAME=Brikocode
APP_ENV=local          # local | production
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_PORT=8000

# Logs
LOG_LEVEL=DEBUG        # DEBUG | INFO | WARNING | ERROR | CRITICAL
                       # En production : LOG_LEVEL=WARNING

# Base de données
DB_DRIVER=mysql        # mysql | pgsql | sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=brikocode
DB_USER=root
DB_PASS=

# SQLite
# DB_DRIVER=sqlite
# DB_PATH=database/db.sqlite

# SMS — driver actif
SMS_DRIVER=log         # log | africastalking | twilio | http
SMS_FROM=Brikocode
OTP_MESSAGE="Votre code de vérification : "

# Africa's Talking (africastalking.com)
AT_USERNAME=sandbox
AT_API_KEY=
AT_SANDBOX=true        # false en production

# Twilio
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=

# HTTP générique (Orange CI, MTN, Moov, etc.)
SMS_HTTP_URL=
SMS_HTTP_FIELD_TO=to
SMS_HTTP_FIELD_MSG=message
SMS_HTTP_FIELD_FROM=from
SMS_HTTP_AUTH_FIELD=apikey
SMS_HTTP_AUTH_VALUE=
SMS_HTTP_SUCCESS_CODE=200
```

---

## Routing

Les routes sont définies dans `app/routes.php`.

### Verbes HTTP

```php
$router->get('/users',          [UserController::class, 'index']);
$router->post('/users',         [UserController::class, 'store']);
$router->put('/users/{id}',     [UserController::class, 'update']);
$router->patch('/users/{id}',   [UserController::class, 'patch']);
$router->delete('/users/{id}',  [UserController::class, 'destroy']);
```

### Routes dynamiques

```php
$router->get('/users/{id}',                          [UserController::class, 'show']);
$router->get('/posts/{slug}/comments/{commentId}',   [CommentController::class, 'show']);
```

### Closures

```php
$router->get('/ping', fn () => ['pong' => true]);
$router->get('/salut', fn () => 'Gbaka est en route 🚐');
```

### Middleware par route

```php
use Briko\Http\Middleware\OfflineFirst;
use Briko\Http\Middleware\LowBandwidth;
use Briko\Http\Middleware\HttpLogger;

$router->get('/users',     [UserController::class, 'index'],  [HttpLogger::class, OfflineFirst::class]);
$router->get('/api/light', [ApiController::class, 'data'],    [LowBandwidth::class]);

// Plusieurs middlewares s'exécutent dans l'ordre du tableau
$router->post('/users',    [UserController::class, 'store'],  [HttpLogger::class, OfflineFirst::class]);
```

---

## Request

```php
use Briko\Http\Request;

public function show(Request $request): array
{
    // Paramètre de route : /users/{id}
    $id = $request->param('id');

    // Valeur GET, POST, JSON body ou param — ordre unifié
    $name   = $request->input('name', 'defaut');
    $search = $request->input('q');

    // Accès direct
    $request->query;     // $_GET
    $request->post;      // $_POST
    $request->params;    // paramètres de route
    $request->files;     // $_FILES

    // Corps JSON brut
    $data = $request->body();    // array

    // Tout fusionné (query + post + body + params)
    $all = $request->all();

    // Utilitaires
    $request->isJson();           // Content-Type: application/json ?
    $request->isLowBandwidth();   // ?lb=1 ou ?compact=1 ou X-Low-Bandwidth: 1
    $request->wantsFields();      // ?fields=id,name → ['id','name'] ou null
    $request->acceptsGzip();      // Accept-Encoding: gzip + extension zlib ?

    return ['id' => $id];
}
```

---

## Response

```php
use Briko\Http\Response;

// JSON automatique si array/object (défaut)
return ['status' => 'ok', 'data' => $users];

// Méthodes statiques
Response::json(['key' => 'value'], 201);
Response::html('<h1>Bonjour</h1>', 200);
Response::notFound('Utilisateur introuvable');
Response::error('Erreur serveur', 500);
```

Les réponses JSON incluent automatiquement `JSON_UNESCAPED_UNICODE` — les caractères spéciaux africains s'affichent correctement.

---

## Middleware

### Middlewares intégrés

| Middleware | Rôle |
|---|---|
| `Guard` | Exemple de blocage par header |
| `OfflineFirst` | Cache GET + queue writes si DB/service down |
| `LowBandwidth` | Gzip + sélection de champs + strip nulls |
| `HttpLogger` | Log automatique requête/réponse + détection slow |

### Créer un middleware personnalisé

```php
<?php
namespace App\middleware;

use Briko\Http\Middleware\MiddlewareInterface;
use Briko\Http\Request;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        $token = $request->input('token') ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

        if (!$token || !$this->tokenValide($token)) {
            return ['error' => 'Non autorisé', 'code' => 401];
        }

        return $next($request);
    }

    private function tokenValide(string $token): bool
    {
        // Ta logique de validation
        return $token === env('API_TOKEN');
    }
}
```

```php
// Utilisation
$router->get('/admin', [AdminController::class, 'index'], [AuthMiddleware::class]);
```

---

## Base de données — database

### Connexion

La connexion est gérée automatiquement via `.env`. Aucun code d'initialisation requis.

```php
use Briko\Database\DB;
```

### Lecture

```php
// Tous les enregistrements
$users = DB::table('users')->get();

// Par ID
$user = DB::table('users')->find(1);
$user = DB::table('users')->findOrFail(1);  // lève RuntimeException si absent

// Premier résultat
$user = DB::table('users')->where('email', 'aya@abidjan.ci')->first();

// Conditions
DB::table('users')->where('active', 1)->get();
DB::table('users')->where('age', 18, '>=')->get();
DB::table('users')->where('ville', 'Abidjan')->orWhere('ville', 'Bouaké')->get();

// Sélection de colonnes
DB::table('users')->select('id', 'nom', 'email')->get();

// Tri
DB::table('users')->orderBy('nom')->get();
DB::table('users')->latest('created_at')->get();   // DESC
DB::table('users')->oldest('created_at')->get();   // ASC

// Limite / offset
DB::table('users')->limit(10)->offset(20)->get();

// Pagination
$page = DB::table('users')
    ->where('active', 1)
    ->orderBy('nom')
    ->paginate(15, 1);
// → ['data' => [...], 'total' => 120, 'per_page' => 15, 'current_page' => 1, 'last_page' => 8]

// Valeurs d'une seule colonne
$emails = DB::table('users')->pluck('email');   // ['a@ci', 'b@ci', ...]

// Comptage
$total  = DB::table('users')->where('active', 1)->count();
$existe = DB::table('users')->where('email', 'x@ci')->exists();  // bool
```

### Écriture

```php
// Insertion
DB::table('users')->insert(['nom' => 'Kouadio', 'email' => 'k@abidjan.ci']);

// Insertion avec retour de l'ID
$id = DB::table('users')->insertGetId(['nom' => 'Aya', 'email' => 'aya@ci']);

// Mise à jour
DB::table('users')->where('id', 1)->update(['nom' => 'Amani Koné']);

// Suppression
DB::table('users')->where('id', 1)->delete();
```

### Transactions

```php
DB::transaction(function () {
    DB::table('comptes')->where('id', 1)->update(['solde' => 500]);
    DB::table('comptes')->where('id', 2)->update(['solde' => 1500]);
    // Rollback automatique si une exception est levée
});
```

### SQL brut

```php
$results = DB::raw('SELECT * FROM users WHERE created_at > ?', ['2024-01-01']);
$affected = DB::exec('UPDATE users SET active = 0 WHERE last_login < ?', ['2023-01-01']);
```

### Drivers supportés

```dotenv
DB_DRIVER=mysql     # MySQL / MariaDB
DB_DRIVER=pgsql     # PostgreSQL
DB_DRIVER=sqlite    # SQLite (DB_PATH=database/db.sqlite)
```

---

## Offline-First

Conçu pour les zones à connectivité intermittente. Aucune dépendance externe.

### Principe

```
GET  + service UP   → réponse normale + mise en cache automatique
GET  + service DOWN → sert depuis le cache fichier (_offline: true)
POST + service DOWN → enfile la requête dans storage/queue/offline.json
php briko sync      → rejoue les requêtes en file via le routeur interne
```

### Activer par route

```php
use Briko\Http\Middleware\OfflineFirst;

$router->get('/produits',        [ProduitController::class, 'index'],  [OfflineFirst::class]);
$router->post('/commandes',      [CommandeController::class, 'store'], [OfflineFirst::class]);
$router->put('/commandes/{id}',  [CommandeController::class, 'update'],[OfflineFirst::class]);
```

### Réponse en mode offline

**Lecture (GET) :**
```json
{
  "data": [...],
  "_offline": true,
  "_cached_at": "2026-04-30 10:15:22",
  "_message": "Données en cache — connexion indisponible"
}
```

**Écriture (POST/PUT/DELETE) :**
```json
{
  "_offline": true,
  "queued": true,
  "sync_id": "briko_abc123",
  "message": "Requête enfilée — sera synchronisée à la reconnexion (php briko sync)",
  "pending": 3
}
```

### Synchronisation

```bash
php briko sync           # Rejoue toutes les requêtes en attente
php briko sync:status    # Voir la file d'attente + stats du cache
php briko sync:flush     # Vider la file (attention : données perdues)
```

### TTL du cache

Le TTL par défaut est de 300 secondes (5 minutes). Configurable :

```php
// Cache pendant 10 minutes
$router->get('/produits', [ProduitController::class, 'index'], [
    new OfflineFirst(ttl: 600)
]);
```

### Cache manuel

```php
use Briko\Database\ResponseCache;
use Briko\Database\OfflineQueue;

// Cache
ResponseCache::set('/produits', $data, 600);
ResponseCache::get('/produits');
ResponseCache::has('/produits');
ResponseCache::forget('/produits');
ResponseCache::flush();
ResponseCache::stats();  // ['entries' => 5, 'size_kb' => 1.2]

// File d'attente
OfflineQueue::push('POST', '/commandes', ['produit_id' => 42]);
OfflineQueue::pending();
OfflineQueue::count();
OfflineQueue::flush();
```

---

## Mode Low Bandwidth

Réduit la taille des réponses pour les connexions lentes.

### Activer par route

```php
use Briko\Http\Middleware\LowBandwidth;

$router->get('/users', [UserController::class, 'index'], [LowBandwidth::class]);

// Combiné avec Offline-First
$router->get('/users', [UserController::class, 'index'], [OfflineFirst::class, LowBandwidth::class]);
```

### Optimisations automatiques

| Mécanisme | Déclencheur | Effet |
|---|---|---|
| **Gzip** | Header `Accept-Encoding: gzip` | Corps compressé (−60 à −80 %) |
| **Sélection de champs** | `?fields=id,nom,email` | Retourne seulement les colonnes demandées |
| **Strip des nulls** | `?lb=1` · `?compact=1` · `X-Low-Bandwidth: 1` | Supprime les valeurs null et vides |

### Exemples

```http
# Ne retourne que id et nom dans chaque objet
GET /users?fields=id,nom

# Supprime les champs null + compresse
GET /users?compact=1

# Les deux combinés
GET /users?fields=id,nom,email&compact=1

# Via header (pratique pour les clients mobiles)
GET /users
X-Low-Bandwidth: 1
Accept-Encoding: gzip
```

### Headers retournés

```
X-Bandwidth-Mode: low
X-Payload-Original: 4800B
X-Payload-Compact: 1240B
X-Payload-Saved: 3560B
Content-Encoding: gzip
X-Compressed-Size: 312B
```

---

## SMS — sms

Le **tam-tam** était le système de communication longue distance en Afrique avant les réseaux. Aujourd'hui c'est ton SMS.

### Drivers disponibles

| Driver | Cas d'usage | Inscription |
|---|---|---|
| `log` | Développement (SMS dans les logs, rien envoyé) | — |
| `africastalking` | **Recommandé Afrique** — CI, GH, NG, KE, TZ… | africastalking.com |
| `twilio` | International | twilio.com |
| `http` | Orange CI, MTN CI, Moov, tout provider HTTP | Ton opérateur local |

### Envoi simple

```php
use Briko\Sms\SMS;

// Un destinataire
SMS::to('+2250700000000')->send('Votre commande #1042 est confirmée.');

// Expéditeur personnalisé
SMS::to('+2250700000000')->from('MonBoutik')->send('Nouvelle promo !');

// Multi-destinataires
SMS::to(['+2250700000000', '+2250800000000'])->send('Fermeture exceptionnelle demain.');

// Helper global
sms('+2250700000000')->send('Bienvenue !');
```

### OTP (One-Time Password)

Sans base de données — codes stockés dans `storage/otp/`.

```php
// Générer et envoyer un OTP (6 chiffres, valide 5 minutes)
$code = SMS::otp('+2250700000000');
// → Envoie "[Brikocode] Votre code : 482916"
// → Retourne '482916' pour vérification côté serveur

// OTP personnalisé : 4 chiffres, valide 10 minutes
$code = SMS::otp('+2250700000000', length: 4, ttlMinutes: 10);

// Préfixe personnalisé
$code = SMS::otp('+2250700000000', prefix: 'Code MonApp : ');

// Vérifier — supprime le code si correct
$ok = SMS::verifyOtp('+2250700000000', '482916');  // true / false
// Bloqué automatiquement après 3 tentatives échouées

// Utilitaires
SMS::otpPending('+2250700000000');   // bool — un OTP est-il en attente ?
SMS::cancelOtp('+2250700000000');    // Annuler un OTP
```

### Résultat d'envoi

```php
$result = SMS::to('+2250700000000')->send('Bonjour');

$result->isOk();       // bool
$result->success;      // bool
$result->messageId;    // string — ID retourné par le provider
$result->info;         // string — statut ou message d'erreur
$result->raw;          // array  — réponse brute du provider
$result->toArray();    // array  ['success', 'message_id', 'info']
```

### Configuration Africa's Talking

```dotenv
SMS_DRIVER=africastalking
AT_USERNAME=monapp         # Ton username AT
AT_API_KEY=abcdef123       # Ta clé API
AT_SANDBOX=false           # true en développement
SMS_FROM=BrikoApp
```

### Configuration Twilio

```dotenv
SMS_DRIVER=twilio
TWILIO_SID=ACxxxxxxxx
TWILIO_TOKEN=xxxxxxxx
TWILIO_FROM=+1234567890
```

### Configuration HTTP générique (Orange CI, MTN…)

```dotenv
SMS_DRIVER=http
SMS_HTTP_URL=https://api.orange.ci/sms/send
SMS_HTTP_AUTH_FIELD=apikey
SMS_HTTP_AUTH_VALUE=ta_cle
SMS_HTTP_FIELD_TO=numero
SMS_HTTP_FIELD_MSG=contenu
SMS_HTTP_FIELD_FROM=expediteur
SMS_HTTP_SUCCESS_CODE=200
```

---

## Logging

Logs JSON structurés, rotation journalière, zéro dépendance.

### Niveaux

```
DEBUG → INFO → WARNING → ERROR → CRITICAL
```

Le niveau minimum est configurable via `LOG_LEVEL` dans `.env`.
En production, utilise `LOG_LEVEL=WARNING` pour ignorer DEBUG et INFO.

### Canaux

| Canal | Contenu |
|---|---|
| `app` | Logs applicatifs généraux |
| `http` | Requêtes HTTP (via `HttpLogger` middleware) |
| `db` | Requêtes base de données |
| `sms` | Envois SMS et OTP |
| `security` | Authentification, accès refusés |
| `*` (libre) | N'importe quel canal personnalisé |

### Utilisation

```php
use Briko\Foundation\Logger;

// Canal app (défaut)
Logger::debug('Valeur inspectée', ['var' => $value]);
Logger::info('Utilisateur connecté', ['user_id' => 42]);
Logger::warning('Tentative de connexion échouée', ['ip' => '185.x.x.x', 'attempts' => 3]);
Logger::error('Paiement refusé', ['montant' => 5000, 'operateur' => 'MTN']);
Logger::critical('Intrusion détectée', ['ip' => '185.x.x.x', 'uri' => '/admin']);

// Canal nommé
Logger::channel('db')->info('Requête lente', ['sql' => 'SELECT...', 'duration_ms' => 820]);
Logger::channel('security')->warning('Token expiré', ['user_id' => 5]);
Logger::channel('sms')->info('OTP envoyé', ['phone' => '+225...']);

// Helper global
logger('Paiement reçu', ['montant' => 5000], 'info');
```

### Format d'une entrée de log

```json
{
  "ts": "2026-04-30 13:39:56",
  "request_id": "1092537e0d",
  "level": "WARNING",
  "channel": "http",
  "message": "GET /api/data",
  "elapsed_ms": 623.0,
  "memory_kb": 503,
  "context": {
    "duration_ms": 623,
    "ip": "197.157.1.8",
    "slow": true
  }
}
```

### Métadonnées automatiques

- `ts` — horodatage
- `request_id` — ID unique par requête (corrèle tous les logs d'une même requête)
- `elapsed_ms` — temps écoulé depuis le boot
- `memory_kb` — mémoire PHP utilisée

### Fichiers générés

```
storage/logs/
├── 2026-04-30.log           # Tous les canaux fusionnés
├── 2026-04-30-app.log       # Canal app
├── 2026-04-30-http.log      # Canal http
├── 2026-04-30-sms.log       # Canal sms
└── ...
```

### HttpLogger middleware

Log automatique de chaque requête. Détecte les requêtes lentes (seuil configurable).

```php
use Briko\Http\Middleware\HttpLogger;

// Sur une route
$router->get('/users', [UserController::class, 'index'], [HttpLogger::class]);

// Seuil personnalisé : WARNING si > 1000ms
$router->get('/users', [UserController::class, 'index'], [new HttpLogger(1000)]);
```

Masque automatiquement les champs sensibles dans les payloads :
`password`, `password_confirmation`, `token`, `secret`, `card_number`

---

## CLI — console

```bash
# Serveur de développement
php briko feu

# Générateurs
php briko fabrique:controller UserController
php briko fabrique:model      User

# Offline sync
php briko sync                     # Rejoue les requêtes en attente
php briko sync:status              # État de la file + stats cache
php briko sync:flush               # Vider la file d'attente

# Logs
php briko logs                     # 50 dernières lignes (tous canaux)
php briko logs http 100            # 100 dernières lignes du canal http
php briko logs:tail                # Suivre en temps réel (tail -f)
php briko logs:tail sms            # Suivre le canal sms
php briko logs:clear               # Supprimer tous les fichiers de log

# SMS
php briko sms:driver               # Voir le driver actif et sa configuration
php briko sms:test +2250700000000  "Message de test"
php briko sms:otp  +2250700000000  # Générer et envoyer un OTP

# Aide
php briko help
```

---

## Helpers globaux

Disponibles dans tout le code sans `use` ni `import`.

```php
// Lire une variable d'environnement
env('APP_NAME');
env('DB_HOST', '127.0.0.1');  // avec valeur par défaut

// Query Builder raccourci
db('users')->where('active', 1)->get();
// équivalent de DB::table('users')->where('active', 1)->get()

// Envoi SMS raccourci
sms('+2250700000000')->send('Bonjour');
// équivalent de SMS::to('+2250700000000')->send('Bonjour')

// Log raccourci
logger('Événement important', ['key' => 'value']);
logger('Erreur critique', ['exception' => $e->getMessage()], 'error');

// Chemin absolu depuis la racine du projet
base_path('storage/logs');
base_path('app/config.php');
```

---

## Générateurs de code

### Controller

```bash
php briko fabrique:controller PaiementController
```

Génère `app/controllers/PaiementController.php` avec les 5 méthodes REST :

```php
index()    → GET    /ressource
show()     → GET    /ressource/{id}
store()    → POST   /ressource
update()   → PUT    /ressource/{id}
destroy()  → DELETE /ressource/{id}
```

### Model

```bash
php briko fabrique:model Produit
```

Génère `app/models/Produit.php` avec les méthodes statiques :

```php
Produit::all();
Produit::find(1);
Produit::where('categorie', 'electronique');
Produit::create(['nom' => 'iPhone', 'prix' => 350000]);
Produit::update(1, ['prix' => 320000]);
Produit::delete(1);
```

---

## Configuration — .env

Toutes les variables de configuration se trouvent dans le fichier `.env` à la racine du projet.

### Initialisation rapide

```bash
php briko env:setup
```

Copie `.env.example` vers `.env`. À faire **une seule fois** lors de l'installation.

### Variables essentielles

```env
APP_NAME=MonApp
APP_ENV=local           # local | production
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_LEVEL=DEBUG         # DEBUG | INFO | WARNING | ERROR | CRITICAL

# Base de données
DB_DRIVER=mysql         # mysql | pgsql | sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ma_base
DB_USER=root
DB_PASS=

# Mail (voir section mail ci-dessous)
MAIL_DRIVER=log

# SMS (voir section sms ci-dessous)
SMS_DRIVER=log
```

---

## Migrations

Le système de migrations gère l'évolution du schéma de ta base de données via des fichiers PHP versionnés.

### Structure d'un fichier de migration

Les fichiers se placent dans `app/migrations/` avec le format `YYYY_MM_DD_HHMMSS_nom.php` :

```php
<?php
// app/migrations/2024_01_15_120000_create_users_table.php

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE users (
                id       INT AUTO_INCREMENT PRIMARY KEY,
                nom      VARCHAR(100) NOT NULL,
                email    VARCHAR(150) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS users");
    },
];
```

### Commandes CLI

```bash
# Exécuter toutes les migrations en attente
php briko migrate

# Voir l'état de chaque migration (pending / done)
php briko migrate:status

# Annuler le dernier batch
php briko migrate:rollback

# Supprimer toutes les tables et tout rejouer depuis zéro
php briko migrate:fresh
```

### Exemple : sortie de migrate:status

```
  ┌─────────────────────────────────────────────────┬────────┬───────┐
  │ Migration                                       │ Status │ Batch │
  ├─────────────────────────────────────────────────┼────────┼───────┤
  │ 2024_01_15_120000_create_users_table            │ done   │     1 │
  │ 2024_02_01_090000_add_phone_to_users            │ done   │     2 │
  │ 2024_03_10_140000_create_orders_table           │pending │       │
  └─────────────────────────────────────────────────┴────────┴───────┘
```

---

## Mailing

Le module `mail/` gère l'envoi d'emails avec 4 drivers interchangeables. Aucune dépendance externe — SMTP natif via sockets PHP.

### Configuration .env

```env
MAIL_DRIVER=log
# Drivers : log | smtp | sendgrid | mailgun

MAIL_FROM_ADDRESS=noreply@monapp.ci
MAIL_FROM_NAME="Mon Application"
MAIL_REPLY_TO=

# SMTP (Gmail, OVH, Amazon SES, Orange CI...)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ton@gmail.com
MAIL_PASSWORD=ton_mot_de_passe_app
MAIL_ENCRYPTION=tls
# tls (port 587) | ssl (port 465) | none (port 25)

# SendGrid
SENDGRID_API_KEY=SG.xxxxx

# Mailgun
MAILGUN_API_KEY=key-xxxxx
MAILGUN_DOMAIN=mg.monapp.ci
MAILGUN_REGION=us
# us | eu
```

### Drivers disponibles

| Driver      | Quand l'utiliser                                        |
|-------------|--------------------------------------------------------|
| `log`       | Développement — emails écrits dans les logs, rien envoyé |
| `smtp`      | Serveur SMTP : Gmail, OVH, Outlook, Amazon SES SMTP    |
| `sendgrid`  | API SendGrid (volume + deliverability)                 |
| `mailgun`   | API Mailgun (transactions, région US/EU)               |

### Envoi simple — Mail facade

```php
use Briko\Mail\Mail;

// HTML brut
Mail::to('aya@abidjan.ci')
    ->subject('Confirmation commande')
    ->html('<h1>Merci pour ta commande !</h1>')
    ->send();

// Avec CC / BCC / ReplyTo
Mail::to('client@ci.ci')
    ->cc('copie@ci.ci')
    ->bcc('archive@ci.ci')
    ->replyTo('support@ci.ci')
    ->subject('Votre facture')
    ->html($html)
    ->text($texte)
    ->send();

// Multi-destinataires
Mail::to(['a@ci.ci', 'b@ci.ci'])
    ->subject('Newsletter')
    ->html($html)
    ->send();
```

### Helper global

```php
mail_to('user@ci.ci')
    ->subject('Bienvenue')
    ->html('<p>Bonjour !</p>')
    ->send();
```

### Templates PHP — view()

Les templates se placent dans `app/mails/`. La méthode `view()` charge le template et injecte les variables.

```php
Mail::to($user['email'])
    ->subject('Bienvenue !')
    ->view('welcome', ['user' => $user])
    ->send();
```

`app/mails/welcome.php` reçoit la variable `$user` :

```html
<!DOCTYPE html>
<html>
<body>
  <h1>Bienvenue, <?= htmlspecialchars($user['nom']) ?> !</h1>
  <p>Ton compte a été créé avec succès.</p>
</body>
</html>
```

### Mailable — emails structurés

Pour les emails complexes ou réutilisables, crée une classe `Mailable` :

```bash
php briko fabrique:mail Welcome
```

Génère `app/mailables/WelcomeMail.php` :

```php
<?php
namespace App\mailables;

use Briko\Mail\Mail;
use Briko\Mail\Mailable;
use Briko\Mail\MailMessage;

class WelcomeMail extends Mailable
{
    public function __construct(private array $user) {}

    public function build(): MailMessage
    {
        return Mail::to($this->user['email'])
            ->subject('Bienvenue sur ' . env('APP_NAME'))
            ->view('welcome', ['user' => $this->user]);
    }
}
```

Envoi :

```php
use Briko\Mail\Mail;
use App\mailables\WelcomeMail;

Mail::send(new WelcomeMail($user));
```

### Pièces jointes

```php
Mail::to('client@ci.ci')
    ->subject('Votre facture')
    ->html($html)
    ->attach(base_path('storage/factures/facture-1042.pdf'))
    ->attach(base_path('storage/cgv.pdf'), 'Conditions générales', 'application/pdf')
    ->send();
```

### Résultat d'envoi — MailResult

```php
$result = Mail::to('user@ci.ci')
    ->subject('Test')
    ->html('<p>Ok</p>')
    ->send();

if ($result->success) {
    echo 'Envoyé — ID : ' . $result->messageId;
} else {
    echo 'Échec : ' . $result->error;
}
```

### Commandes CLI mail

```bash
# Envoyer un email de test (vérifie la config)
php briko mail:test ton@email.ci

# Afficher le driver actif et sa configuration
php briko mail:driver

# Générer un Mailable + template
php briko fabrique:mail CommandeConfirmee
```

### Exemple de sortie mail:driver

```
  ╔══════════════════════════════════════╗
  ║     Driver Mail actif : smtp         ║
  ╚══════════════════════════════════════╝

  De       : noreply@monapp.ci (Mon Application)
  Config   :
    Host       : smtp.gmail.com
    Port       : 587
    Encryption : tls
    Username   : ✅ défini
    Password   : ✅ défini
```

---

## Générateurs de code

### Mailable

```bash
php briko fabrique:mail NomDuMail
```

Génère `app/mailables/NomDuMailMail.php` + `app/mails/nomdumail.php`.

---

## Licence

MIT — Fait avec 🔥 en Côte d'Ivoire.
