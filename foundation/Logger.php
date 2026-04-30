<?php
namespace Briko\Foundation;

class Logger
{
    const DEBUG    = 'DEBUG';
    const INFO     = 'INFO';
    const WARNING  = 'WARNING';
    const ERROR    = 'ERROR';
    const CRITICAL = 'CRITICAL';

    private static string $requestId = '';
    private static float  $startTime = 0.0;

    private static array $levelOrder = [
        self::DEBUG    => 0,
        self::INFO     => 1,
        self::WARNING  => 2,
        self::ERROR    => 3,
        self::CRITICAL => 4,
    ];

    // Appelé une fois au boot de l'app
    public static function boot(): void
    {
        self::$requestId = substr(md5(uniqid('', true)), 0, 10);
        self::$startTime = microtime(true);
    }

    // Retourne un canal nommé
    public static function channel(string $channel): LogChannel
    {
        return new LogChannel($channel);
    }

    // Raccourcis sur le canal 'app'
    public static function debug(string $msg, array $ctx = []): void    { static::write(self::DEBUG, 'app', $msg, $ctx); }
    public static function info(string $msg, array $ctx = []): void     { static::write(self::INFO, 'app', $msg, $ctx); }
    public static function warning(string $msg, array $ctx = []): void  { static::write(self::WARNING, 'app', $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void    { static::write(self::ERROR, 'app', $msg, $ctx); }
    public static function critical(string $msg, array $ctx = []): void { static::write(self::CRITICAL, 'app', $msg, $ctx); }

    public static function write(string $level, string $channel, string $msg, array $ctx = []): void
    {
        $minLevel = Env::get('LOG_LEVEL', self::DEBUG);
        if (!static::shouldLog($level, $minLevel)) return;

        $entry = [
            'ts'         => date('Y-m-d H:i:s'),
            'request_id' => self::$requestId,
            'level'      => $level,
            'channel'    => $channel,
            'message'    => $msg,
            'elapsed_ms' => round((microtime(true) - self::$startTime) * 1000, 2),
            'memory_kb'  => round(memory_get_usage() / 1024),
        ];

        if (!empty($ctx)) {
            $entry['context'] = $ctx;
        }

        $dir = base_path('storage/logs');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        // Fichier par canal + rotation journalière
        file_put_contents($dir . '/' . date('Y-m-d') . '-' . $channel . '.log', $line, FILE_APPEND | LOCK_EX);

        // Fichier combiné (tous canaux)
        file_put_contents($dir . '/' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);

        // CRITICAL → stderr (visible dans les logs système/docker)
        if ($level === self::CRITICAL) {
            fwrite(STDERR, "[CRITICAL][$channel] $msg\n");
        }
    }

    public static function requestId(): string
    {
        return self::$requestId;
    }

    public static function elapsed(): float
    {
        return round((microtime(true) - self::$startTime) * 1000, 2);
    }

    private static function shouldLog(string $level, string $minLevel): bool
    {
        $current = self::$levelOrder[$level]    ?? 0;
        $minimum = self::$levelOrder[$minLevel] ?? 0;
        return $current >= $minimum;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Canal nommé  (Logger::channel('http')->info(...))
// ─────────────────────────────────────────────────────────────────────────────

class LogChannel
{
    public function __construct(private string $channel) {}

    public function debug(string $msg, array $ctx = []): void    { Logger::write(Logger::DEBUG,    $this->channel, $msg, $ctx); }
    public function info(string $msg, array $ctx = []): void     { Logger::write(Logger::INFO,     $this->channel, $msg, $ctx); }
    public function warning(string $msg, array $ctx = []): void  { Logger::write(Logger::WARNING,  $this->channel, $msg, $ctx); }
    public function error(string $msg, array $ctx = []): void    { Logger::write(Logger::ERROR,    $this->channel, $msg, $ctx); }
    public function critical(string $msg, array $ctx = []): void { Logger::write(Logger::CRITICAL, $this->channel, $msg, $ctx); }
}
