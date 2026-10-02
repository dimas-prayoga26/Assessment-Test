<?php

namespace App\Support;

use Illuminate\Http\Request;

class Branding
{
    /**
     * @return array{name: string, logo: string}
     */
    public function current(?Request $request = null): array
    {
        return $this->forHost($request?->getHost());
    }

    /**
     * @return array{name: string, logo: string}
     */
    public function forHost(?string $host): array
    {
        $host = strtolower($host ?? '');
        $hosts = config('branding.hosts', []);

        return $hosts[$host] ?? config('branding.default');
    }

    public function logoUrl(?Request $request = null): string
    {
        return asset($this->encodePublicPath($this->current($request)['logo']));
    }

    private function encodePublicPath(string $path): string
    {
        $segments = array_map(rawurlencode(...), explode('/', ltrim($path, '/')));

        return implode('/', $segments);
    }
}
