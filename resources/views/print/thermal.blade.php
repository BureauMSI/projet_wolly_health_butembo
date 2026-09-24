@extends('layouts.print')
@section('title', $docTitle)
@section('print_css')
    @include('print._thermal-css', ['width' => $width])
@endsection
@section('content')
    <div class="ticket ticket-{{ $width }}">
        @include('print._thermal-header')
        <div class="doc-title">{{ $docTitle }}</div>

        @if($kind === 'voucher')
            <div class="ticket-meta">
                <p><strong>{{ __('messages.voucher_number') }}:</strong> {{ $movement->id }}</p>
                <p>{{ $movement->occurred_at?->format('d/m/Y H:i') }}</p>
                <p>{{ __('messages.branch') }}: {{ $movement->branch?->name }}</p>
                <p>{{ __('messages.category') }}: {{ $movement->categoryLabel() }}</p>
                @if($movement->description)
                    <p>{{ __('messages.description') }}: {{ $movement->description }}</p>
                @endif
                @if($movement->sale)
                    <p>{{ __('messages.sale_number') }}: {{ $movement->sale->number }}</p>
                @endif
            </div>
            <div class="line"></div>
            <div class="totals">
                <p><span>{{ __('messages.amount') }}</span><span>{{ number_format($movement->amount, 2) }} {{ $movement->currency_code }}</span></p>
                <p class="grand"><span>{{ __('messages.total_usd') }}</span><span>{{ number_format($movement->amount_usd, 2) }} USD</span></p>
            </div>
        @else
            <div class="ticket-meta">
                <p><strong>{{ __('messages.sale_number') }}:</strong> {{ $sale->number }}</p>
                <p>{{ $sale->sold_at?->format('d/m/Y H:i') }}</p>
                <p>{{ __('messages.branch') }}: {{ $sale->branch?->name }}</p>
                <p>{{ __('messages.buyer') }}: {{ $sale->buyerName() }}</p>
                <p>{{ __('messages.buyer_type') }}: {{ __('messages.buyer_'.$sale->buyer_type) }}</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('messages.products') }}</th>
                        @if($width == 80)
                            <th class="num">{{ __('messages.quantity') }}</th>
                        @endif
                        <th class="num">{{ __('messages.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td>
                                {{ $item->product?->name }}
                                @if($width == 58)
                                    <br>{{ $item->quantity }} × {{ number_format($item->unit_price_usd, 2) }}
                                @endif
                            </td>
                            @if($width == 80)
                                <td class="num">{{ $item->quantity }} {{ __('messages.'.$item->packing) }}</td>
                            @endif
                            <td class="num">{{ number_format($item->line_total_usd, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="line"></div>
            <div class="totals">
                <p><span>{{ __('messages.subtotal') }}</span><span>{{ number_format($sale->subtotal_usd, 2) }}</span></p>
                @if((float) $sale->promo_usd > 0)
                    <p><span>{{ __('messages.promo') }}</span><span>{{ number_format($sale->promo_usd, 2) }}</span></p>
                @endif
                @if((float) $sale->discount_usd > 0)
                    <p><span>{{ __('messages.discount') }}</span><span>{{ number_format($sale->discount_usd, 2) }}</span></p>
                @endif
                <p class="grand"><span>{{ __('messages.total') }}</span><span>{{ number_format($sale->total_usd, 2) }} USD</span></p>
                @if($kind === 'receipt')
                    <p><span>{{ __('messages.ticket_status') }}</span><span>{{ __('messages.ticket_paid') }}</span></p>
                @endif
            </div>
        @endif

        <div class="ticket-sign">
            <div>
                <div class="sig">{{ __('messages.ticket_client') }}</div>
            </div>
            <div>
                <div class="sig">{{ __('messages.cashier_role') }}</div>
            </div>
        </div>
        <div class="ticket-foot">
            <p>{{ __('messages.ticket_served_by') }}: {{ $printedBy ?? ($sale->cashier?->name ?? $movement?->user?->name ?? '') }}</p>
            @if($institution?->invoice_footer)
                <p>{{ $institution->invoice_footer }}</p>
            @endif
            <p>{{ __('messages.ticket_thanks') }}</p>
        </div>
    </div>
@endsection
