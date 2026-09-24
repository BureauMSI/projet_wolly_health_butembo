<form class="row g-2 mb-3" method="get">
    <div class="col-6 col-md-4">
        <label class="form-label">{{ __('messages.from') }}</label>
        <input class="form-control" type="date" name="from" value="{{ $from }}">
    </div>
    <div class="col-6 col-md-4">
        <label class="form-label">{{ __('messages.to') }}</label>
        <input class="form-control" type="date" name="to" value="{{ $to }}">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-success w-100" type="submit">{{ __('messages.filter') }}</button>
    </div>
</form>
