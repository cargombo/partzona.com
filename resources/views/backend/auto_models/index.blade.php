@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('All Auto Models') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('admin.auto-models.create') }}" class="btn btn-primary">
                <span>{{ translate('Add New Auto Model') }}</span>
            </a>
        </div>
    </div>
</div>
<br>

<div class="card">
    <form class="" id="sort_auto_models" action="" method="GET">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-md-0 h6">{{ translate('Auto Models') }}</h5>
            </div>
            <div class="col-md-3">
                <select class="form-control aiz-selectpicker" name="brand_id" id="brand_id" onchange="sort_auto_models()">
                    <option value="">{{ translate('All Brands') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @if ($brand->id == request()->brand_id) selected @endif>
                            {{ $brand->getTranslation('name') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" @isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                </div>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary" type="submit">{{ translate('Filter') }}</button>
            </div>
        </div>
    </form>

    <div class="card-body">
        <table class="table aiz-table mb-0 footable footable-1 breakpoint-xl" style="">
            <thead>
                <tr class="footable-header">
                    <th>#</th>
                    <th>{{ translate('Name') }}</th>
                    <th>{{ translate('Brand') }}</th>
                    <th data-breakpoints="md">{{ translate('Model Group') }}</th>
                    <th data-breakpoints="md">{{ translate('Year From') }}</th>
                    <th data-breakpoints="md">{{ translate('Year To') }}</th>
                    <th class="text-right" width="20%">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($models as $key => $model)
                    <tr>
                        <td>{{ $key + 1 + ($models->currentPage() - 1) * $models->perPage() }}</td>
                        <td>{{ $model->name }}</td>
                        <td>{{ $model->brand ? $model->brand->getTranslation('name') : '-' }}</td>
                        <td>{{ $model->modelGroup ? $model->modelGroup->name : '-' }}</td>
                        <td>{{ $model->year_from ?? '-' }}</td>
                        <td>{{ $model->year_to ?? '-' }}</td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('admin.auto-models.edit', $model->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('admin.auto-models.destroy', $model->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $models->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        function sort_auto_models() {
            $('#sort_auto_models').submit();
        }
    </script>
@endsection
