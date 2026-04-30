<?php
namespace Briko\gbaka\Middleware;

use Briko\gbaka\Request;
use Briko\gbaka\Response;

class LowBandwidth implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        $response = $next($request);

        if (!is_array($response)) {
            return $response;
        }

        $original = $response;

        // Sélection de champs : ?fields=id,name,email
        $fields = $request->wantsFields();
        if ($fields) {
            $response = $this->selectFields($response, $fields);
        }

        // Strip des nulls/vides : toujours en low-bandwidth
        if ($request->isLowBandwidth()) {
            $response = $this->stripNulls($response);
        }

        // Active la compression gzip dans Response
        Response::enableCompression($request);

        // Headers informatifs (taille estimée avant/après)
        $originalSize = strlen(json_encode($original, JSON_UNESCAPED_UNICODE));
        $compactSize  = strlen(json_encode($response, JSON_UNESCAPED_UNICODE));
        header('X-Bandwidth-Mode: low');
        header('X-Payload-Original: ' . $originalSize . 'B');
        header('X-Payload-Compact: ' . $compactSize . 'B');
        header('X-Payload-Saved: ' . max(0, $originalSize - $compactSize) . 'B');

        return $response;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function selectFields(array $data, array $fields): array
    {
        $pick = array_flip($fields);

        // Liste d'objets
        if (isset($data[0]) && is_array($data[0])) {
            return array_values(array_map(
                fn ($item) => array_intersect_key($item, $pick),
                $data
            ));
        }

        // Objet imbriqué avec clé 'data'
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = $this->selectFields($data['data'], $fields);
            return $data;
        }

        // Objet simple
        return array_intersect_key($data, $pick);
    }

    private function stripNulls(array $data): array
    {
        // Liste d'objets
        if (isset($data[0]) && is_array($data[0])) {
            return array_values(array_map(fn ($item) => $this->filterItem($item), $data));
        }

        // Objet imbriqué avec clé 'data'
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = $this->stripNulls($data['data']);
            return $data;
        }

        return $this->filterItem($data);
    }

    private function filterItem(array $item): array
    {
        return array_filter(
            $item,
            fn ($v) => $v !== null && $v !== '' && $v !== []
        );
    }
}
