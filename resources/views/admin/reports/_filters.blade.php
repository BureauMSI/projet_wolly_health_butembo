<form class="row g-2 mb-3" method="get">
    <div class="col-6 col-md-3">
        <label class="form-label">{{ __('messages.from') }}</label>
        <input class="form-control" type="date" name="from" value="{{ $from }}">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">{{ __('messages.to') }}</label>
        <input class="form-control" type="date" name="to" value="{{ $to }}">
    </div>
    @if(auth()->user()->isAdmin())
        <div class="col-md-3">
            <label class="form-label">{{ __('messages.branch') }}</label>
            <select class="form-select" name="branch_id">
                <option value="">{{ __('messages.all_branches') }}</option>
                @foreach($branches as $option)
                    <option value="{{ $option->id }}" @selected($branchId==$option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-success w-100" type="submit">{{ __('messages.filter') }}</button>
    </div>
</form>
