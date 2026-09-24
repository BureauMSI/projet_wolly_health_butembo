@extends('layouts.staff')
@section('title', __('messages.users'))
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ __('messages.users') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.users_intro') }}</p>
        </div>
        <a class="btn btn-success" href="{{ route('admin.users.create') }}">{{ __('messages.new_user') }}</a>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.name_field') }}</th>
                            <th>{{ __('messages.username') }}</th>
                            <th>{{ __('messages.role') }}</th>
                            <th>{{ __('messages.branch') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $row)
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->username }}</td>
                                <td>{{ __('messages.'.$row->role.'_role') }}</td>
                                <td>{{ $row->branch?->name ?? __('messages.no_branch') }}</td>
                                <td>
                                    @if($row->is_active)
                                        <span class="badge text-bg-success">{{ __('messages.active') }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">{{ __('messages.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('admin.users.edit', $row) }}">{{ __('messages.edit') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $users->links() }}</div>
        </div>
    </div>
@endsection
