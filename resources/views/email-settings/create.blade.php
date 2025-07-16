<x-app-layout>
	<x-slot name="header">@if($aRow) {{ __('Update Email Setting') }} @else {{ __('Add Email Setting') }} @endif  </x-slot>
	<div class="card mb-4">
		<div class="card-body">
			@if($aRow)
			<form method="POST"  action="{{ route('email-settings.update',$aRow->id) }}" enctype="multipart/form-data">
				@method('PUT')
				@else
			<form method="POST"  action="{{ route('email-settings.store') }}" enctype="multipart/form-data">
				@endif 
				@csrf
                <div class="row mb-3">
                    <div class="col-md-12">
						<label class="form-label" for="setting_name">Setting Name</label>
						<input type="text" id="setting_name" class="form-control" name="setting_name" class="form-control{{ $errors->has('setting_name') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->setting_name : old('setting_name') }}" required placeholder="Setting Name">
						@if ($errors->has('setting_name'))
                            <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $errors->first('setting_name') }}</strong>
                            </span>
						@endif
					</div>
                </div>
				<div class="row mb-3">
                    
                    
				</div>
                
				<button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
			</form>
		</div>
	</div>


</x-app-layout>