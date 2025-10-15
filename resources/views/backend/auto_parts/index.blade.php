@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('All Auto Parts') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('admin.auto-parts.create') }}" class="btn btn-primary">
                <span>{{ translate('Add New Auto Part') }}</span>
            </a>
        </div>
    </div>
</div>
<br>

<div class="card">
    <form class="" id="sort_auto_parts" action="" method="GET">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-md-0 h6">{{ translate('Auto Parts') }}</h5>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" @isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" type="submit">{{ translate('Filter') }}</button>
            </div>
        </div>
    </form>

    <div class="card-body">
        <table class="table aiz-table mb-0 footable footable-1 breakpoint-xl" style="">
            <thead>
                <tr class="footable-header">
                    <th>#</th>
                    <th>{{ translate('Name (AZ)') }}</th>
                    <th>{{ translate('Name (EN)') }}</th>
                    <th data-breakpoints="md">{{ translate('Name (RU)') }}</th>
                    <th data-breakpoints="md">{{ translate('Name (ZH)') }}</th>
                    <th class="text-right" width="20%">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($parts as $key => $part)
                    <tr>
                        <td>{{ $key + 1 + ($parts->currentPage() - 1) * $parts->perPage() }}</td>
                        <td>{{ $part->getTranslation('name', 'az') }}</td>
                        <td>{{ $part->getTranslation('name', 'en') }}</td>
                        <td>{{ $part->getTranslation('name', 'ru') }}</td>
                        <td>{{ $part->getTranslation('name', 'zh') }}</td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('admin.auto-parts.edit', $part->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('admin.auto-parts.destroy', $part->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $parts->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection
