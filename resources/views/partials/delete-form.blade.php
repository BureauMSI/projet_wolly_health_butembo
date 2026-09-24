<form method="post" action="{{ $action }}" class="d-inline" onsubmit="return confirm(@json(__('messages.confirm_delete')))">
    @csrf
    @method('DELETE')
    <button class="{{ $class ?? 'btn btn-sm btn-outline-danger' }}" type="submit">{{ __('messages.delete') }}</button>
</form>
