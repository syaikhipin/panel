<?php

use App\Models\ProvisionedResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function api()
{
    return test()->withHeaders(['Authorization' => 'Bearer test-token']);
}

function makeServer(): int
{
    $response = api()->postJson('/servers', [
        'name' => 'web-1',
        'plan' => 'cax11',
        'region' => 'fsn1',
        'credential' => 1,
    ]);

    return $response->json('data.id');
}

function makeSite(int $serverId, string $domain = 'example.com'): int
{
    $response = api()->postJson("/servers/{$serverId}/sites", ['root_domain' => $domain]);

    return $response->json('data.id');
}

it('rejects requests without a bearer token', function () {
    $this->getJson('/servers')->assertUnauthorized();
});

it('reports an unlimited subscription on ping', function () {
    api()->getJson('/ping')
        ->assertOk()
        ->assertJsonPath('user.subscription', 'unlimited');
});

it('creates a server and flips its status to active after the provisioning delay', function () {
    $response = api()->postJson('/servers', [
        'name' => 'web-1',
        'plan' => 'cax11',
        'region' => 'fsn1',
        'credential' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'status', 'name', 'ip_address', 'internal_ip', 'ssh_port', 'php_version', 'installed_php_versions', 'mysql_version', 'sites_count', 'monitoring']])
        ->assertJsonPath('data.status', ProvisionedResource::STATUS_CREATED);

    $this->travel(5)->seconds();

    api()->getJson('/servers/' . $response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.status', ProvisionedResource::STATUS_ACTIVE);
});

it('rejects a duplicate root domain with the exact error message the client matches on', function () {
    $serverId = makeServer();
    makeSite($serverId);

    api()->postJson("/servers/{$serverId}/sites", ['root_domain' => 'example.com'])
        ->assertStatus(422)
        ->assertJsonPath('errors.root_domain.0', 'The root domain has already been taken.');
});

it('returns the database user id the client stores on database creation', function () {
    $serverId = makeServer();

    $response = api()->postJson("/servers/{$serverId}/databases", [
        'name' => 'db1',
        'user' => 'user1',
        'password' => 'secret',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'status', 'users' => [['id', 'name']]]]);

    api()->postJson("/servers/{$serverId}/databases/{$response->json('data.id')}/users/{$response->json('data.users.0.id')}/reset-password")
        ->assertOk()
        ->assertJsonStructure(['data' => ['new_password']]);
});

it('returns a top-level database_name when installing an app with a database', function () {
    $serverId = makeServer();
    $siteId = makeSite($serverId);

    api()->postJson("/servers/{$serverId}/sites/{$siteId}/wordpress", ['create_database' => true])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id'], 'database_name']);

    api()->deleteJson("/servers/{$serverId}/sites/{$siteId}/wordpress")
        ->assertOk()
        ->assertJsonPath('data', true);
});

it('answers deletes of server and site subresources with exactly 200', function () {
    $serverId = makeServer();
    $siteId = makeSite($serverId);

    $daemonId = api()->postJson("/servers/{$serverId}/daemons", [
        'command' => 'php artisan queue:work',
        'system_user' => 'ploi',
        'processes' => 1,
    ])->json('data.id');

    $queueId = api()->postJson("/servers/{$serverId}/sites/{$siteId}/queues", [])->json('data.id');

    api()->deleteJson("/servers/{$serverId}/daemons/{$daemonId}")->assertOk()->assertJsonPath('data', true);
    api()->deleteJson("/servers/{$serverId}/sites/{$siteId}/queues/{$queueId}")->assertOk()->assertJsonPath('data', true);
    api()->deleteJson("/servers/{$serverId}/sites/{$siteId}")->assertOk()->assertJsonPath('data', true);
    api()->deleteJson("/servers/{$serverId}")->assertOk()->assertJsonPath('data', true);
});

it('lists providers with plans and regions for the provider sync', function () {
    api()->getJson('/user/server-providers')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'label',
                    'name',
                    'provider' => [
                        'plans' => [['id', 'name']],
                        'regions' => [['id', 'name']],
                    ],
                ],
            ],
        ]);
});

it('serves synchronize lists with the fields the sync actions read', function () {
    $serverId = makeServer();
    makeSite($serverId);

    api()->getJson('/synchronize/servers')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'ip_address', 'sites_count', 'status']]]);

    api()->getJson('/synchronize/sites')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'domain', 'server_id', 'system_user', 'status', 'php_version']]]);
});
