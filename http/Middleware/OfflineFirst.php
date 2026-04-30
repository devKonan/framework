<?php
namespace Briko\Http\Middleware;

use Briko\Http\Request;
use Briko\Database\OfflineQueue;
use Briko\Database\ResponseCache;

class OfflineFirst implements MiddlewareInterface
{
    // Durée de vie du cache en secondes (défaut 5 minutes)
    private int $ttl;

    public function __construct(int $ttl = 300)
    {
        $this->ttl = $ttl;
    }

    public function handle(Request $request, callable $next): mixed
    {
        try {
            $response = $next($request);

            // Cache toute réponse GET réussie
            if ($request->method === 'GET' && is_array($response)) {
                ResponseCache::set($request->uri, $response, $this->ttl);
            }

            // Invalidate le cache si une écriture réussit sur la même URI
            if (in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                ResponseCache::forget($request->uri);
            }

            return $response;

        } catch (\RuntimeException $e) {
            return $this->handleOffline($request, $e);
        }
    }

    private function handleOffline(Request $request, \RuntimeException $e): array
    {
        if ($request->method === 'GET') {
            return $this->serveFromCache($request);
        }

        return $this->queueWrite($request);
    }

    private function serveFromCache(Request $request): array
    {
        $cached    = ResponseCache::get($request->uri);
        $cachedAt  = ResponseCache::cachedAt($request->uri);

        if ($cached !== null) {
            $payload = is_array($cached) ? $cached : ['data' => $cached];
            return array_merge($payload, [
                '_offline'   => true,
                '_cached_at' => $cachedAt,
                '_message'   => 'Données en cache — connexion indisponible',
            ]);
        }

        return [
            '_offline' => true,
            'error'    => 'Service indisponible et aucun cache trouvé pour cette ressource',
        ];
    }

    private function queueWrite(Request $request): array
    {
        $id = OfflineQueue::push(
            $request->method,
            $request->uri,
            $request->all()
        );

        return [
            '_offline' => true,
            'queued'   => true,
            'sync_id'  => $id,
            'message'  => 'Requête enfilée — sera synchronisée à la reconnexion (php briko sync)',
            'pending'  => OfflineQueue::count(),
        ];
    }
}
