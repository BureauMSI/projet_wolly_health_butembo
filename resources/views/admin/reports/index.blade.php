@extends('layouts.staff')
@section('title', __('messages.reports'))
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ __('messages.reports') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.report_catalog_intro') }}</p>
        </div>
    </div>
    @foreach(['finance' => 'report_group_finance', 'network' => 'report_group_network'] as $group => $groupLabel)
        @php $groupItems = collect($items)->where('group', $group); @endphp
        @if($groupItems->isNotEmpty())
            <h2 class="h6 text-muted text-uppercase mt-3 mb-2">{{ __('messages.'.$groupLabel) }}</h2>
            <div class="row g-3 mb-3">
                @foreach($groupItems as $item)
                    <div class="col-md-6 col-xl-4">
                        <a class="text-decoration-none" href="{{ route('admin.reports.show', $item['type']) }}">
                            <div class="card surface-card h-100">
                                <div class="card-body d-flex gap-3 align-items-start">
                                    <i class="bi {{ $item['icon'] }} fs-3 text-success"></i>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ __('messages.report_'.$item['type']) }}</div>
                                        <div class="small text-muted">{{ __('messages.report_'.$item['type'].'_hint') }}</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
@endsection
