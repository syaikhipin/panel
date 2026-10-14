<?php

namespace App\Http\Controllers\Ploi;

use App\Models\ProvisionedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubResourceController extends PloiController
{
    /**
     * Route path segment (plural) => stored resource type (singular).
     */
    private const TYPES = [
        'databases' => 'database',
        'crontabs' => 'crontab',
        'daemons' => 'daemon',
        'network-rules' => 'network_rule',
        'system-users' => 'system_user',
        'certificates' => 'certificate',
        'redirects' => 'redirect',
        'queues' => 'queue',
    ];

    public function index(Request $request, ProvisionedResource $server): JsonResponse
    {
        return $this->many($this->query($request, $server)->get());
    }

    public function store(Request $request, ProvisionedResource $server): JsonResponse
    {
        $type = $this->type($request);
        $data = $request->validate($this->rules($type));

        $resource = $this->provision(self::TYPES[$type], $data, server: $server, site: $this->site($request));

        if (self::TYPES[$type] === 'database') {
            $user = $this->provision('database_user', [
                'database_id' => $resource->id,
                'name' => $data['user'],
            ], server: $server);

            $resource->payload = array_merge($resource->payload, [
                'users' => [['id' => $user->id, 'name' => $data['user']]],
            ]);
            $resource->save();
        }

        return $this->created($resource);
    }

    public function show(Request $request, ProvisionedResource $server): JsonResponse
    {
        return $this->one($this->find($request, $server));
    }

    public function destroy(Request $request, ProvisionedResource $server): JsonResponse
    {
        $this->find($request, $server)->delete();

        return $this->destroyed();
    }

    public function restart(Request $request, ProvisionedResource $server): JsonResponse
    {
        return $this->one($this->find($request, $server));
    }

    public function togglePause(Request $request, ProvisionedResource $server): JsonResponse
    {
        return $this->one($this->find($request, $server));
    }

    public function resetPassword(Request $request, ProvisionedResource $server): JsonResponse
    {
        return response()->json(['data' => ['new_password' => Str::password(16)]]);
    }

    private function type(Request $request): string
    {
        return (string) $request->route('type');
    }

    private function site(Request $request): ?ProvisionedResource
    {
        $siteId = $request->route('site');

        if (! $siteId) {
            return null;
        }

        return ProvisionedResource::query()->ofType('site')->find((int) $siteId);
    }

    private function query(Request $request, ProvisionedResource $server)
    {
        $context = ['server_id' => $server->id];

        if ($site = $this->site($request)) {
            $context['site_id'] = $site->id;
        }

        return ProvisionedResource::query()->ofType(self::TYPES[$this->type($request)], $context);
    }

    private function find(Request $request, ProvisionedResource $server): ProvisionedResource
    {
        $resource = $this->query($request, $server)->find((int) $request->route('id'));

        if (! $resource) {
            throw new NotFoundHttpException('Resource not found.');
        }

        return $resource;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(string $type): array
    {
        return match ($type) {
            'databases' => [
                'name' => ['required', 'string'],
                'user' => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            'crontabs' => [
                'command' => ['required', 'string'],
                'frequency' => ['required', 'string'],
                'user' => ['sometimes', 'string'],
            ],
            'daemons' => [
                'command' => ['required', 'string'],
                'system_user' => ['required', 'string'],
                'processes' => ['required', 'integer'],
            ],
            'network-rules' => [
                'name' => ['required', 'string'],
                'port' => ['required', 'integer'],
                'type' => ['sometimes', 'string'],
                'from_ip_address' => ['required', 'string'],
            ],
            'system-users' => [
                'name' => ['required', 'string'],
                'sudo' => ['sometimes', 'boolean'],
            ],
            'certificates' => [
                'type' => ['sometimes', 'string'],
                'certificate' => ['required', 'string'],
                'private' => ['nullable', 'string'],
            ],
            'redirects' => [
                'redirect_from' => ['required', 'string'],
                'redirect_to' => ['required', 'string'],
                'type' => ['sometimes', 'string'],
            ],
            'queues' => [
                'connection' => ['sometimes', 'string'],
                'queue' => ['sometimes', 'string'],
                'maximum_seconds' => ['sometimes', 'integer'],
                'sleep' => ['sometimes', 'integer'],
                'processes' => ['sometimes', 'integer'],
                'maximum_tries' => ['sometimes', 'integer'],
            ],
        };
    }
}
