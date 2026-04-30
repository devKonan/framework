<?php
namespace Briko\djassa;

use Briko\core\App;
use Briko\grenier\Connection;
use PDO;

class Console
{
    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';

        switch ($command) {
            case 'feu':
                $this->serve();
                break;
            case 'fabrique:controller':
                $this->makeController($argv[2] ?? 'DemoController');
                break;
            case 'fabrique:model':
                $this->makeModel($argv[2] ?? 'Demo');
                break;
            case 'migrate':
                $this->migrate();
                break;
            case 'migrate:status':
                $this->migrateStatus();
                break;
            case 'migrate:rollback':
                $this->migrateRollback();
                break;
            case 'migrate:fresh':
                $this->migrateFresh();
                break;
            case 'sync':
                $this->sync();
                break;
            case 'sync:status':
                $this->syncStatus();
                break;
            case 'sync:flush':
                $this->syncFlush();
                break;
            case 'logs':
                $this->logs($argv[2] ?? null, (int) ($argv[3] ?? 50));
                break;
            case 'logs:tail':
                $this->logsTail($argv[2] ?? null);
                break;
            case 'logs:clear':
                $this->logsClear();
                break;
            case 'sms:test':
                $this->smsTest($argv[2] ?? null, $argv[3] ?? 'Test Brikocode 🔥');
                break;
            case 'sms:driver':
                $this->smsDriver();
                break;
            case 'sms:otp':
                $this->smsOtp($argv[2] ?? null);
                break;
            case 'mail:test':
                $this->mailTest($argv[2] ?? null);
                break;
            case 'mail:driver':
                $this->mailDriver();
                break;
            case 'fabrique:mail':
                $this->makeMail($argv[2] ?? 'Welcome');
                break;
            case 'env:setup':
                $this->envSetup();
                break;
            case 'help':
            default:
                $this->help();
        }
    }

    private function help(): void
    {
        echo "\n";
        echo "  ╔══════════════════════════════════════╗\n";
        echo "  ║     Brikocode CLI  — djassa 🔥       ║\n";
        echo "  ╚══════════════════════════════════════╝\n\n";
        echo "  Commandes disponibles :\n\n";
        echo "    php briko feu                          Démarrer le serveur de dev\n";
        echo "    php briko fabrique:controller <Nom>    Créer un controller\n";
        echo "    php briko fabrique:model <Nom>         Créer un model\n\n";
        echo "    php briko migrate                      Exécuter les migrations en attente\n";
        echo "    php briko migrate:status               Voir l'état des migrations\n";
        echo "    php briko migrate:rollback             Annuler le dernier batch\n";
        echo "    php briko migrate:fresh                Rejouer toutes les migrations\n\n";
        echo "    php briko sync                         Rejouer les requêtes offline en attente\n";
        echo "    php briko sync:status                  Voir les requêtes en file d'attente\n";
        echo "    php briko sync:flush                   Vider la file d'attente offline\n\n";
        echo "    php briko logs [canal] [n]             Afficher les N dernières lignes de log\n";
        echo "    php briko logs:tail [canal]            Suivre les logs en temps réel\n";
        echo "    php briko logs:clear                   Supprimer tous les fichiers de log\n\n";
        echo "    php briko sms:test <numéro> [message]  Envoyer un SMS de test\n";
        echo "    php briko sms:otp <numéro>             Générer et envoyer un OTP\n";
        echo "    php briko sms:driver                   Voir le driver SMS actif\n\n";
        echo "    php briko mail:test <email>            Envoyer un email de test\n";
        echo "    php briko mail:driver                  Voir le driver Mail actif\n";
        echo "    php briko fabrique:mail <Nom>          Créer un Mailable\n\n";
        echo "    php briko env:setup                    Créer .env depuis .env.example\n\n";
        echo "    php briko help                         Afficher cette aide\n\n";
    }

    private function serve(): void
    {
        $public = realpath(__DIR__ . '/../public');
        echo "🔥 Brikocode — serveur lancé sur http://localhost:8000\n";
        passthru('php -S localhost:8000 -t ' . escapeshellarg($public));
    }

    private function makeController(string $name): void
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name);
        $path = __DIR__ . '/../village/controllers/' . $name . '.php';

        if (file_exists($path)) {
            echo "⚠️  Controller déjà existant : $name\n";
            return;
        }

        $tpl = '<?php' . "\n"
            . 'namespace Briko\village\controllers;' . "\n\n"
            . 'use Briko\gbaka\Request;' . "\n"
            . 'use Briko\gbaka\Response;' . "\n\n"
            . "class $name\n{\n"
            . "    public function index(Request \$request): array\n"
            . "    {\n"
            . "        return ['message' => '$name opérationnel'];\n"
            . "    }\n\n"
            . "    public function show(Request \$request): array\n"
            . "    {\n"
            . "        \$id = \$request->param('id');\n"
            . "        return ['id' => \$id];\n"
            . "    }\n\n"
            . "    public function store(Request \$request): array\n"
            . "    {\n"
            . "        \$data = \$request->all();\n"
            . "        return ['created' => true, 'data' => \$data];\n"
            . "    }\n\n"
            . "    public function update(Request \$request): array\n"
            . "    {\n"
            . "        \$id   = \$request->param('id');\n"
            . "        \$data = \$request->all();\n"
            . "        return ['updated' => true, 'id' => \$id];\n"
            . "    }\n\n"
            . "    public function destroy(Request \$request): array\n"
            . "    {\n"
            . "        \$id = \$request->param('id');\n"
            . "        return ['deleted' => true, 'id' => \$id];\n"
            . "    }\n"
            . "}\n";

        file_put_contents($path, $tpl);
        echo "✅ Controller créé : village/controllers/$name.php\n";
    }

    private function makeModel(string $name): void
    {
        $name  = preg_replace('/[^A-Za-z0-9_]/', '', $name);
        $table = strtolower($name) . 's';

        if (!is_dir(__DIR__ . '/../village/models')) {
            mkdir(__DIR__ . '/../village/models', 0755, true);
        }

        $path = __DIR__ . '/../village/models/' . $name . '.php';

        if (file_exists($path)) {
            echo "⚠️  Model déjà existant : $name\n";
            return;
        }

        $tpl = '<?php' . "\n"
            . 'namespace Briko\village\models;' . "\n\n"
            . 'use Briko\grenier\DB;' . "\n\n"
            . "class $name\n{\n"
            . "    protected static string \$table = '$table';\n\n"
            . "    public static function all(): array\n"
            . "    {\n"
            . "        return DB::table(static::\$table)->get();\n"
            . "    }\n\n"
            . "    public static function find(int|string \$id): ?array\n"
            . "    {\n"
            . "        return DB::table(static::\$table)->find(\$id);\n"
            . "    }\n\n"
            . "    public static function where(string \$col, mixed \$val): array\n"
            . "    {\n"
            . "        return DB::table(static::\$table)->where(\$col, \$val)->get();\n"
            . "    }\n\n"
            . "    public static function create(array \$data): int|string\n"
            . "    {\n"
            . "        return DB::table(static::\$table)->insertGetId(\$data);\n"
            . "    }\n\n"
            . "    public static function update(int|string \$id, array \$data): int\n"
            . "    {\n"
            . "        return DB::table(static::\$table)->where('id', \$id)->update(\$data);\n"
            . "    }\n\n"
            . "    public static function delete(int|string \$id): int\n"
            . "    {\n"
            . "        return DB::table(static::\$table)->where('id', \$id)->delete();\n"
            . "    }\n"
            . "}\n";

        file_put_contents($path, $tpl);
        echo "✅ Model créé : village/models/$name.php\n";
    }

    private function sync(): void
    {
        $pending = \Briko\grenier\OfflineQueue::pending();

        if (empty($pending)) {
            echo "✅ Aucune requête en attente.\n";
            return;
        }

        echo "🔄 Synchronisation de " . count($pending) . " requête(s) en attente...\n\n";

        $app    = new \Briko\core\App();
        $kernel = new \Briko\gbaka\Kernel($app);

        $done = $fail = 0;
        foreach ($pending as $item) {
            echo "  → [{$item['method']}] {$item['uri']} (id: {$item['id']})... ";
            try {
                $req      = \Briko\gbaka\Request::fromArray($item['method'], $item['uri'], $item['payload']);
                $response = $kernel->dispatch($req);

                if (isset($response['error'])) {
                    throw new \RuntimeException($response['error']);
                }

                \Briko\grenier\OfflineQueue::markDone($item['id']);
                echo "✅ OK\n";
                $done++;
            } catch (\Throwable $e) {
                \Briko\grenier\OfflineQueue::markFailed($item['id'], $e->getMessage());
                echo "❌ Échec : {$e->getMessage()}\n";
                $fail++;
            }
        }

        echo "\n  Résultat : $done synchronisée(s), $fail échouée(s).\n";
    }

    private function syncStatus(): void
    {
        $all     = \Briko\grenier\OfflineQueue::all();
        $pending = array_filter($all, fn ($i) => $i['status'] === 'pending');
        $done    = array_filter($all, fn ($i) => $i['status'] === 'done');
        $failed  = array_filter($all, fn ($i) => $i['status'] === 'failed');
        $cache   = \Briko\grenier\ResponseCache::stats();

        echo "\n  📦 File d'attente offline\n";
        echo "  ─────────────────────────\n";
        echo "  En attente  : " . count($pending) . "\n";
        echo "  Synchronisé : " . count($done) . "\n";
        echo "  Échoué      : " . count($failed) . "\n\n";

        if (!empty($pending)) {
            echo "  Détail des requêtes en attente :\n";
            foreach ($pending as $item) {
                echo "    [{$item['method']}] {$item['uri']} — enfilée le {$item['queued_at']}\n";
            }
            echo "\n";
        }

        echo "  🗂  Cache réponses : {$cache['entries']} entrée(s) ({$cache['size_kb']} KB)\n\n";
    }

    private function syncFlush(): void
    {
        \Briko\grenier\OfflineQueue::flush();
        echo "🗑  File d'attente offline vidée.\n";
    }

    // ─── Migrations ───────────────────────────────────────────────────────────

    private function migrate(): void
    {
        $pdo = $this->migrationPdo();
        if (!$pdo) return;

        $this->ensureMigrationTable($pdo);

        $files     = $this->migrationFiles();
        $executed  = $this->executedMigrations($pdo);
        $pending   = array_values(array_filter($files, fn (string $file) => !isset($executed[basename($file, '.php')])));

        if (empty($pending)) {
            echo "✅ Aucune migration en attente.\n";
            return;
        }

        $batch = $this->nextBatch($pdo);
        echo "\n  🧱 Migration batch #$batch\n";

        $done = 0;
        foreach ($pending as $file) {
            $name = basename($file, '.php');
            echo "  → $name ... ";

            try {
                $migration = $this->loadMigration($file);

                $pdo->beginTransaction();
                $this->callMigrationStep($migration['up'], $pdo);

                $stmt = $pdo->prepare('INSERT INTO briko_migrations (migration, batch, ran_at) VALUES (?, ?, ?)');
                $stmt->execute([$name, $batch, date('c')]);

                $pdo->commit();
                echo "✅\n";
                $done++;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "❌ {$e->getMessage()}\n";
                break;
            }
        }

        echo "\n  Résultat : $done migration(s) exécutée(s).\n\n";
    }

    private function migrateStatus(): void
    {
        $pdo = $this->migrationPdo();
        if (!$pdo) return;

        $this->ensureMigrationTable($pdo);
        $files    = $this->migrationFiles();
        $executed = $this->executedMigrations($pdo);

        if (empty($files)) {
            echo "⚠️  Aucun fichier de migration trouvé dans grenier/migrations.\n";
            return;
        }

        echo "\n  📋 État des migrations\n";
        echo "  " . str_repeat('─', 72) . "\n";

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $row  = $executed[$name] ?? null;

            if ($row) {
                $batch = str_pad((string) $row['batch'], 4, ' ', STR_PAD_LEFT);
                echo "  ✅ $name  (batch $batch, {$row['ran_at']})\n";
                continue;
            }

            echo "  ⏳ $name  (pending)\n";
        }

        echo "\n";
    }

    private function migrateRollback(): void
    {
        $pdo = $this->migrationPdo();
        if (!$pdo) return;

        $this->ensureMigrationTable($pdo);
        $batch = $this->latestBatch($pdo);

        if ($batch === null) {
            echo "✅ Aucun batch à annuler.\n";
            return;
        }

        $filesMap = [];
        foreach ($this->migrationFiles() as $file) {
            $filesMap[basename($file, '.php')] = $file;
        }

        $stmt = $pdo->prepare('SELECT migration FROM briko_migrations WHERE batch = ? ORDER BY migration DESC');
        $stmt->execute([$batch]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($rows)) {
            echo "✅ Aucun batch à annuler.\n";
            return;
        }

        echo "\n  ↩️  Rollback batch #$batch\n";

        $done = 0;
        foreach ($rows as $row) {
            $name = $row['migration'];
            echo "  → $name ... ";

            if (!isset($filesMap[$name])) {
                echo "❌ fichier de migration manquant\n";
                break;
            }

            try {
                $migration = $this->loadMigration($filesMap[$name]);

                $pdo->beginTransaction();
                $this->callMigrationStep($migration['down'], $pdo);

                $del = $pdo->prepare('DELETE FROM briko_migrations WHERE migration = ?');
                $del->execute([$name]);

                $pdo->commit();
                echo "✅\n";
                $done++;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "❌ {$e->getMessage()}\n";
                break;
            }
        }

        echo "\n  Résultat : $done migration(s) annulée(s).\n\n";
    }

    private function migrateFresh(): void
    {
        $pdo = $this->migrationPdo();
        if (!$pdo) return;

        $this->ensureMigrationTable($pdo);

        while (($batch = $this->latestBatch($pdo)) !== null) {
            $filesMap = [];
            foreach ($this->migrationFiles() as $file) {
                $filesMap[basename($file, '.php')] = $file;
            }

            $stmt = $pdo->prepare('SELECT migration FROM briko_migrations WHERE batch = ? ORDER BY migration DESC');
            $stmt->execute([$batch]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $row) {
                $name = $row['migration'];
                if (!isset($filesMap[$name])) {
                    throw new \RuntimeException("Migration introuvable pour rollback: $name");
                }

                $migration = $this->loadMigration($filesMap[$name]);

                $pdo->beginTransaction();
                $this->callMigrationStep($migration['down'], $pdo);

                $del = $pdo->prepare('DELETE FROM briko_migrations WHERE migration = ?');
                $del->execute([$name]);
                $pdo->commit();
            }
        }

        echo "🧼 Base remise à zéro. Relance des migrations...\n";
        $this->migrate();
    }

    private function migrationFiles(): array
    {
        $dir = base_path('grenier/migrations');
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.php') ?: [];
        sort($files);
        return $files;
    }

    private function migrationPdo(): ?PDO
    {
        try {
            new App();
            return Connection::get();
        } catch (\Throwable $e) {
            echo "❌ Connexion DB impossible : {$e->getMessage()}\n";
            echo "   Vérifie DB_DRIVER / DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS dans .env\n";
            return null;
        }
    }

    private function ensureMigrationTable(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS briko_migrations (
            migration VARCHAR(255) PRIMARY KEY,
            batch INTEGER NOT NULL,
            ran_at VARCHAR(32) NOT NULL
        )');
    }

    private function executedMigrations(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT migration, batch, ran_at FROM briko_migrations ORDER BY batch, migration')
            ->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $result[$row['migration']] = $row;
        }

        return $result;
    }

    private function latestBatch(PDO $pdo): ?int
    {
        $value = $pdo->query('SELECT MAX(batch) FROM briko_migrations')->fetchColumn();
        if ($value === false || $value === null) {
            return null;
        }

        return (int) $value;
    }

    private function nextBatch(PDO $pdo): int
    {
        return ($this->latestBatch($pdo) ?? 0) + 1;
    }

    private function loadMigration(string $file): array
    {
        $migration = require $file;

        if (!is_array($migration) || !isset($migration['up'], $migration['down'])) {
            throw new \RuntimeException('Migration invalide : ' . basename($file));
        }

        if (!is_callable($migration['up']) || !is_callable($migration['down'])) {
            throw new \RuntimeException('Migration invalide (up/down non callable) : ' . basename($file));
        }

        return $migration;
    }

    private function callMigrationStep(callable $step, PDO $pdo): void
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($step));
        if ($reflection->getNumberOfParameters() > 0) {
            $step($pdo);
            return;
        }

        $step();
    }

    // ─── Logs ─────────────────────────────────────────────────────────────────

    private function logs(?string $channel, int $lines = 50): void
    {
        $file = $this->resolveLogFile($channel);
        if (!$file) {
            echo "  Aucun fichier de log trouvé pour aujourd'hui.\n";
            return;
        }

        $entries = $this->readLastLines($file, $lines);
        if (empty($entries)) {
            echo "  Aucune entrée dans ce fichier.\n";
            return;
        }

        echo "\n  📋 " . basename($file) . " — {$lines} dernières lignes\n";
        echo "  " . str_repeat('─', 70) . "\n\n";

        foreach ($entries as $raw) {
            $entry = json_decode($raw, true);
            if (!$entry) continue;

            $color   = $this->levelColor($entry['level'] ?? 'INFO');
            $reset   = "\033[0m";
            $dim     = "\033[2m";
            $level   = str_pad($entry['level'] ?? '?', 8);
            $channel = str_pad($entry['channel'] ?? 'app', 8);
            $ts      = substr($entry['ts'] ?? '', 11, 8); // HH:MM:SS
            $msg     = $entry['message'] ?? '';
            $ms      = $entry['elapsed_ms'] ?? '';
            $rid     = $entry['request_id'] ?? '';
            $mem     = $entry['memory_kb'] ?? '';

            echo "  {$dim}{$ts}{$reset} {$color}{$level}{$reset} {$dim}[{$channel}]{$reset}  {$msg}";
            echo $ms ? "  {$dim}+{$ms}ms{$reset}" : '';
            echo $mem ? "  {$dim}{$mem}KB{$reset}" : '';
            echo "\n";

            if (!empty($entry['context'])) {
                foreach ($entry['context'] as $k => $v) {
                    $val = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
                    echo "    {$dim}  {$k}: {$val}{$reset}\n";
                }
            }
        }
        echo "\n";
    }

    private function logsTail(?string $channel): void
    {
        $file = $this->resolveLogFile($channel);
        if (!$file) {
            echo "  Aucun fichier de log trouvé. En attente de nouveaux logs...\n";
            $file = base_path('storage/logs/' . date('Y-m-d') . ($channel ? "-$channel" : '') . '.log');
        }

        echo "  🔍 Suivi de " . basename($file) . " (Ctrl+C pour arrêter)\n\n";
        passthru('tail -f ' . escapeshellarg($file));
    }

    private function logsClear(): void
    {
        $dir   = base_path('storage/logs');
        $files = glob($dir . '/*.log') ?: [];
        foreach ($files as $f) unlink($f);
        echo "🗑  " . count($files) . " fichier(s) de log supprimé(s).\n";
    }

    private function resolveLogFile(?string $channel): ?string
    {
        $dir    = base_path('storage/logs');
        $prefix = date('Y-m-d') . ($channel ? "-$channel" : '');
        $file   = $dir . '/' . $prefix . '.log';
        if (file_exists($file)) return $file;

        // Cherche le dernier fichier dispo
        $candidates = glob($dir . '/' . date('Y-m-d') . '*.log') ?: [];
        if (empty($candidates)) {
            $candidates = glob($dir . '/*.log') ?: [];
        }
        if (empty($candidates)) return null;

        usort($candidates, fn ($a, $b) => filemtime($b) - filemtime($a));
        return $candidates[0];
    }

    private function readLastLines(string $file, int $n): array
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_slice($lines, -$n);
    }

    private function levelColor(string $level): string
    {
        return match ($level) {
            'DEBUG'    => "\033[2;37m",
            'INFO'     => "\033[0;32m",
            'WARNING'  => "\033[0;33m",
            'ERROR'    => "\033[0;31m",
            'CRITICAL' => "\033[1;31m",
            default    => "\033[0m",
        };
    }

    // ─── SMS ──────────────────────────────────────────────────────────────────

    private function smsTest(?string $phone, string $message): void
    {
        if (!$phone) {
            echo "  Usage : php briko sms:test <+2250700000000> [message]\n";
            return;
        }

        new \Briko\core\App();

        echo "\n  📱 Envoi SMS de test...\n";
        $result = \Briko\tamtam\SMS::to($phone)->send($message);

        if ($result->isOk()) {
            echo "  ✅ SMS envoyé vers $phone\n";
            echo "     Driver : " . env('SMS_DRIVER', 'log') . "\n";
            if ($result->messageId) echo "     ID     : {$result->messageId}\n";
        } else {
            echo "  ❌ Échec : {$result->info}\n";
        }
        echo "\n";
    }

    private function smsOtp(?string $phone): void
    {
        if (!$phone) {
            echo "  Usage : php briko sms:otp <+2250700000000>\n";
            return;
        }

        new \Briko\core\App();

        echo "\n  🔑 Génération OTP pour $phone...\n";
        $code = \Briko\tamtam\SMS::otp($phone);

        echo "  ✅ OTP généré et envoyé\n";
        echo "     Code   : $code  (valide 5 minutes)\n";
        echo "     Driver : " . env('SMS_DRIVER', 'log') . "\n\n";
        echo "  Vérification :\n";
        echo "    \$ok = SMS::verifyOtp('$phone', '$code'); // true\n\n";
    }

    private function smsDriver(): void
    {
        new \Briko\core\App();

        $driver = env('SMS_DRIVER', 'log');
        $from   = env('SMS_FROM', 'Brikocode');

        echo "\n  📡 Driver SMS actif : $driver\n";
        echo "  Expéditeur par défaut : $from\n\n";

        $details = match ($driver) {
            'africastalking' => [
                'Username'  => env('AT_USERNAME', '—'),
                'API Key'   => env('AT_API_KEY')   ? '✅ défini' : '❌ manquant (AT_API_KEY)',
                'Sandbox'   => env('AT_SANDBOX', 'true') === 'true' ? '✅ oui' : 'non',
            ],
            'twilio' => [
                'SID'   => env('TWILIO_SID')   ? '✅ défini' : '❌ manquant (TWILIO_SID)',
                'Token' => env('TWILIO_TOKEN') ? '✅ défini' : '❌ manquant (TWILIO_TOKEN)',
                'From'  => env('TWILIO_FROM')  ? env('TWILIO_FROM') : '❌ manquant (TWILIO_FROM)',
            ],
            'http' => [
                'URL'          => env('SMS_HTTP_URL', '❌ manquant'),
                'Auth field'   => env('SMS_HTTP_AUTH_FIELD', 'apikey'),
                'Auth value'   => env('SMS_HTTP_AUTH_VALUE') ? '✅ défini' : '— non défini',
                'Success code' => env('SMS_HTTP_SUCCESS_CODE', '200'),
            ],
            default => ['Mode' => 'log — SMS affichés dans les logs, rien envoyé'],
        };

        foreach ($details as $k => $v) {
            echo "    $k : $v\n";
        }
        echo "\n";
    }

    // ─── Mail ─────────────────────────────────────────────────────────────────

    private function mailTest(?string $email): void
    {
        if (!$email) {
            echo "  Usage : php briko mail:test <email@domaine.ci>\n";
            return;
        }

        new \Briko\core\App();

        $appName = env('APP_NAME', 'Brikocode');
        echo "\n  ✉️  Envoi email de test vers $email...\n";

        $result = \Briko\courrier\Mail::to($email)
            ->subject("Test email — $appName")
            ->html("<h2>🔥 $appName fonctionne !</h2><p>Ce message confirme que le driver <strong>" . env('MAIL_DRIVER', 'log') . "</strong> est correctement configuré.</p>")
            ->text("$appName fonctionne ! Driver : " . env('MAIL_DRIVER', 'log'))
            ->send();

        if ($result->isOk()) {
            echo "  ✅ Email envoyé\n";
            echo "     Driver : " . env('MAIL_DRIVER', 'log') . "\n";
            if ($result->messageId) echo "     ID     : {$result->messageId}\n";
        } else {
            echo "  ❌ Échec : {$result->error}\n";
        }
        echo "\n";
    }

    private function mailDriver(): void
    {
        new \Briko\core\App();

        $driver = env('MAIL_DRIVER', 'log');
        $from   = env('MAIL_FROM_ADDRESS', '—');
        $name   = env('MAIL_FROM_NAME', '—');

        echo "\n  📬 Driver Mail actif : $driver\n";
        echo "  From : $name <$from>\n\n";

        $details = match ($driver) {
            'smtp' => [
                'Host'       => env('MAIL_HOST', '—'),
                'Port'       => env('MAIL_PORT', '587'),
                'Encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'Username'   => env('MAIL_USERNAME') ? '✅ défini' : '❌ manquant (MAIL_USERNAME)',
                'Password'   => env('MAIL_PASSWORD') ? '✅ défini' : '❌ manquant (MAIL_PASSWORD)',
            ],
            'sendgrid' => [
                'API Key' => env('SENDGRID_API_KEY') ? '✅ défini' : '❌ manquant (SENDGRID_API_KEY)',
            ],
            'mailgun' => [
                'API Key' => env('MAILGUN_API_KEY')  ? '✅ défini' : '❌ manquant (MAILGUN_API_KEY)',
                'Domain'  => env('MAILGUN_DOMAIN')   ? env('MAILGUN_DOMAIN')  : '❌ manquant (MAILGUN_DOMAIN)',
                'Region'  => env('MAILGUN_REGION', 'us'),
            ],
            default => ['Mode' => 'log — Emails affichés dans les logs, rien envoyé'],
        };

        foreach ($details as $k => $v) {
            echo "    $k : $v\n";
        }
        echo "\n";
    }

    private function makeMail(string $name): void
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name);
        $dir  = base_path('village/mailables');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $path = $dir . '/' . $name . 'Mail.php';
        if (file_exists($path)) {
            echo "⚠️  Mailable déjà existant : {$name}Mail\n";
            return;
        }

        $tpl = '<?php' . "\n"
            . 'namespace Briko\village\mailables;' . "\n\n"
            . 'use Briko\courrier\Mail;' . "\n"
            . 'use Briko\courrier\Mailable;' . "\n"
            . 'use Briko\courrier\MailMessage;' . "\n\n"
            . "class {$name}Mail extends Mailable\n{\n"
            . "    public function __construct(\n"
            . "        private array \$data = []\n"
            . "    ) {}\n\n"
            . "    public function build(): MailMessage\n"
            . "    {\n"
            . "        return Mail::to(\$this->data['email'])\n"
            . "            ->subject('" . $name . " — ' . env('APP_NAME', 'Brikocode'))\n"
            . "            ->view('" . strtolower($name) . "', ['data' => \$this->data]);\n"
            . "            // ou ->html('<h1>Contenu HTML</h1>')\n"
            . "    }\n"
            . "}\n";

        file_put_contents($path, $tpl);

        // Crée aussi le template vue
        $tplDir  = base_path('village/mails');
        $tplFile = $tplDir . '/' . strtolower($name) . '.php';
        if (!file_exists($tplFile)) {
            file_put_contents($tplFile,
                "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>$name</title></head>\n"
                . "<body>\n  <h1>$name</h1>\n  <p>Contenu de l'email...</p>\n</body>\n</html>\n"
            );
            echo "✅ Template créé     : village/mails/" . strtolower($name) . ".php\n";
        }

        echo "✅ Mailable créé     : village/mailables/{$name}Mail.php\n";
        echo "\n  Utilisation :\n";
        echo "    Mail::send(new {$name}Mail(['email' => 'user@ci.ci']));\n\n";
    }

    private function envSetup(): void
    {
        $target  = base_path('.env');
        $example = base_path('.env.example');

        if (!file_exists($example)) {
            echo "❌ .env.example introuvable — impossible de créer .env\n";
            return;
        }

        if (file_exists($target)) {
            echo "⚠️  .env existe déjà. Utilise --force pour écraser :\n";
            echo "     php briko env:setup --force\n\n";

            // Si --force passé globalement via argv
            global $argv;
            if (!in_array('--force', $argv ?? [], true)) {
                return;
            }
            echo "  ⚡ --force détecté, remplacement en cours...\n";
        }

        if (!copy($example, $target)) {
            echo "❌ Impossible de créer .env (permission refusée ?)\n";
            return;
        }

        echo "✅ .env créé depuis .env.example\n\n";
        echo "  Prochaines étapes :\n";
        echo "    1. Ouvre .env et renseigne tes valeurs (DB, MAIL, SMS...)\n";
        echo "    2. Lance le serveur : php briko feu\n\n";
        echo "  Clés importantes :\n";
        echo "    APP_URL          URL de ton application\n";
        echo "    DB_HOST/DB_NAME  Connexion base de données\n";
        echo "    MAIL_DRIVER      log | smtp | sendgrid | mailgun\n";
        echo "    SMS_DRIVER       log | africastalking | twilio | http\n\n";
    }
}
