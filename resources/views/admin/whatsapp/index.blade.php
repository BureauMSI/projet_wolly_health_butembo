@extends('layouts.staff')
@section('title', __('messages.outbox'))
@section('content')
    <div class="page-header"><h1>{{ __('messages.outbox') }}</h1></div>
    <div class="card surface-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.phone') }}</th>
                            <th>{{ __('messages.type') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $message)
                            <tr>
                                <td>{{ $message->to_phone }}</td>
                                <td>{{ $message->template_key }}</td>
                                <td>{{ $message->status === 'opened' ? __('messages.wa_opened') : __('messages.wa_pending') }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.whatsapp.open', $message) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit">{{ __('messages.mark_opened') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">{{ __('messages.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $messages->links() }}</div>
        </div>
    </div>
@endsection
