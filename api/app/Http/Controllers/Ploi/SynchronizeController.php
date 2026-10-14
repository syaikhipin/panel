<?php

namespace App\Http\Controllers\Ploi;

use App\Models\ProvisionedResource;
use Illuminate\Http\JsonResponse;

class SynchronizeController extends PloiController
{
    public function servers(): JsonResponse
    {
        return $this->many(ProvisionedResource::query()->ofType('server')->get());
    }

    public function server(int $id): JsonResponse
    {
        return $this->one($this->findOrFail('server', $id));
    }

    public function sites(): JsonResponse
    {
        return $this->many(ProvisionedResource::query()->ofType('site')->get());
    }

    public function site(int $id): JsonResponse
    {
        return $this->one($this->findOrFail('site', $id));
    }

    private function findOrFail(string $type, int $id): ProvisionedResource
    {
        return ProvisionedResource::query()->ofType($type)->findOrFail($id);
    }
}
