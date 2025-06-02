<x-app-layout>
	<x-slot name="header">@if($aRow) {{ __('Update Menu') }} @else {{ __('Add Menu') }} @endif  </x-slot>
	<div class="card mb-4">
		<div class="card-header">
			<strong>@if($aRow) {{ __('Update Menu') }} @else {{ __('Add Menu') }} @endif </strong>
		</div>
		<div class="card-body">
			@if($aRow)
			<form method="POST"  action="{{ route('menus.update',$aRow->id) }}" enctype="multipart/form-data">
				@method('PUT')
				@else
			<form method="POST"  action="{{ route('menus.store') }}" enctype="multipart/form-data">
				@endif 
				@csrf
                <div class="row mb-3">
                    <div class="col-md-12">
						<label class="form-label" for="page_type">
						{{ __('Menu Name') }}</label>
						<input type="text" id="menu_name" class="form-control" name="menu_name" class="form-control{{ $errors->has('menu_name') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->menu_name : old('menu_name') }}" required placeholder="Menu Name">
						@if ($errors->has('menu_name'))
                            <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $errors->first('menu_name') }}</strong>
                            </span>
						@endif
					</div>
                </div>
				<div class="row mb-3">
                    <div class="col-md-6" id="category_id">
						<label class="form-label" for="name">
						{{ __('Parent Menu') }}</label>
						<select name="menu_parent" class="form-control">
							<option value="">Select Menu</option>
							@if(count($parents) > 0)
							@foreach($parents as $value)
							<option value="{{$value->id}}" 
							@if(isset($aRow->menu_parent) && $aRow->menu_parent == $value->id) selected @endif>
							{{$value->menu_name}}
							</option>
							@endforeach
							@endif
						</select>
						@if ($errors->has('menu_parent'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('menu_parent') }}</strong>
						</span>
						@endif
					</div>
                    <div class="col-md-6" id="page_menu">
						<label class="form-label" for="page_menu">
						{{ __('Page') }}</label>
						<select name="menu_pageid" class="form-control" required>
							<option value="">Select Page</option>
							@if(count($pagemenu) > 0)
							@foreach($pagemenu as $value)
                                <option value="{{$value->id}}" 
                                    @if(isset($aRow->menu_pageid) && $aRow->menu_pageid == $value->id) selected @endif>
                                    {{$value->page_title}}
                                </option>
							@endforeach
							@endif
						</select>
						@if ($errors->has('menu_pageid'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('menu_pageid') }}</strong>
						</span>
						@endif
					</div>
				</div>
                
				<button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
			</form>
		</div>
	</div>


</x-app-layout>