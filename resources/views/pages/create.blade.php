<x-app-layout>
	<x-slot name="header">@if($aRow) {{ __('Update Page') }} @else {{ __('Add Page') }} @endif  </x-slot>
	<div class="card mb-4">
		<div class="card-header">
			<strong>@if($aRow) {{ __('Update Page') }} @else {{ __('Add Page') }} @endif </strong>
		</div>
		<div class="card-body">
			@if($aRow)
			<form method="POST"  action="{{ route('pages.update',$aRow->id) }}" enctype="multipart/form-data">
				@method('PUT')
				@else
			<form method="POST"  action="{{ route('pages.store') }}" enctype="multipart/form-data">
				@endif 
				@csrf
                <div class="row mb-3">
                    <div class="col-md-12">
						<label class="form-label" for="name">
						{{ __('Page Type') }}</label>
						<select name="page_type" id="page_type" class="form-control{{ $errors->has('page_type') ? ' is-invalid' : '' }}" required>
							<option value="">Select Page</option>
							<option value="1">Page</option>
							<option value="2">Category</option>
						</select>
                        @error('page_type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
					</div>
                </div>
				<div class="row mb-3">
                    <div class="col-md-6" id="category_id">
						<label class="form-label" for="name">
						{{ __('Category') }}</label>
						<select name="category_id" class="form-control{{ $errors->has('category_id') ? ' is-invalid' : '' }}" required>
							<option value="">Select Category</option>
							@if(count($categories) > 0)
							@foreach($categories as $value)
							<option value="{{$value->id}}" 
							@if(isset($aRow->category_id) && $aRow->category_id == $value->id) selected @endif>
							{{$value->name}}
							</option>
							@endforeach
							@endif
						</select>
						@if ($errors->has('category_id'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('category_id') }}</strong>
						</span>
						@endif
					</div>
                    <div class="col-md-6" id="page_menu">
						<label class="form-label" for="name">
						{{ __('Page Menu') }}</label>
						<select name="page_menu" class="form-control{{ $errors->has('page_menu') ? ' is-invalid' : '' }}" required>
							<option value="">Select Page</option>
							@if(count($pagemenu) > 0)
							@foreach($pagemenu as $value)
							<option value="{{$value->id}}" 
                                @if(isset($aRow->id) && $aRow->id == $value->id) selected @endif>
                                {{$value->page_menu}}
							</option>
							@endforeach
							@endif
						</select>
						@if ($errors->has('page_menu'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('page_menu') }}</strong>
						</span>
						@endif
					</div>
					<div class="col-md-6">
						<label class="form-label" for="name">{{ __('Page Name') }}</label>
						<input type="text" id="name" class="form-control" name="name" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->name : old('name') }}" required placeholder="Name">
						@if ($errors->has('name'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('name') }}</strong>
						</span>
						@endif
					</div>
					
				</div>
				<div class="row mb-3">
					<div class="col-md-12">
						<label class="form-label" for="description">{{ __('Description') }}</label>
						<textarea class="form-control" id="description" rows="3" name="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" 
							placeholder="Description">{{ $aRow ? $aRow->description : old('description') }}</textarea>
					</div>
				</div>
				<div class="row mb-3">
					<div class="col-md-6">
						<label class="form-label" for="banner_title">{{ __('Banner Title') }}</label>
						<input type="text" id="banner_title" class="form-control" name="banner_title" class="form-control{{ $errors->has('banner_title') ? ' is-invalid' : '' }}" 
							value="{{ $aRow ? $aRow->banner_title : old('banner_title') }}"  placeholder="Banner Title">
						@if ($errors->has('banner_title'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('banner_title') }}</strong>
						</span>
						@endif
					</div>
					<div class="col-md-6">
						<label class="form-label" for="banner_image">{{ __('Banner Image') }}</label>
						<input type="file" id="name" class="form-control" name="banner_image" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" />
						@if($aRow && $aRow->banner_image) 
						<img src="{{ \App\Helpers\CustomHelper::displayImage($aRow->banner_image, 'blogs') }}" height="100" width="100" class="mt-2" />                
						@endif
						@if ($errors->has('banner_image d-block'))
						<span class="invalid-feedback" role="alert">
						<strong>{{ $errors->first('banner_image') }}</strong>
						</span>
						@endif
					</div>
				</div>
				<h5 class="mt-5 mb-3">Seo Information</h5>
				<div class="row mb-3">
					<div class="col-md-12">
						<label class="form-label" for="seo_title">{{ __('Seo Title') }}</label>
						<input type="text" id="seo_title" class="form-control" name="seo_title" class="form-control{{ $errors->has('seo_title') ? ' is-invalid' : '' }}" 
							value="{{ $aRow ? $aRow->seo_title : old('seo_title') }}" required placeholder="Seo Title">              
					</div>
				</div>
				<div class="row mb-3">
					<div class="col-md-12">
						<label class="form-label" for="seo_description">{{ __('Seo Description') }}</label>
						<textarea class="form-control" id="seo_description" rows="3" name="seo_description" class="form-control{{ $errors->has('seo_description') ? ' is-invalid' : '' }}" 
							placeholder="Seo Description">{{ $aRow ? $aRow->seo_description : old('seo_description') }}</textarea>
					</div>
				</div>
				<button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
			</form>
		</div>
	</div>
<script>
    $(document).ready(function() {
        function toggleFields() {
            var type = $('#page_type').val();
            if (type == '1') { // Page
                $('#page_menu').show();
                $('#category_id').hide();
            } else if (type == '2') { // Category
                $('#page_menu').hide();
                $('#category_id').show();
            } else {
                $('#page_menu').hide();
                $('#category_id').hide();
            }
        }

        // Run on change
        $('#page_type').change(function() {
            toggleFields();
        });

        // Run on page load in case of edit
        toggleFields();
    });
</script>

</x-app-layout>