@if($member->placementParent)
    {{ $member->placementParent->full_name }} ({{ __('messages.'.$member->placement_side) }})
@elseif($member->isTreeRoot())
    {{ __('messages.root_member') }}
@else
    <span class="badge text-bg-warning">{{ __('messages.awaiting_placement') }}</span>
@endif
