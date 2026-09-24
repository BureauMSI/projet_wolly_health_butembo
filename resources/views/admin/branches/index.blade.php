@extends('layouts.staff')
@section('title', __('messages.branches'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.branches') }}</h1>
        <a class="btn btn-success" href="{{ route('admin.branches.create') }}">{{ __('messages.new_branch') }}</a>
    </div>
    <p class="text-muted">{{ __('messages.registration_codes_branches_hint') }}</p>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.name_field') }}</th>
                            <th>{{ __('messages.code') }}</th>
                            <th>{{ __('messages.phone') }}</th>
                            <th>{{ __('messages.registration_codes_available') }}</th>
                            <th>{{ __('messages.registration_codes_used') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td>{{ $branch->name }}</td>
                                <td>{{ $branch->code }}</td>
                                <td>{{ $branch->phone }}</td>
                                <td>{{ $branch->available_codes_count }}</td>
                                <td>{{ $branch->used_codes_count }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('admin.branches.show', $branch) }}">{{ __('messages.registration_codes') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $branches->links() }}</div>
        </div>
    </div>
@endsection
