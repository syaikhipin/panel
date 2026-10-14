<?php

namespace App\Http\Controllers\Ploi;

use App\Models\ProvisionedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerController extends PloiController
{
    public function index(): JsonResponse
    {
        return $this->many(ProvisionedResource::query()->ofType('server')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'plan' => ['required', 'string'],
            'region' => ['required', 'string'],
            'credential' => ['required'],
            'type' => ['sometimes', 'string'],
            'database_type' => ['sometimes', 'string'],
            'webserver_type' => ['sometimes', 'string'],
            'php_version' => ['sometimes', 'string'],
            'os_type' => ['sometimes', 'string'],
        ]);

        $phpVersion = $data['php_version'] ?? '8.1';

        $server = $this->provision('server', [
            'name' => $data['name'],
            'type' => $data['type'] ?? 'server',
            'ip_address' => null,
            'internal_ip' => null,
            'ssh_port' => 22,
            'php_version' => $phpVersion,
            'installed_php_versions' => [$phpVersion],
            'mysql_version' => '8.0',
            'sites_count' => 0,
            'monitoring' => false,
        ]);

        $server->payload = array_merge($server->payload, [
            'ip_address' => '192.0.2.' . (($server->id % 200) + 1),
            'internal_ip' => '10.0.0.' . (($server->id % 200) + 1),
        ]);
        $server->save();

        return $this->created($server);
    }

    public function show(ProvisionedResource $server): JsonResponse
    {
        return $this->one($server);
    }

    public function destroy(ProvisionedResource $server): JsonResponse
    {
        ProvisionedResource::query()->where('server_id', $server->id)->delete();
        $server->delete();

        return $this->destroyed();
    }

    public function logs(ProvisionedResource $server): JsonResponse
    {
        return response()->json(['data' => []]);
    }
}
