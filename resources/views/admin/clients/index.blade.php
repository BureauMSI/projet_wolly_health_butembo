@extends('layouts.staff')
@section('title', __('messages.clients'))
@section('content')
    <div class="page-header">
        <h1>{{ __('messages.clients') }}</h1>
        @can('create', App\Models\Client::class)
            <a class="btn btn-success" href="{{ route('admin.clients.create') }}">{{ __('messages.new_client') }}</a>
        @endcan
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.name_field') }}</th>
                            <th>{{ __('messages.phone') }}</th>
                            <th>{{ __('messages.referrer') }}</th>
                            <th>{{ __('messages.accumulated_pv') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            <tr>
                                <td>{{ $client->name }}</td>
                                <td>{{ $client->phone }}</td>
                                <td>{{ $client->referrer?->full_name }}</td>
                                <td>
                                    {{ number_format($client->accumulated_pv, 2) }}
                                    @if($client->converted_member_id)
                                        <span class="badge text-bg-success">{{ __('messages.membership_indirect') }}</span>
                                    @elseif((float) $client->accumulated_pv >= (float) $threshold)
                                        <span class="badge text-bg-warning">{{ __('messages.threshold_reached') }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @can('update', $client)
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.clients.edit', $client) }}">{{ __('messages.edit') }}</a>
                                        @include('partials.delete-form', ['action' => route('admin.clients.destroy', $client)])
                                    @endcan
                                    @can('create', App\Models\Member::class)
                                        @if($client->converted_member_id === null && (float) $client->accumulated_pv >= (float) $threshold)
                                            <a class="btn btn-sm btn-success" href="{{ route('admin.members.create', ['membership_type' => 'indirect', 'source_client_id' => $client->id]) }}">{{ __('messages.assign_indirect_membership') }}</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $clients->links() }}</div>
        </div>
    </div>
@endsection
