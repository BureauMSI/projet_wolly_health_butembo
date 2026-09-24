@extends('layouts.staff')
@section('title', __('messages.members'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.members') }}</h1>
        @can('create', App\Models\Member::class)
            <a class="btn btn-success" href="{{ route('admin.members.create') }}">{{ __('messages.new_member') }}</a>
        @endcan
    </div>
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-8">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="{{ __('messages.search_placeholder') }}">
        </div>
        <div class="col-md-4 d-grid d-md-block">
            <button class="btn btn-outline-success" type="submit">{{ __('messages.search') }}</button>
        </div>
    </form>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.full_name') }}</th>
                            <th>{{ __('messages.username') }}</th>
                            <th>{{ __('messages.sponsor') }}</th>
                            <th>{{ __('messages.placement') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>{{ $member->full_name }}</td>
                                <td>{{ $member->username }}</td>
                                <td>{{ $member->sponsor?->full_name ?? '—' }}</td>
                                <td>
                                    @include('partials.member-placement', ['member' => $member])
                                </td>
                                <td class="text-end">
                                    @if($member->isAwaitingPlacement())
                                        <a class="btn btn-sm btn-success" href="{{ route('admin.placements.index', ['member' => $member->id]) }}#member-{{ $member->id }}">{{ __('messages.go_to_assignment') }}</a>
                                    @else
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.members.show', $member) }}">{{ __('messages.show') }}</a>
                                    @can('update', $member)
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.members.edit', $member) }}">{{ __('messages.edit') }}</a>
                                    @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $members->links() }}</div>
        </div>
    </div>
@endsection
