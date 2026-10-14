<?php

use App\Http\Controllers\Ploi\PingController;
use App\Http\Controllers\Ploi\ServerController;
use App\Http\Controllers\Ploi\SiteController;
use App\Http\Controllers\Ploi\SubResourceController;
use App\Http\Controllers\Ploi\SynchronizeController;
use App\Http\Controllers\Ploi\UserController;
use Illuminate\Support\Facades\Route;

$serverChildren = 'databases|crontabs|daemons|network-rules|system-users';
$siteChildren = 'certificates|redirects|queues';

Route::middleware('ploi.token')->group(function () use ($serverChildren, $siteChildren) {
    Route::get('ping', [PingController::class, 'index']);
    Route::post('ping', [PingController::class, 'store']);

    Route::get('user', [UserController::class, 'index']);
    Route::get('user/server-providers', [UserController::class, 'serverProviders']);
    Route::get('user/server-providers/{id}', [UserController::class, 'serverProvider']);

    Route::get('synchronize/servers', [SynchronizeController::class, 'servers']);
    Route::get('synchronize/servers/{id}', [SynchronizeController::class, 'server']);
    Route::get('synchronize/sites', [SynchronizeController::class, 'sites']);
    Route::get('synchronize/sites/{id}', [SynchronizeController::class, 'site']);

    Route::get('servers', [ServerController::class, 'index']);
    Route::post('servers', [ServerController::class, 'store']);
    Route::get('servers/{server}/logs', [ServerController::class, 'logs']);
    Route::get('servers/{server}', [ServerController::class, 'show']);
    Route::delete('servers/{server}', [ServerController::class, 'destroy']);

    Route::get('servers/{server}/sites', [SiteController::class, 'index']);
    Route::post('servers/{server}/sites', [SiteController::class, 'store']);
    Route::get('servers/{server}/sites/{site}/log', [SiteController::class, 'logs']);
    Route::post('servers/{server}/sites/{site}/php-version', [SiteController::class, 'phpVersion']);
    Route::get('servers/{server}/sites/{site}/repository', [SiteController::class, 'repository']);
    Route::post('servers/{server}/sites/{site}/deploy', [SiteController::class, 'deploy']);
    Route::get('servers/{server}/sites/{site}/deploy/script', [SiteController::class, 'deployScript']);
    Route::patch('servers/{server}/sites/{site}/deploy/script', [SiteController::class, 'updateDeployScript']);
    Route::get('servers/{server}/sites/{site}/aliases', [SiteController::class, 'aliases']);
    Route::post('servers/{server}/sites/{site}/aliases', [SiteController::class, 'storeAliases']);
    Route::delete('servers/{server}/sites/{site}/aliases/{alias}', [SiteController::class, 'destroyAlias']);
    Route::post('servers/{server}/sites/{site}/{app}', [SiteController::class, 'installApp'])->where('app', 'wordpress|nextcloud');
    Route::delete('servers/{server}/sites/{site}/{app}', [SiteController::class, 'uninstallApp'])->where('app', 'wordpress|nextcloud');
    Route::get('servers/{server}/sites/{site}', [SiteController::class, 'show']);
    Route::delete('servers/{server}/sites/{site}', [SiteController::class, 'destroy']);

    Route::post('servers/{server}/databases/{database}/users/{user}/reset-password', [SubResourceController::class, 'resetPassword']);
    Route::get('servers/{server}/{type}', [SubResourceController::class, 'index'])->where('type', $serverChildren);
    Route::post('servers/{server}/{type}', [SubResourceController::class, 'store'])->where('type', $serverChildren);
    Route::post('servers/{server}/{type}/{id}/restart', [SubResourceController::class, 'restart'])->where('type', 'daemons');
    Route::post('servers/{server}/{type}/{id}/toggle-pause', [SubResourceController::class, 'togglePause'])->where('type', 'daemons');
    Route::get('servers/{server}/{type}/{id}', [SubResourceController::class, 'show'])->where('type', $serverChildren);
    Route::delete('servers/{server}/{type}/{id}', [SubResourceController::class, 'destroy'])->where('type', $serverChildren);

    Route::get('servers/{server}/sites/{site}/{type}', [SubResourceController::class, 'index'])->where('type', $siteChildren);
    Route::post('servers/{server}/sites/{site}/{type}', [SubResourceController::class, 'store'])->where('type', $siteChildren);
    Route::post('servers/{server}/sites/{site}/{type}/{id}/restart', [SubResourceController::class, 'restart'])->where('type', 'queues');
    Route::post('servers/{server}/sites/{site}/{type}/{id}/toggle-pause', [SubResourceController::class, 'togglePause'])->where('type', 'queues');
    Route::get('servers/{server}/sites/{site}/{type}/{id}', [SubResourceController::class, 'show'])->where('type', $siteChildren);
    Route::delete('servers/{server}/sites/{site}/{type}/{id}', [SubResourceController::class, 'destroy'])->where('type', $siteChildren);
});
