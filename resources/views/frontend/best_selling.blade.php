@extends('frontend.layouts.app')
<style>
    .esmer img{
        object-fit: contain !important;
    }
</style>
@section('content')
    <section class="mb-5 esmer" style="margin-top: 2rem;">
        <div class="container">
            @php
                $best_selling_products = get_best_selling_products(24);

            @endphp
            <!-- Products Section -->
            <h2 class="font-weight-bold mb-2">
                Best Selling
            </h2>
            <div class="px-3">
                <div class="row row-cols-xxl-6 row-cols-xl-5 row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-2 gutters-16 border-top border-left">
                    @foreach ($best_selling_products as $key => $product)
                        <div class="col text-center border-right border-bottom has-transition hov-shadow-out z-1">
                            @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1',['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
