<x-app-layout>
    <x-slot name="header">@if($aRow) {{ __('Update Profile Questions') }} @else {{ __('Add Questions') }} @endif  </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>@if($aRow) {{ __('Update Questions') }} @else {{ __('Add Questions') }} @endif </strong>
      </div>
      <div class="card-body">
          @if($aRow)
            <form method="POST"  action="{{ route('profilequestion.update',$aRow->id) }}" enctype="multipart/form-data">
          @method('PUT')
          @else
            <form method="POST"  action="{{ route('profilequestion.store') }}" enctype="multipart/form-data">
          @endif 

          @csrf
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label" for="name">{{ __('Questions') }}</label>
              <input type="text" id="questions" class="form-control" name="questions" class="form-control{{ $errors->has('questions') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->questions : old('questions') }}" required placeholder="Questions">
              @if ($errors->has('questions'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('questions') }}</strong>
              </span>
              @endif
            </div>
            
          </div>
          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           