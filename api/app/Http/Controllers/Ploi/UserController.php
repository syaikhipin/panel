<?php

namespace App\Http\Controllers\Ploi;

use Illuminate\Http\JsonResponse;

class UserController extends PloiController
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private const PROVIDERS = [
        [
            'id' => 1,
            'label' => 'Hetzner',
            'name' => 'hetzner',
            'provider' => [
                'plans' => [
                    ['id' => 'cax11', 'name' => 'CAX11'],
                    ['id' => 'cax21', 'name' => 'CAX21'],
                    ['id' => 'cpx31', 'name' => 'CPX31'],
                ],
                'regions' => [
                    ['id' => 'fsn1', 'name' => 'Falkenstein'],
                    ['id' => 'nbg1', 'name' => 'Nuremberg'],
                    ['id' => 'hel1', 'name' => 'Helsinki'],
                ],
            ],
        ],
        [
            'id' => 2,
            'label' => 'DigitalOcean',
            'name' => 'digitalocean',
            'provider' => [
                'plans' => [
                    ['id' => 's-1vcpu-1gb', 'name' => 'Basic 1 vCPU / 1 GB'],
                    ['id' => 's-1vcpu-2gb', 'name' => 'Basic 1 vCPU / 2 GB'],
                ],
                'regions' => [
                    ['id' => 'ams3', 'name' => 'Amsterdam 3'],
                    ['id' => 'lon1', 'name' => 'London 1'],
                ],
            ],
        ],
    ];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 1,
                'name' => 'Stub Operator',
                'email' => 'operator@example.com',
            ],
        ]);
    }

    public function serverProviders(): JsonResponse
    {
        return response()->json(['data' => self::PROVIDERS]);
    }

    public function serverProvider(int $id): JsonResponse
    {
        foreach (self::PROVIDERS as $provider) {
            if ($provider['id'] === $id) {
                return response()->json(['data' => $provider]);
            }
        }

        abort(404, 'Provider not found.');
    }
}
