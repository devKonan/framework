<?php
namespace Briko\grenier;

class OfflineQueue
{
    private static function path(): string
    {
        $dir = base_path('storage/queue');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/offline.json';
    }

    public static function push(string $method, string $uri, array $payload): string
    {
        $items  = self::all();
        $id     = uniqid('briko_', true);
        $items[] = [
            'id'        => $id,
            'method'    => strtoupper($method),
            'uri'       => $uri,
            'payload'   => $payload,
            'queued_at' => date('Y-m-d H:i:s'),
            'attempts'  => 0,
            'status'    => 'pending',
        ];
        file_put_contents(self::path(), json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $id;
    }

    public static function pending(): array
    {
        return array_values(array_filter(self::all(), fn ($item) => $item['status'] === 'pending'));
    }

    public static function all(): array
    {
        $path = self::path();
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?? [];
    }

    public static function count(): int
    {
        return count(self::pending());
    }

    public static function markDone(string $id): void
    {
        self::updateItem($id, ['status' => 'done', 'synced_at' => date('Y-m-d H:i:s')]);
    }

    public static function markFailed(string $id, string $reason = ''): void
    {
        self::updateItem($id, [
            'status'        => 'failed',
            'fail_reason'   => $reason,
            'last_attempt'  => date('Y-m-d H:i:s'),
        ]);
    }

    public static function incrementAttempt(string $id): void
    {
        $items = self::all();
        foreach ($items as &$item) {
            if ($item['id'] === $id) {
                $item['attempts']++;
            }
        }
        self::save($items);
    }

    public static function flush(): void
    {
        self::save([]);
    }

    private static function updateItem(string $id, array $fields): void
    {
        $items = self::all();
        foreach ($items as &$item) {
            if ($item['id'] === $id) {
                $item = array_merge($item, $fields);
                $item['attempts']++;
            }
        }
        self::save($items);
    }

    private static function save(array $items): void
    {
        file_put_contents(self::path(), json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
