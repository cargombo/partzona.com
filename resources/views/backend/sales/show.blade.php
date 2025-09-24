@extends('backend.layouts.app')

@section('content')

    <style>
        .copy-btn {
            padding: 0.15rem 0.35rem;
            line-height: 1;
            min-width: 28px;
        }
        .border-bottom {
            border-bottom: 1px solid #dee2e6 !important;
        }
    </style>
    <div class="card">
        <div class="card-header">
            <h1 class="h2 fs-16 mb-0">{{ translate('Order Details') }}</h1>
        </div>
        <div class="card-body">
            <div class="row gutters-5">
                <div class="col text-md-left text-center">
                </div>
                @php
                    $delivery_status = $order->delivery_status;
                    $payment_status = $order->payment_status;
                    $admin_user_id = get_admin()->id;
                @endphp
                @if ($order->seller_id == $admin_user_id || get_setting('product_manage_by_admin') == 1)

                    <!--Assign Delivery Boy-->
                    @if (addon_is_activated('delivery_boy'))
                        <div class="col-md-3 ml-auto">
                            <label for="assign_deliver_boy">{{ translate('Assign Deliver Boy') }}</label>
                            @if (($delivery_status == 'pending' || $delivery_status == 'confirmed' || $delivery_status == 'picked_up') && auth()->user()->can('assign_delivery_boy_for_orders'))
                                <select class="form-control aiz-selectpicker" data-live-search="true"
                                        data-minimum-results-for-search="Infinity" id="assign_deliver_boy">
                                    <option value="">{{ translate('Select Delivery Boy') }}</option>
                                    @foreach ($delivery_boys as $delivery_boy)
                                        <option value="{{ $delivery_boy->id }}"
                                                @if ($order->assign_delivery_boy == $delivery_boy->id) selected @endif>
                                            {{ $delivery_boy->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ optional($order->delivery_boy)->name }}"
                                       disabled>
                            @endif
                        </div>
                    @endif

                    <div class="col-md-3 ml-auto">
                        <label for="update_payment_status">{{ translate('Payment Status') }}</label>
                        @if (auth()->user()->can('update_order_payment_status') && $payment_status == 'unpaid')
                            <select class="form-control aiz-selectpicker" data-minimum-results-for-search="Infinity" id="update_payment_status" onchange="confirm_payment_status()">
                                <option value="unpaid" @if ($payment_status == 'unpaid') selected @endif>
                                    {{ translate('Unpaid') }}
                                </option>
                                <option value="paid" @if ($payment_status == 'paid') selected @endif>
                                    {{ translate('Paid') }}
                                </option>
                            </select>
                        @else
                            <input type="text" class="form-control" value="{{ ucfirst($payment_status) }}" disabled>
                        @endif
                    </div>
                    <div class="col-md-3 ml-auto">
                        <label for="update_delivery_status">{{ translate('Delivery Status') }}</label>
                        @if (auth()->user()->can('update_order_delivery_status') && $delivery_status != 'delivered' && $delivery_status != 'cancelled')
                            <select class="form-control aiz-selectpicker" data-minimum-results-for-search="Infinity"
                                    id="update_delivery_status">
                                <option value="pending" @if ($delivery_status == 'pending') selected @endif>
                                    {{ translate('Pending') }}
                                </option>
                                <option value="confirmed" @if ($delivery_status == 'confirmed') selected @endif>
                                    {{ translate('Confirmed') }}
                                </option>
                                <option value="picked_up" @if ($delivery_status == 'picked_up') selected @endif>
                                    {{ translate('Picked Up') }}
                                </option>
                                <option value="on_the_way" @if ($delivery_status == 'on_the_way') selected @endif>
                                    {{ translate('On The Way') }}
                                </option>
                                <option value="delivered" @if ($delivery_status == 'delivered') selected @endif>
                                    {{ translate('Delivered') }}
                                </option>
                                <option value="cancelled" @if ($delivery_status == 'cancelled') selected @endif>
                                    {{ translate('Cancel') }}
                                </option>
                            </select>
                        @else
                            <input type="text" class="form-control" value="{{ $delivery_status }}" disabled>
                        @endif
                    </div>
                    <div class="col-md-3 ml-auto">
                        <label for="update_tracking_code">
                            {{ translate('Tracking Code (optional)') }}
                        </label>
                        <input type="text" class="form-control" id="update_tracking_code"
                               value="{{ $order->tracking_code }}">
                    </div>
                @endif
            </div>
            <div class="mb-3">
                @php
                    $removedXML = '<?xml version="1.0" encoding="UTF-8"?>';
                @endphp
{{--                {!! str_replace($removedXML, '', QrCode::size(100)->generate($order->code)) !!}--}}
            </div>
            <div class="row gutters-5">
                <div class="col text-md-left text-center">
                    <!-- User Real Address Section -->
                    <div class="mb-4">
                        <h4 class="mb-3 border-bottom pb-2">{{ translate('User Real Address') }}</h4>
                        @if(json_decode($order->shipping_address))
                            <address class="mb-0">
                                <div class="d-flex flex-column">
                                    <strong class="text-main mb-1">
                                        {{ json_decode($order->shipping_address)->name }}
                                    </strong>
                                    <span class="mb-1">{{ json_decode($order->shipping_address)->email }}</span>
                                    <span class="mb-1">{{ json_decode($order->shipping_address)->phone }}</span>
                                    <span class="mb-1">
                        {{ json_decode($order->shipping_address)->address }},
                        {{ json_decode($order->shipping_address)->city }},
                        @if(isset(json_decode($order->shipping_address)->state))
                                            {{ json_decode($order->shipping_address)->state }} -
                                        @endif
                                        {{ json_decode($order->shipping_address)->postal_code }}
                    </span>
                                    <span>{{ json_decode($order->shipping_address)->country }}</span>
                                </div>
                            </address>
                        @else
                            <address class="mb-0">
                                <div class="d-flex flex-column">
                                    <strong class="text-main mb-1">
                                        {{ $order->user->name }}
                                    </strong>
                                    <span class="mb-1">{{ $order->user->email }}</span>
                                    <span>{{ $order->user->phone }}</span>
                                </div>
                            </address>
                        @endif
                    </div>

                    <!-- Amazon Delivery Address Section -->
                    <div class="mb-4">
                        <h4 class="mb-3 border-bottom pb-2">{{ translate('Amazon Delivery Address') }}</h4>
                        <address class="mb-0">
                            <div class="d-flex flex-column">
                                <!-- Name with Copy Button -->
                                <div class="d-flex align-items-center mb-2">
                                    <strong class="text-main me-2 flex-grow-1">
                                        {{$order->user->customer_id}} {{ json_decode($order->shipping_address)->name ?? $order->user->name }}
                                    </strong>
                                    <button class="btn btn-xs btn-outline-primary copy-btn"
                                            onclick="copyToClipboard('{{$order->user->customer_id}} {{ json_decode($order->shipping_address)->name ?? $order->user->name }}')"
                                            title="{{ translate('Copy') }}">
                                        <i class="las la-copy"></i>
                                    </button>
                                </div>

                                <!-- Address Line 1 -->
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2 flex-grow-1">218 HIGHLAND BLVD APT C</span>
                                    <button class="btn btn-xs btn-outline-primary copy-btn"
                                            onclick="copyToClipboard('218 HIGHLAND BLVD APT C')"
                                            title="{{ translate('Copy') }}">
                                        <i class="las la-copy"></i>
                                    </button>
                                </div>

                                <!-- Address Line 2 -->
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2 flex-grow-1">NEW CASTLE DE, 19720-6966</span>
                                    <button class="btn btn-xs btn-outline-primary copy-btn"
                                            onclick="copyToClipboard('NEW CASTLE DE, 19720-6966')"
                                            title="{{ translate('Copy') }}">
                                        <i class="las la-copy"></i>
                                    </button>
                                </div>

                                <!-- City -->
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2 flex-grow-1">{{ translate('City') }}: NEW CASTLE</span>
                                    <button class="btn btn-xs btn-outline-primary copy-btn"
                                            onclick="copyToClipboard('NEW CASTLE')"
                                            title="{{ translate('Copy') }}">
                                        <i class="las la-copy"></i>
                                    </button>
                                </div>

                                <!-- Zip Code -->
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2 flex-grow-1">{{ translate('Zip code') }}: 19720-6966</span>
                                    <button class="btn btn-xs btn-outline-primary copy-btn"
                                            onclick="copyToClipboard('19720-6966')"
                                            title="{{ translate('Copy') }}">
                                        <i class="las la-copy"></i>
                                    </button>
                                </div>

                                <!-- State -->
                                <div class="d-flex align-items-center">
                                    <span class="me-2 flex-grow-1">{{ translate('State') }}: Delaware</span>
                                    <button class="btn btn-xs btn-outline-primary copy-btn"
                                            onclick="copyToClipboard('Delaware')"
                                            title="{{ translate('Copy') }}">
                                        <i class="las la-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </address>
                    </div>

                    <!-- Payment Information Section -->
                    @if ($order->manual_payment && is_array(json_decode($order->manual_payment_data, true)))
                        <div class="mb-4">
                            <h4 class="mb-3 border-bottom pb-2">{{ translate('Payment Information') }}</h4>
                            <div class="d-flex flex-column">
                                <div class="mb-2">
                                    <strong>{{ translate('Name') }}:</strong>
                                    <span>{{ json_decode($order->manual_payment_data)->name }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>{{ translate('Amount') }}:</strong>
                                    <span>{{ single_price(json_decode($order->manual_payment_data)->amount) }}</span>
                                </div>
                                <div class="mb-3">
                                    <strong>{{ translate('TRX ID') }}:</strong>
                                    <span>{{ json_decode($order->manual_payment_data)->trx_id }}</span>
                                </div>
                                <a href="{{ uploaded_asset(json_decode($order->manual_payment_data)->photo) }}"
                                   target="_blank"
                                   class="d-inline-block">
                                    <img src="{{ uploaded_asset(json_decode($order->manual_payment_data)->photo) }}"
                                         alt="{{ translate('Payment receipt') }}"
                                         class="img-thumbnail"
                                         style="max-height: 150px;">
                                </a>
                            </div>
                        </div>
                    @endif
                </div>


                <div class="col-md-4">
                    <table class="ml-auto">
                        <tbody>
                        <tr>
                            <td class="text-main text-bold">{{ translate('Order #') }}</td>
                            <td class="text-info text-bold text-right"> {{ $order->code }}</td>
                        </tr>
                        <tr>
                            <td class="text-main text-bold">{{ translate('Order Status') }}</td>
                            <td class="text-right">
                                @if ($delivery_status == 'delivered')
                                    <span class="badge badge-inline badge-success">
                                            {{ translate(ucfirst(str_replace('_', ' ', $delivery_status))) }}
                                        </span>
                                @else
                                    <span class="badge badge-inline badge-info">
                                            {{ translate(ucfirst(str_replace('_', ' ', $delivery_status))) }}
                                        </span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-main text-bold">{{ translate('Order Date') }} </td>
                            <td class="text-right">{{ date('d-m-Y h:i A', $order->date) }}</td>
                        </tr>
                        <tr>
                            <td class="text-main text-bold">
                                {{ translate('Total amount') }}
                            </td>
                            <td class="text-right">
                                {{ single_price($order->grand_total) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-main text-bold">{{ translate('Payment method') }}</td>
                            <td class="text-right">
                                {{ translate(ucfirst(str_replace('_', ' ', $order->payment_type))) }}</td>
                        </tr>
                        <tr>
                            <td class="text-main text-bold">{{ translate('Additional Info') }}</td>
                            <td class="text-right">{{ $order->additional_info }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <hr class="new-section-sm bord-no">
            <div class="row">
                <?php
                    $create_button = false;
                    foreach ($order->orderDetails as $key => $orderDetail){
                        if(!$orderDetail->delivery_id){
                            $create_button = true;
                        }
                    }
                ?>

                <div class="col-lg-12 table-responsive">
                    @if($create_button)
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <button type="button" class="btn btn-success create-delivery-btn mr-3">
                                    {{ translate('Create Delivery') }}
                                </button>

                                <!-- Badge with more space and clear separation -->
                                <div class="selected-count" style="min-width: 120px;"></div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all-products">
                                <label class="form-check-label" for="select-all-products">
                                    {{ translate('Select All Products') }}
                                </label>
                            </div>
                        </div>
                    @endif
                        <table class="table-bordered aiz-table invoice-summary table">
                            <thead>
                            <tr class="bg-trans-dark">
                                <th data-breakpoints="lg" class="min-col">#</th>
                                <th width="10%">{{ translate('Photo') }}</th>
                                <th class="text-uppercase">{{ translate('Description') }}</th>
                                <th data-breakpoints="lg" class="text-uppercase">{{ translate('Delivery Type') }}</th>
                                <th data-breakpoints="lg" class="text-uppercase">{{ translate('Amazon Order Number') }}</th>
                                <th data-breakpoints="lg" class="text-uppercase">{{ translate('Amazon Delivery Number') }}</th>
                                <th data-breakpoints="lg" class="text-uppercase">{{ translate('Tazbeeb Delivery Number') }}</th>
                                <!-- New column for Amazon Link -->
                                <th data-breakpoints="lg" class="text-uppercase">{{ translate('Amazon Link') }}</th>
                                <th data-breakpoints="lg" class="min-col text-uppercase text-center">
                                    {{ translate('Qty') }}
                                </th>
                                <th data-breakpoints="lg" class="min-col text-uppercase text-center">
                                    {{ translate('Price') }}</th>
                                <th data-breakpoints="lg" class="min-col text-uppercase text-right">
                                    {{ translate('Total') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($order->orderdetails as $key => $orderdetail)
                                <tr>
                                    <td>
                                        {{ $key + 1 }}
                                        @if(!$orderdetail->delivery_id)
                                            <input type="checkbox" name="order_detail_id[]" value="{{$orderdetail->id}}" class="order_detail_checkbox">
                                        @endif
                                    </td>
                                    <td>
                                        @if ($orderdetail->product != null && $orderdetail->product->auction_product == 0)
                                            <a href="{{ route('product', $orderdetail->product->slug) }}" target="_blank">
                                                <img height="50" src="{{ uploaded_asset($orderdetail->product->thumbnail_img) }}">
                                            </a>
                                        @elseif ($orderdetail->product != null && $orderdetail->product->auction_product == 1)
                                            <a href="{{ route('auction-product', $orderdetail->product->slug) }}" target="_blank">
                                                <img height="50" src="{{ uploaded_asset($orderdetail->product->thumbnail_img) }}">
                                            </a>
                                        @else
                                            <strong>{{ translate('n/a') }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($orderdetail->product != null && $orderdetail->product->auction_product == 0)
                                            <strong>
                                                <a href="{{ route('product', $orderdetail->product->slug) }}" target="_blank"
                                                   class="text-muted">
                                                    {{ $orderdetail->product->gettranslation('name') }}
                                                </a>
                                            </strong>
                                            <br>
                                            <small style="font-size: 16px;color: red ">
                                                {{ $orderdetail->variation }}
                                            </small>
                                            <br>
                                            <small>
                                                @php
                                                    $product_stock = $orderdetail->product->stocks->where('variant', $orderdetail->variation)->first();
                                                @endphp
                                                {{translate('sku')}}: {{ $product_stock['sku'] }}
                                            </small>
                                        @elseif ($orderdetail->product != null && $orderdetail->product->auction_product == 1)
                                            <strong>
                                                <a href="{{ route('auction-product', $orderdetail->product->slug) }}" target="_blank"
                                                   class="text-muted">
                                                    {{ $orderdetail->product->gettranslation('name') }}
                                                </a>
                                            </strong>
                                        @else
                                            <strong>{{ translate('product unavailable') }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->shipping_type != null && $order->shipping_type == 'home_delivery')
                                            {{ translate('home delivery') }}
                                        @elseif ($order->shipping_type == 'pickup_point')
                                            @if ($order->pickup_point != null)
                                                {{ $order->pickup_point->gettranslation('name') }}
                                                ({{ translate('pickup point') }})
                                            @else
                                                {{ translate('pickup point') }}
                                            @endif
                                        @elseif($order->shipping_type == 'carrier')
                                            @if ($order->carrier != null)
                                                {{ $order->carrier->name }} ({{ translate('carrier') }})
                                                <br>
                                                {{ translate('transit time').' - '.$order->carrier->transit_time }}
                                            @else
                                                {{ translate('carrier') }}
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $orderdetail->delivery->amazon_order_number ?? null}}
                                    </td>
                                    @if(in_array($orderdetail->delivery_status,['pending','confirmed']) && $orderdetail->delivery_id !== null)
                                        <td class="text-center amazon_delivery_number" data-id="{{ $orderdetail->id }}">
                                            <span class="delivery-number-text">{{ $orderdetail->delivery->amazon_delivery_number ?? '' }}</span>
                                            <a href="javascript:void(0)" class="edit-delivery-number ml-2">
                                                <i class="las la-edit"></i>
                                            </a>
                                        </td>
                                    @else
                                        <td class="text-center">
                                            {{ $orderdetail->delivery->tazbeeb_order_number ?? null}}
                                        </td>
                                    @endif

                                    <td class="text-center">
                                        {{ $orderdetail->delivery->tazbeeb_order_number ?? null}}
                                    </td>

                                    <!-- new amazon link column with eye icon -->
                                    <td class="text-center">
                                        @if ($orderdetail->product != null && isset($product_stock['datas']['color']['link']))
{{--                                            @dd($product_stock['datas'])--}}
{{--                                            {{isset($product_stock['datas']['size']['asin']) ? "/".$product_stock['datas']['size']['asin'] : ""}}--}}
                                            <a href="{{ $product_stock['datas']['color']['link'] }}" target="_blank" title="{{ translate('view on amazon') }}">
                                                <i class="las la-eye"></i>
                                            </a>

                                        @else
                                            <span class="text-muted">{{ translate('n/a') }}</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        {{ $orderdetail->quantity }}
                                    </td>
                                    <td class="text-center">
                                        {{ single_price($orderdetail->price / $orderdetail->quantity) }}
                                    </td>
                                    <td class="text-center">
                                        {{ single_price($orderdetail->price) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                </div>
            </div>
            <div class="clearfix float-right">
                <table class="table">
                    <tbody>
                    <tr>
                        <td>
                            <strong class="text-muted">{{ translate('Sub Total') }} :</strong>
                        </td>
                        <td>
                            {{ single_price($order->orderDetails->sum('price')) }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong class="text-muted">{{ translate('Tax') }} :</strong>
                        </td>
                        <td>
                            {{ single_price($order->orderDetails->sum('tax')) }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong class="text-muted">{{ translate('Shipping') }} :</strong>
                        </td>
                        <td>
                            {{ single_price($order->orderDetails->sum('shipping_cost')) }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong class="text-muted">{{ translate('Coupon') }} :</strong>
                        </td>
                        <td>
                            {{ single_price($order->coupon_discount) }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong class="text-muted">{{ translate('TOTAL') }} :</strong>
                        </td>
                        <td class="text-muted h5">
                            {{ single_price($order->grand_total) }}
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="no-print text-right">
                    <a href="{{ route('invoice.download', $order->id) }}" type="button" class="btn btn-icon btn-light"><i
                                class="las la-print"></i></a>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('modal')

    <!-- confirm payment Status Modal -->
    <div id="confirm-payment-status" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content p-2rem">
                <div class="modal-body text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="64" viewBox="0 0 72 64">
                        <g id="Octicons" transform="translate(-0.14 -1.02)">
                            <g id="alert" transform="translate(0.14 1.02)">
                                <path id="Shape" d="M40.159,3.309a4.623,4.623,0,0,0-7.981,0L.759,58.153a4.54,4.54,0,0,0,0,4.578A4.718,4.718,0,0,0,4.75,65.02H67.587a4.476,4.476,0,0,0,3.945-2.289,4.773,4.773,0,0,0,.046-4.578Zm.6,52.555H31.582V46.708h9.173Zm0-13.734H31.582V23.818h9.173Z" transform="translate(-0.14 -1.02)" fill="#ffc700" fill-rule="evenodd"/>
                            </g>
                        </g>
                    </svg>
                    <p class="mt-3 mb-3 fs-16 fw-700">{{translate('Are you sure you want to change the payment status?')}}</p>
                    <button type="button" class="btn btn-light rounded-2 mt-2 fs-13 fw-700 w-150px" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" onclick="update_payment_status()" class="btn btn-success rounded-2 mt-2 fs-13 fw-700 w-150px">{{translate('Confirm')}}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Delivery Modal -->
    <div id="create-delivery-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content p-2rem">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Create Delivery') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="create-delivery-form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="amazon_delivery_number">{{ translate('Amazon Delivery Number') }}</label>
                            <input type="text" class="form-control" id="amazon_delivery_number" name="amazon_delivery_number">
                            <small class="form-text text-muted">{{ translate('Enter a unique delivery tracking number') }}</small>

                            <label for="amazon_order_number">{{ translate('Amazon Order Number') }}</label>
                            <input type="text" class="form-control" id="amazon_order_number" name="amazon_order_number" required>
                            <small class="form-text text-muted">{{ translate('Enter a unique order number') }}</small>
                        </div>
                        <div class="selected-products-summary">
                            <p>{{ translate('Selected Products') }}: <span class="selected-product-count">0</span></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-2 mt-2 fs-13 fw-700" data-dismiss="modal">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success rounded-2 mt-2 fs-13 fw-700">
                            {{ translate('Create Delivery') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script>
        function copyToClipboard(text) {
            // Create a temporary input element
            var tempInput = document.createElement("input");
            tempInput.value = text;
            document.body.appendChild(tempInput);

            // Select and copy the text
            tempInput.select();
            document.execCommand("copy");

            // Remove the temporary element
            document.body.removeChild(tempInput);

            // Show a brief notification or alert
            alert("{{ translate('Copied!') }}");
        }
    </script>
    <script type="text/javascript">
        $('#assign_deliver_boy').on('change', function() {
            var order_id = {{ $order->id }};
            var delivery_boy = $('#assign_deliver_boy').val();
            $.post('{{ route('orders.delivery-boy-assign') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                delivery_boy: delivery_boy
            }, function(data) {
                AIZ.plugins.notify('success', '{{ translate('Delivery boy has been assigned') }}');
            });
        });
        $('#update_delivery_status').on('change', function() {
            var order_id = {{ $order->id }};
            var status = $('#update_delivery_status').val();
            $.post('{{ route('orders.update_delivery_status') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                status: status
            }, function(data) {
                AIZ.plugins.notify('success', '{{ translate('Delivery status has been updated') }}');
            });
        });

        // Payment Status Update
        function confirm_payment_status(value){
            $('#confirm-payment-status').modal('show');
        }

        function update_payment_status(){
            $('#confirm-payment-status').modal('hide');
            var order_id = {{ $order->id }};
            $.post('{{ route('orders.update_payment_status') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                status: 'paid'
            }, function(data) {
                $('#update_payment_status').prop('disabled', true);
                AIZ.plugins.bootstrapSelect('refresh');
                AIZ.plugins.notify('success', '{{ translate('Payment status has been updated') }}');
            });
        }

        $('#update_tracking_code').on('change', function() {
            var order_id = {{ $order->id }};
            var tracking_code = $('#update_tracking_code').val();
            $.post('{{ route('orders.update_tracking_code') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                tracking_code: tracking_code
            }, function(data) {
                AIZ.plugins.notify('success', '{{ translate('Order tracking code has been updated') }}');
            });
        });

        // Create Delivery Functionality
        $(document).ready(function() {

            $(document).on('click', '.edit-delivery-number', function() {
                var cell = $(this).closest('.amazon_delivery_number');
                var currentText = cell.find('.delivery-number-text').text().trim();
                var orderDetailId = cell.data('id');

                // Create and show the input field
                cell.find('.delivery-number-text, .edit-delivery-number').hide();
                cell.append('<div class="inline-edit-container">' +
                    '<input type="text" class="form-control form-control-sm delivery-number-input" value="' + currentText + '">' +
                    '<div class="mt-2">' +
                    '<button class="btn btn-xs btn-primary save-delivery-number mr-1">{{ translate("Save") }}</button>' +
                    '<button class="btn btn-xs btn-light cancel-edit">{{ translate("Cancel") }}</button>' +
                    '</div></div>');

                // Focus on the input
                cell.find('.delivery-number-input').focus();
            });

            // Cancel edit click handler
            $(document).on('click', '.cancel-edit', function() {
                var cell = $(this).closest('.amazon_delivery_number');
                cell.find('.inline-edit-container').remove();
                cell.find('.delivery-number-text, .edit-delivery-number').show();
            });

            // Save delivery number click handler
            $(document).on('click', '.save-delivery-number', function() {
                var cell = $(this).closest('.amazon_delivery_number');
                var orderDetailId = cell.data('id');
                var newValue = cell.find('.delivery-number-input').val();

                // Show loading state
                var saveBtn = $(this);
                saveBtn.html('<i class="las la-spinner la-spin"></i>');
                saveBtn.prop('disabled', true);

                // Submit via AJAX
                $.ajax({
                    url: '{{ route("orders.update_delivery_number") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ @csrf_token() }}',
                        order_detail_id: orderDetailId,
                        amazon_delivery_number: newValue
                    },
                    success: function(response) {
                        if(response.success) {
                            // Update the displayed text
                            cell.find('.delivery-number-text').text(newValue);

                            // Remove the inline edit container
                            cell.find('.inline-edit-container').remove();
                            cell.find('.delivery-number-text, .edit-delivery-number').show();

                            // Show success notification
                            AIZ.plugins.notify('success', response.message || '{{ translate("Updated successfully") }}');
                        } else {
                            AIZ.plugins.notify('danger', response.message || '{{ translate("Error updating delivery number") }}');
                            saveBtn.html('{{ translate("Save") }}');
                            saveBtn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        AIZ.plugins.notify('danger', '{{ translate("Something went wrong") }}');
                        saveBtn.html('{{ translate("Save") }}');
                        saveBtn.prop('disabled', false);
                    }
                });
            });

            // Also handle Enter key press in the input
            $(document).on('keypress', '.delivery-number-input', function(e) {
                if(e.which == 13) { // Enter key
                    $(this).closest('.inline-edit-container').find('.save-delivery-number').click();
                    return false;
                }
            });

            // Handle Escape key to cancel
            $(document).on('keyup', '.delivery-number-input', function(e) {
                if(e.which == 27) { // Escape key
                    $(this).closest('.inline-edit-container').find('.cancel-edit').click();
                    return false;
                }
            });
            // Update selected count function
            function updateSelectedCount() {
                var count = $('.order_detail_checkbox:checked').length;
                if (count > 0) {
                    $('.selected-count').html('<span class="badge badge-primary p-2 w-auto">' + count + ' {{ translate("items selected") }}</span>');
                } else {
                    $('.selected-count').html('');
                }
                $('.selected-product-count').text(count);
            }

            // Listen for checkbox changes
            $(document).on('change', '.order_detail_checkbox', function() {
                updateSelectedCount();
            });

            // Create delivery button click handler
            $('.create-delivery-btn').on('click', function() {
                // Check if any products are selected
                if($('.order_detail_checkbox:checked').length == 0) {
                    AIZ.plugins.notify('warning', '{{ translate("Please select at least one product for delivery") }}');
                    return false;
                }

                // Clear all form fields
                $('#amazon_delivery_number').val('');
                $('#amazon_order_number').val('');

                // Show the modal
                $('#create-delivery-modal').modal('show');
            });

// Track how many items have been added to deliveries
            function updateProcessedCount() {
                var processedCount = $('.order_detail_checkbox:disabled').length;
                if (processedCount > 0) {
                    $('.processed-count').html('<span class="badge badge-success w-auto">' + processedCount + ' {{ translate("items in delivery") }}</span>');
                } else {
                    $('.processed-count').html('');
                }
            }

// Update the selected count display function
            function updateSelectedCount() {
                var count = $('.order_detail_checkbox:checked').length;
                if (count > 0) {
                    $('.selected-count').html('<span class="badge badge-primary p-2 w-auto">' + count + ' {{ translate("items selected") }}</span>');
                } else {
                    $('.selected-count').html('');
                }
                $('.selected-product-count').text(count);

                // Also update the processed count
                updateProcessedCount();
            }

// Form submission handler
            $('#create-delivery-form').on('submit', function(e) {
                e.preventDefault();

                // Get selected product IDs
                var selectedProducts = [];
                $('.order_detail_checkbox:checked').each(function() {
                    selectedProducts.push($(this).val());
                });

                // Get delivery number
                var amazon_delivery_number = $('#amazon_delivery_number').val();
                var amazon_order_number = $('#amazon_order_number').val();

                if(amazon_order_number == '') {
                    AIZ.plugins.notify('warning', '{{ translate("Please enter Amazon Order number") }}');
                    return false;
                }

                // Submit form via AJAX
                $.ajax({
                    url: '{{ route("orders.create_delivery") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ @csrf_token() }}',
                        order_id: {{ $order->id }},
                        order_detail_ids: selectedProducts,
                        amazon_delivery_number: amazon_delivery_number,
                        amazon_order_number: amazon_order_number
                    },
                    success: function(response) {
                        if(response.success) {
                            // Close modal
                            $('#create-delivery-modal').modal('hide');

                            // Disable the checkboxes that were selected instead of unchecking them
                            $('.order_detail_checkbox:checked').each(function() {
                                // Disable the checkbox
                                $(this).prop('disabled', true);

                                // Add a visual indicator to show it's been processed
                                var $row = $(this).closest('tr');

                                // Add a visual cue for processed items (like greying out or adding a badge)
                                $row.find('td').css('opacity', '0.6');

                                // Add a badge or note to show it's been added to a delivery
                                var $cell = $(this).closest('td');
                                $cell.append('<span class="badge badge-success ml-2 w-auto">{{ translate("Added to delivery") }} #' + amazon_order_number + '</span>');

                                // Uncheck the checkbox
                                $(this).prop('checked', false);
                            });

                            // Update selected count and processed count
                            updateSelectedCount();

                            // Reset "Select All" checkbox
                            $('#select-all-products').prop('checked', false);

                            // Show success notification
                            AIZ.plugins.notify('success', response.message);
                        } else {
                            AIZ.plugins.notify('danger', response.message);
                        }
                    },
                    error: function() {
                        AIZ.plugins.notify('danger', '{{ translate("Something went wrong") }}');
                    }
                });
            });

// Initialize counts when the page loads
            $(document).ready(function() {
                updateSelectedCount();
                updateProcessedCount();

                // Select all products checkbox
                $('#select-all-products').on('change', function() {
                    // Only check the boxes that aren't disabled
                    $('.order_detail_checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
                    updateSelectedCount();
                });

                // Listen for checkbox changes
                $(document).on('change', '.order_detail_checkbox', function() {
                    updateSelectedCount();
                });
            });
        });
    </script>
@endsection
