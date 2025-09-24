<form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
    @csrf
    <input type="hidden" name="payment_method" value="paypal">
    <div class="form-group row">
        <input type="hidden" name="types[]" value="PAYPAL_CLIENT_ID">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Paypal Client Id') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control" name="PAYPAL_CLIENT_ID"
                   value="{{ env('PAYPAL_CLIENT_ID') }}"
                   placeholder="{{ translate('Paypal Client ID') }}" required>
        </div>
    </div>
    <div class="form-group row">
        <input type="hidden" name="types[]" value="PAYPAL_CLIENT_SECRET">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Paypal Client Secret') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control" name="PAYPAL_CLIENT_SECRET"
                   value="{{ env('PAYPAL_CLIENT_SECRET') }}"
                   placeholder="{{ translate('Paypal Client Secret') }}" required>
        </div>
    </div>
    <div class="form-group mb-0 text-right">
        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
    </div>
</form>
