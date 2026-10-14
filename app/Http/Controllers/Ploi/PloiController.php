<?php

namespace App\Http\Controllers\Ploi;

use App\Http\Controllers\Controller;
use App\Models\ProvisionedResource;
use Illuminate\Http\JsonResponse;

abstract class PloiController extends Controller
{
    protected function one(ProvisionedResource $resource): JsonResponse
    {
        return response()->json(['data' => $resource->toApiArray()]);
    }

    /**
     * @param  iterable<int, ProvisionedResource>  $resources
     */
    protected function many(iterable $resources): JsonResponse
    {
        $data = collect($resources)->map(fn (ProvisionedResource $resource) => $resource->toApiArray())->values()->all();

        return response()->json(['data' => $data]);
    }

    protected function created(ProvisionedResource $resource): JsonResponse
    {
        return response()->json(['data' => $resource->toApiArray()], 201);
    }

    protected function destroyed(): JsonResponse
    {
        return response()->json(['data' => true], 200);
    }

    protected function provision(string $type, array $payload, ?ProvisionedResource $server = null, ?ProvisionedResource $site = null): ProvisionedResource
    {
        return ProvisionedResource::create([
            'type' => $type,
            'server_id' => $server?->id,
            'site_id' => $site?->id,
            'initial_status' => $type === 'server' ? ProvisionedResource::STATUS_CREATED : ProvisionedResource::STATUS_BUSY,
            'payload' => $payload,
        ]);
    }
}
