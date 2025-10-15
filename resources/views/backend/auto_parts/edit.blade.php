@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{ translate('Edit Auto Part') }}</h1>
		</div>
	</div>
</div>

<div class="col-lg-10 mx-auto">
	<div class="card">
		<div class="card-header">
			<h5 class="mb-0 h6">{{ translate('Auto Part Information') }}</h5>
		</div>
		<div class="card-body">
			<form action="{{ route('admin.auto-parts.update', $part->id) }}" method="POST">
				<input type="hidden" name="_method" value="PATCH">
				@csrf

				@php
					$translations = [];
					foreach ($part->translations as $trans) {
						$translations[$trans->lang] = $trans;
					}
				@endphp

				<div class="card mb-3 shadow-sm">
					<div class="card-header bg-light">
						<h6 class="mb-0"><i class="las la-language"></i> {{ translate('Azerbaijani (AZ)') }}</h6>
					</div>
					<div class="card-body">
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
							<div class="col-md-9">
								<input type="text" name="name_az" value="{{ $translations['az']->name ?? '' }}" class="form-control" placeholder="{{ translate('Enter part name in Azerbaijani') }}" required>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Description') }}</label>
							<div class="col-md-9">
								<textarea name="description_az" class="form-control" rows="3" placeholder="{{ translate('Enter description in Azerbaijani') }}">{{ $translations['az']->description ?? '' }}</textarea>
							</div>
						</div>
						<div class="form-group row mb-0">
							<label class="col-md-3 col-form-label">{{ translate('Search Keywords') }}</label>
							<div class="col-md-9">
								<input type="text" name="search_keywords_az" value="{{ $translations['az']->search_keywords ?? '' }}" class="form-control" placeholder="{{ translate('motor yağı, yağ, oil') }}">
								<small class="text-muted">{{ translate('Comma separated keywords for search optimization') }}</small>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-3 shadow-sm">
					<div class="card-header bg-light">
						<h6 class="mb-0"><i class="las la-language"></i> {{ translate('English (EN)') }}</h6>
					</div>
					<div class="card-body">
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
							<div class="col-md-9">
								<input type="text" name="name_en" value="{{ $translations['en']->name ?? '' }}" class="form-control" placeholder="{{ translate('Enter part name in English') }}" required>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Description') }}</label>
							<div class="col-md-9">
								<textarea name="description_en" class="form-control" rows="3" placeholder="{{ translate('Enter description in English') }}">{{ $translations['en']->description ?? '' }}</textarea>
							</div>
						</div>
						<div class="form-group row mb-0">
							<label class="col-md-3 col-form-label">{{ translate('Search Keywords') }}</label>
							<div class="col-md-9">
								<input type="text" name="search_keywords_en" value="{{ $translations['en']->search_keywords ?? '' }}" class="form-control" placeholder="{{ translate('engine oil, oil, filter') }}">
								<small class="text-muted">{{ translate('Comma separated keywords for search optimization') }}</small>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-3 shadow-sm">
					<div class="card-header bg-light">
						<h6 class="mb-0"><i class="las la-language"></i> {{ translate('Russian (RU)') }}</h6>
					</div>
					<div class="card-body">
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
							<div class="col-md-9">
								<input type="text" name="name_ru" value="{{ $translations['ru']->name ?? '' }}" class="form-control" placeholder="{{ translate('Enter part name in Russian') }}" required>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Description') }}</label>
							<div class="col-md-9">
								<textarea name="description_ru" class="form-control" rows="3" placeholder="{{ translate('Enter description in Russian') }}">{{ $translations['ru']->description ?? '' }}</textarea>
							</div>
						</div>
						<div class="form-group row mb-0">
							<label class="col-md-3 col-form-label">{{ translate('Search Keywords') }}</label>
							<div class="col-md-9">
								<input type="text" name="search_keywords_ru" value="{{ $translations['ru']->search_keywords ?? '' }}" class="form-control" placeholder="{{ translate('масло двигателя, масло, фильтр') }}">
								<small class="text-muted">{{ translate('Comma separated keywords for search optimization') }}</small>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-3 shadow-sm">
					<div class="card-header bg-light">
						<h6 class="mb-0"><i class="las la-language"></i> {{ translate('Chinese (ZH)') }}</h6>
					</div>
					<div class="card-body">
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
							<div class="col-md-9">
								<input type="text" name="name_zh" value="{{ $translations['zh']->name ?? '' }}" class="form-control" placeholder="{{ translate('Enter part name in Chinese') }}" required>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-md-3 col-form-label">{{ translate('Description') }}</label>
							<div class="col-md-9">
								<textarea name="description_zh" class="form-control" rows="3" placeholder="{{ translate('Enter description in Chinese') }}">{{ $translations['zh']->description ?? '' }}</textarea>
							</div>
						</div>
						<div class="form-group row mb-0">
							<label class="col-md-3 col-form-label">{{ translate('Search Keywords') }}</label>
							<div class="col-md-9">
								<input type="text" name="search_keywords_zh" value="{{ $translations['zh']->search_keywords ?? '' }}" class="form-control" placeholder="{{ translate('发动机油, 油, 过滤器') }}">
								<small class="text-muted">{{ translate('Comma separated keywords for search optimization') }}</small>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group mb-0 text-right">
					<button type="submit" class="btn btn-primary btn-lg">
						<i class="las la-save"></i> {{ translate('Update Auto Part') }}
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection
