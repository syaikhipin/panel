<?php

namespace App\DataTransferObjects\Support;

use Illuminate\Pagination\LengthAwarePaginator;

class TransformedDataCollectableResolver extends \Spatie\LaravelData\Resolvers\TransformedDataCollectableResolver
{
    /**
     * Ploi Core's API historically returns paginated responses in the same shape as
     * Laravel's API resources: first/last/prev/next URLs under "links" and the page
     * link collection under "meta.links", instead of the package's default shape.
     *
     * @return array{links: array<string, string|null>, meta: array<string, mixed>}
     */
    protected function resolveLengthAwarePaginatorLinksAndMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection()->toArray(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
