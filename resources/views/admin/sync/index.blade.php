@extends('layouts.staff')

@section('title', __('messages.sync'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ __('messages.sync') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.sync_help') }}</p>
    </div>
    <form method="post" action="{{ route('admin.sync.run') }}">
        @csrf
        <button type="submit" class="btn btn-success">
            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            {{ __('messages.sync_now') }}
        </button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="p-3 border rounded-3 h-100">
            <div class="text-muted small">{{ __('messages.sync_mode') }}</div>
            <div class="fs-5 fw-semibold">
                {{ $mode === 'local' ? __('messages.sync_mode_local') : __('messages.sync_mode_remote') }}
            </div>
            <div class="small text-muted mt-1">{{ __('messages.device') }}: {{ $originDeviceId }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded-3 h-100">
            <div class="text-muted small">{{ __('messages.sync_pending') }}</div>
            <div class="fs-5 fw-semibold" data-sync-pending>{{ $pendingCount }}</div>
            <div class="small text-muted mt-1">{{ __('messages.sync_pending_hint') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded-3 h-100">
            <div class="text-muted small">{{ __('messages.sync_remote_url') }}</div>
            <div class="fw-semibold text-break">{{ $remoteUrl ?: __('messages.sync_remote_empty') }}</div>
            <div class="small text-muted mt-1">{{ __('messages.sync_remote_hint') }}</div>
        </div>
    </div>
</div>

@if($failed->isNotEmpty())
    <div class="mb-4">
        <h2 class="h5">{{ __('messages.sync_conflicts') }}</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.type') }}</th>
                        <th>UUID</th>
                        <th>{{ __('messages.error') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failed as $row)
                        <tr>
                            <td>{{ $row->entity_type }}</td>
                            <td><code class="small">{{ $row->entity_uuid }}</code></td>
                            <td>{{ $row->last_error }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div>
    <h2 class="h5">{{ __('messages.sync_queue') }}</h2>
    @if($pending->isEmpty())
        <p class="text-muted">{{ __('messages.sync_queue_empty') }}</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.type') }}</th>
                        <th>{{ __('messages.operation') }}</th>
                        <th>UUID</th>
                        <th>{{ __('messages.queued_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pending as $row)
                        <tr>
                            <td>{{ $row->entity_type }}</td>
                            <td>{{ $row->operation }}</td>
                            <td><code class="small">{{ $row->entity_uuid }}</code></td>
                            <td>{{ $row->queued_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
