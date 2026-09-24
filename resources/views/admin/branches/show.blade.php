@extends('layouts.staff')
@section('title', $branch->name)
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $branch->name }}</h1>
            <p class="text-muted mb-0">{{ $branch->code }} · {{ __('messages.registration_codes_available') }}: {{ $availableCount }}</p>
        </div>
        <a class="btn btn-outline-success" href="{{ route('admin.branches.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <h2 class="h6">{{ __('messages.generate_registration_codes') }}</h2>
            @if($canGenerateCodes ?? false)
                <p class="text-muted small">{{ __('messages.generate_registration_codes_hint') }}</p>
                <form method="post" action="{{ route('admin.branches.codes.generate', $branch) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label" for="quantity">{{ __('messages.quantity') }}</label>
                        <input class="form-control" id="quantity" type="number" name="quantity" min="1" max="200" value="{{ old('quantity', 5) }}" required>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success" type="submit">{{ __('messages.generate') }}</button>
                    </div>
                </form>
            @else
                <p class="text-muted small mb-0">{{ __('messages.registration_codes_manager_hint') }}</p>
            @endif
        </div>
    </div>

    @if(session('generated_codes'))
        <div class="alert alert-success">
            <strong>{{ __('messages.registration_codes_just_created') }}</strong>
            <div class="mt-2 font-monospace">{{ implode(' · ', session('generated_codes')) }}</div>
            <div class="small mt-1">{{ __('messages.registration_codes_send_hint') }}</div>
        </div>
    @endif

    <div class="card surface-card">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('messages.registration_codes') }}</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.registration_code') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th>{{ __('messages.member') }}</th>
                            <th>{{ __('messages.occurred_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($codes as $row)
                            <tr class="{{ $row->status === 'available' ? '' : 'table-light' }}">
                                <td class="font-monospace">{{ $row->code }}</td>
                                <td>{{ __('messages.registration_code_status_'.$row->status) }}</td>
                                <td>
                                    @if($row->usedByMember)
                                        <a href="{{ route('admin.members.show', $row->usedByMember) }}">{{ $row->usedByMember->full_name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ ($row->used_at ?? $row->created_at)?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $codes->links() }}</div>
        </div>
    </div>
@endsection
