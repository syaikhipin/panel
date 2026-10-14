<?php

namespace App\Http\Controllers\Ploi;

use App\Models\ProvisionedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteController extends PloiController
{
    public function index(ProvisionedResource $server): JsonResponse
    {
        return $this->many(ProvisionedResource::query()->ofType('site', ['server_id' => $server->id])->get());
    }

    public function store(Request $request, ProvisionedResource $server): JsonResponse
    {
        $data = $request->validate([
            'root_domain' => ['required', 'string'],
            'web_directory' => ['sometimes', 'string'],
            'project_root' => ['sometimes', 'string'],
            'system_user' => ['sometimes', 'string'],
            'system_user_password' => ['nullable', 'string'],
        ]);

        $duplicate = ProvisionedResource::query()->ofType('site')
            ->where('payload->domain', $data['root_domain'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['root_domain' => ['The root domain has already been taken.']],
            ], 422);
        }

        $site = $this->provision('site', [
            'server_id' => $server->id,
            'domain' => $data['root_domain'],
            'php_version' => '8.2',
            'project_type' => null,
            'system_user' => $data['system_user'] ?? 'ploi',
        ], server: $server);

        return $this->created($site);
    }

    public function show(ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        return $this->one($site);
    }

    public function destroy(ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        ProvisionedResource::query()->where('site_id', $site->id)->delete();
        $site->delete();

        return $this->destroyed();
    }

    public function logs(ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function phpVersion(Request $request, ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        $data = $request->validate(['php_version' => ['required', 'string']]);

        $site->payload = array_merge($site->payload, ['php_version' => $data['php_version']]);
        $site->save();

        return $this->one($site);
    }

    public function repository(ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        return response()->json(['data' => (object) []]);
    }

    public function deploy(ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        return response()->json(['data' => ['id' => $site->id, 'status' => ProvisionedResource::STATUS_ACTIVE]]);
    }

    public function deployScript(ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        return response()->json([
            'data' => ['deploy_script' => $site->payload['deploy_script'] ?? ''],
        ]);
    }

    public function updateDeployScript(Request $request, ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        $data = $request->validate(['deploy_script' => ['sometimes', 'string']]);

        $site->payload = array_merge($site->payload, ['deploy_script' => $data['deploy_script'] ?? '']);
        $site->save();

        return $this->one($site);
    }

    public function aliases(ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        return $this->many(ProvisionedResource::query()->ofType('alias', ['site_id' => $site->id])->get());
    }

    public function storeAliases(Request $request, ProvisionedResource $server, ProvisionedResource $site): JsonResponse
    {
        $data = $request->validate(['aliases' => ['required', 'array']]);

        foreach ($data['aliases'] as $alias) {
            $this->provision('alias', ['alias' => $alias], server: $server, site: $site);
        }

        return $this->many(ProvisionedResource::query()->ofType('alias', ['site_id' => $site->id])->get());
    }

    public function destroyAlias(ProvisionedResource $server, ProvisionedResource $site, string $alias): JsonResponse
    {
        ProvisionedResource::query()->ofType('alias', ['site_id' => $site->id])
            ->where('payload->alias', $alias)
            ->delete();

        return $this->destroyed();
    }

    public function installApp(Request $request, ProvisionedResource $server, ProvisionedResource $site, string $app): JsonResponse
    {
        $data = $request->validate(['create_database' => ['sometimes', 'boolean']]);

        $databaseName = null;

        if ($data['create_database'] ?? false) {
            $databaseName = Str::slug(str_replace('.', '_', $site->payload['domain']), '_') . '_' . Str::lower(Str::random(4));
        }

        $site->payload = array_merge($site->payload, ['project_type' => $app]);
        $site->save();

        $installed = $this->provision('app', ['app' => $app, 'site_id' => $site->id], server: $server, site: $site);

        return response()->json([
            'data' => $installed->toApiArray(),
            'database_name' => $databaseName,
        ], 201);
    }

    public function uninstallApp(ProvisionedResource $server, ProvisionedResource $site, string $app): JsonResponse
    {
        ProvisionedResource::query()->ofType('app', ['site_id' => $site->id])
            ->where('payload->app', $app)
            ->delete();

        $site->payload = array_merge($site->payload, ['project_type' => null]);
        $site->save();

        return $this->destroyed();
    }
}
