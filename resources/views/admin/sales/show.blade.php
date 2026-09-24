@extends('layouts.staff')
@section('title', $sale->number)
@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $sale->number }}</h1>
            <p class="text-muted mb-0">{{ $sale->sold_at?->format('Y-m-d H:i') }} · {{ $sale->buyerName() }}
                · {{ __('messages.benefit_mode') }}: {{ __('messages.benefit_'.$sale->benefit_mode) }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('update', $sale)
                <a class="btn btn-outline-success" href="{{ route('admin.sales.edit', $sale) }}">{{ __('messages.edit') }}</a>
            @endcan
            <a class="btn btn-outline-success" href="{{ route('admin.sales.invoice58', $sale) }}">{{ __('messages.print_invoice_58') }}</a>
            <a class="btn btn-outline-success" href="{{ route('admin.sales.invoice80', $sale) }}">{{ __('messages.print_invoice_80') }}</a>
            <a class="btn btn-outline-success" href="{{ route('admin.sales.receipt58', $sale) }}">{{ __('messages.print_receipt_58') }}</a>
            <a class="btn btn-outline-success" href="{{ route('admin.sales.receipt80', $sale) }}">{{ __('messages.print_receipt_80') }}</a>
            <form method="post" action="{{ route('admin.sales.whatsapp', $sale) }}">
                @csrf
                <button class="btn btn-outline-success" type="submit">{{ __('messages.whatsapp') }}</button>
            </form>
            @can('delete', $sale)
                @include('partials.delete-form', ['action' => route('admin.sales.destroy', $sale), 'class' => 'btn btn-outline-danger'])
            @endcan
        </div>
    </div>
    <div class="card surface-card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.products') }}</th>
                            <th>{{ __('messages.packing') }}</th>
                            <th>{{ __('messages.quantity') }}</th>
                            <th>{{ __('messages.unit_price') }}</th>
                            <th>{{ __('messages.pv') }}</th>
                            <th>{{ __('messages.commission_percent') }}</th>
                            <th>{{ __('messages.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                            <tr>
                                <td>{{ $item->product?->name }}</td>
                                <td>{{ __('messages.'.$item->packing) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->unit_price_usd, 2) }}</td>
                                <td>{{ number_format($item->pv, 2) }}</td>
                                <td>{{ number_format($item->commission_percent, 2) }} % · {{ number_format($item->commission_usd, 2) }} USD</td>
                                <td>{{ number_format($item->line_total_usd, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card surface-card">
        <div class="card-body">
            <p>{{ __('messages.subtotal') }}: {{ number_format($sale->subtotal_usd, 2) }} USD</p>
            <p>{{ __('messages.promo') }}: {{ number_format($sale->promo_usd, 2) }} USD</p>
            <p>{{ __('messages.discount') }}: {{ number_format($sale->discount_usd, 2) }} USD</p>
            <p class="h5 mb-2">{{ __('messages.total') }}: {{ number_format($sale->total_usd, 2) }} USD</p>
            <p class="text-success mb-0"><i class="bi bi-safe"></i> {{ __('messages.sale_credits_cash') }}
                @foreach($sale->cashMovements as $cash)
                    · {{ number_format($cash->amount_usd, 2) }} USD
                @endforeach
            </p>
        </div>
    </div>
    @if($sale->commissions->isNotEmpty())
        <div class="card surface-card mt-3">
            <div class="card-body">
                <h2 class="h6">{{ __('messages.commissions') }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.members') }}</th>
                                <th>{{ __('messages.type') }}</th>
                                <th>{{ __('messages.generation') }}</th>
                                <th>{{ __('messages.amount_usd') }}</th>
                                <th>{{ __('messages.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->commissions as $commission)
                                <tr>
                                    <td>{{ $commission->member?->full_name }}</td>
                                    <td>{{ \App\Services\ReportBuilder::commissionLabel($commission) }}</td>
                                    <td>{{ $commission->type === 'equilibrium' && $commission->generation ? $commission->generation : '—' }}</td>
                                    <td>{{ number_format($commission->amount_usd, 2) }}</td>
                                    <td>{{ __('messages.'.$commission->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
