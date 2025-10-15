@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
	<h5 class="mb-0 h6">{{ translate('Auto Model Information') }}</h5>
</div>

<div class="col-lg-8 mx-auto">
	<div class="card">
		<div class="card-body p-0">
			<form class="p-4" action="{{ route('admin.auto-models.store') }}" method="POST">
				@csrf
				<div class="form-group row">
					<label class="col-md-3 col-from-label">{{ translate('Brand') }} <span class="text-danger">*</span></label>
					<div class="col-md-9">
						<select class="form-control aiz-selectpicker" name="brand_id" id="brand_id" data-live-search="true" required>
							<option value="">{{ translate('Select Brand') }}</option>
							@foreach ($brands as $brand)
								<option value="{{ $brand->id }}">{{ $brand->getTranslation('name') }}</option>
							@endforeach
						</select>
					</div>
				</div>
				<div class="form-group row">
					<label class="col-md-3 col-from-label">{{ translate('Model Name') }} <span class="text-danger">*</span></label>
					<div class="col-md-9">
						<input type="text" name="name" class="form-control" placeholder="{{ translate('Model Name') }}" required>
					</div>
				</div>
				<div class="form-group row">
					<label class="col-md-3 col-from-label">{{ translate('Model Group') }}</label>
					<div class="col-md-9">
						<select class="form-control aiz-selectpicker" name="auto_model_group_id" data-live-search="true">
							<option value="">{{ translate('Select Model Group') }}</option>
							@foreach ($modelGroups as $group)
								<option value="{{ $group->id }}">{{ $group->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				<div class="form-group row">
					<label class="col-md-3 col-from-label">{{ translate('Year From') }}</label>
					<div class="col-md-9">
						<input type="number" name="year_from" class="form-control" placeholder="{{ translate('Year From') }}" min="1900" max="2100">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-md-3 col-from-label">{{ translate('Year To') }}</label>
					<div class="col-md-9">
						<input type="number" name="year_to" class="form-control" placeholder="{{ translate('Year To') }}" min="1900" max="2100">
					</div>
				</div>
				<div class="form-group mb-0 text-right">
					<button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection
