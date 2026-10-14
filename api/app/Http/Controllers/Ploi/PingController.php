<?php

namespace App\Http\Controllers\Ploi;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PingController extends PloiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'user_name' => 'Stub Operator',
            'email' => 'operator@example.com',
            'name' => 'Ploi Core API Stub',
            'user' => ['subscription' => 'unlimited'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'version' => ['sometimes', 'string'],
            'php_version' => ['sometimes', 'string'],
            'panel_url' => ['sometimes', 'string'],
        ]);

        return response()->json(['data' => ['status' => 'ok']]);
    }
}
