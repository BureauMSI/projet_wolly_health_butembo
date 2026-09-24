@foreach($nodes as $node)
    @php
        $left = $grouped->get($node->id, collect())->firstWhere('placement_side', 'left');
        $right = $grouped->get($node->id, collect())->firstWhere('placement_side', 'right');
        $isSelf = isset($highlightId) && (int) $node->id === (int) $highlightId;
        $volumes = $subtreePv ?? [];
        $leftPv = $left ? (float) ($volumes[$left->id] ?? 0) : 0.0;
        $rightPv = $right ? (float) ($volumes[$right->id] ?? 0) : 0.0;
        $eqLevel = isset($eqLevels[(int) $node->id]) ? (int) $eqLevels[(int) $node->id] : null;
        $eqAmounts = $eqAmounts ?? ['near' => 4.0, 'after' => 1.0];
        $eqAmount = $eqLevel ? \App\Support\EquilibriumPath::amountForLevel($eqLevel, $eqAmounts) : null;
        $parentId = $node->placement_parent_id ? (int) $node->placement_parent_id : null;
        $linkLevel = ($parentId && isset($eqLevels[$parentId])) ? (int) $eqLevels[$parentId] : null;
        $gen = isset($generation) ? (int) $generation : 0;
        $maxDepth = isset($maxDepth) && $maxDepth !== null ? (int) $maxDepth : null;
        $compact = (bool) ($compact ?? false);
        $drillBase = $drillBase ?? null;
        $atMaxDepth = $maxDepth !== null && $gen >= $maxDepth;
        $hasDeeper = $atMaxDepth && ($left || $right);
        $initials = collect(preg_split('/\s+/u', trim((string) $node->full_name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
        $liClass = trim(
            ($linkLevel ? 'mlm-eq-link '.($linkLevel <= 4 ? 'mlm-eq-link-near' : 'mlm-eq-link-far') : '')
            .' '.($isSelf ? 'mlm-eq-source' : '')
        );
        $childInclude = [
            'grouped' => $grouped,
            'highlightId' => $highlightId ?? null,
            'subtreePv' => $volumes,
            'eqLevels' => $eqLevels ?? [],
            'eqAmounts' => $eqAmounts,
            'compact' => $compact,
            'maxDepth' => $maxDepth,
            'drillBase' => $drillBase,
            'generation' => $gen + 1,
        ];
        $drillUrl = $drillBase
            ? $drillBase.(str_contains($drillBase, '?') ? '&' : '?').'root='.$node->id
            : null;
    @endphp
    <li class="{{ $liClass }}" data-member-li="{{ $node->id }}">
        <div
            class="mlm-node{{ $isSelf ? ' mlm-node-self' : '' }}{{ $eqLevel ? ' mlm-node-eq' : '' }}{{ $compact ? ' mlm-node-compact' : '' }}{{ $hasDeeper ? ' mlm-node-has-more' : '' }}"
            data-member-id="{{ $node->id }}"
            data-parent-id="{{ $node->placement_parent_id ?? '' }}"
            data-name="{{ $node->full_name }}"
            data-code="{{ $node->username }}"
            data-username="{{ $node->username }}"
            data-left-pv="{{ number_format($leftPv, 2, '.', '') }}"
            data-right-pv="{{ number_format($rightPv, 2, '.', '') }}"
            data-generation="{{ $gen }}"
            data-has-deeper="{{ $hasDeeper ? '1' : '0' }}"
            @if($drillUrl) data-drill-url="{{ $drillUrl }}" @endif
            @if($eqLevel) data-eq-level="{{ $eqLevel }}" data-keep-eq="1" @endif
            role="button"
            tabindex="0"
            title="{{ __('messages.tree_drill_hint') }}"
        >
            @if(($leg ?? null) === 'left' || ($leg ?? null) === 'right')
                <span class="mlm-chip">{{ __('messages.'.$leg) }}</span>
            @endif
            @if($eqLevel)
                <span class="mlm-eq-badge {{ $eqLevel <= 4 ? 'mlm-eq-badge-near' : 'mlm-eq-badge-far' }}">
                    @if($compact)
                        {{ __('messages.equilibrium_short', ['n' => $eqLevel]) }}
                    @else
                        {{ __('messages.equilibrium_n', ['n' => $eqLevel]) }}
                        · {{ number_format($eqAmount, 0) }}$
                    @endif
                </span>
            @endif
            <div class="mlm-avatar">{{ $initials !== '' ? $initials : 'HH' }}</div>
            <div class="mlm-name">{{ $node->full_name }}</div>
            <div class="mlm-meta">{{ $node->username }}</div>
            @if($hasDeeper)
                <div class="mlm-more">{{ __('messages.tree_more_short') }}</div>
            @endif
            @unless($compact)
                <div class="mlm-feet">
                    <div class="mlm-foot">
                        <span><i class="bi bi-arrow-down-left"></i> {{ __('messages.left') }}</span>
                        <strong>{{ number_format($leftPv, 2) }}</strong>
                    </div>
                    <div class="mlm-foot">
                        <span><i class="bi bi-arrow-down-right"></i> {{ __('messages.right') }}</span>
                        <strong>{{ number_format($rightPv, 2) }}</strong>
                    </div>
                </div>
            @endunless
        </div>
        @if(! $atMaxDepth)
            <ul>
                @if($left)
                    @include('admin.members._nodes', $childInclude + ['nodes' => collect([$left]), 'leg' => 'left'])
                @else
                    <li>
                        <div class="mlm-node mlm-node-empty{{ $compact ? ' mlm-node-compact' : '' }}">
                            <span class="mlm-chip">{{ __('messages.left') }}</span>
                            <div class="mlm-avatar mlm-avatar-empty"><i class="bi bi-plus-lg"></i></div>
                            <div class="mlm-name">{{ __('messages.empty_leg') }}</div>
                        </div>
                    </li>
                @endif
                @if($right)
                    @include('admin.members._nodes', $childInclude + ['nodes' => collect([$right]), 'leg' => 'right'])
                @else
                    <li>
                        <div class="mlm-node mlm-node-empty{{ $compact ? ' mlm-node-compact' : '' }}">
                            <span class="mlm-chip">{{ __('messages.right') }}</span>
                            <div class="mlm-avatar mlm-avatar-empty"><i class="bi bi-plus-lg"></i></div>
                            <div class="mlm-name">{{ __('messages.empty_leg') }}</div>
                        </div>
                    </li>
                @endif
            </ul>
        @endif
    </li>
@endforeach
