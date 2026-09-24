<div class="kpi">
    <div><strong>{{ __('messages.downline') }} :</strong> {{ $downlineCount }}</div>
    <div><strong>{{ __('messages.left_leg_pv') }} :</strong> {{ number_format($leftLegPv, 2) }}</div>
    <div><strong>{{ __('messages.right_leg_pv') }} :</strong> {{ number_format($rightLegPv, 2) }}</div>
</div>
<table class="data">
    <thead>
        <tr>
            <th>{{ __('messages.member_code') }}</th>
            <th>{{ __('messages.full_name') }}</th>
            <th>{{ __('messages.placement_side') }}</th>
            <th class="num">{{ __('messages.pv') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['member']->member_code }}</td>
                <td class="text-start">{{ $row['member']->full_name }}</td>
                <td>{{ $row['member']->placement_side ? __('messages.'.$row['member']->placement_side) : '—' }}</td>
                <td class="num">{{ number_format($row['pv'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">{{ __('messages.no_records') }}</td></tr>
        @endforelse
    </tbody>
</table>
