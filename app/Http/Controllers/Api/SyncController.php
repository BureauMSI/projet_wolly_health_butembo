<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function push(Request $request, SyncEngine $engine): JsonResponse
    {
        $data = $request->validate([
            'origin_device_id' => ['nullable', 'string', 'max:100'],
            'entries' => ['required', 'array'],
            'entries.*.entity_type' => ['required', 'string', 'max:100'],
            'entries.*.entity_uuid' => ['required', 'uuid'],
            'entries.*.operation' => ['required', 'in:create,update,delete'],
            'entries.*.payload' => ['nullable', 'array'],
            'entries.*.origin_device_id' => ['nullable', 'string', 'max:100'],
        ]);

        $results = $engine->ingestMany($data['entries']);

        return response()->json([
            'results' => $results,
        ]);
    }

    public function pull(Request $request, SyncEngine $engine): JsonResponse
    {
        $data = $request->validate([
            'origin_device_id' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $entries = $engine->pendingEntries(
            (int) ($data['limit'] ?? config('sync.batch_size', 100)),
            $data['origin_device_id'] ?? null,
        );

        return response()->json([
            'entries' => $entries,
            'origin_device_id' => config('sync.origin_device_id'),
        ]);
    }

    public function status(SyncEngine $engine): JsonResponse
    {
        return response()->json([
            'mode' => filled(config('sync.remote_url')) ? 'remote' : 'local',
            'pending' => $engine->pendingCount(),
            'origin_device_id' => config('sync.origin_device_id'),
            'remote_configured' => filled(config('sync.remote_url')),
        ]);
    }
}
