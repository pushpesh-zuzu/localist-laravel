<x-app-layout>
    <x-slot name="header">@if($aRow) {{ __('Update Settings') }} @else {{ __('Add Settings') }} @endif  </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>@if($aRow) {{ __('Update Settings') }} @else {{ __('Add Settings') }} @endif </strong>
      </div>
      <div class="card-body">
        @if(isset($aRow->id) && $aRow->id > 0)
            <form method="POST" action="{{ route('settings.update', $aRow->id) }}" enctype="multipart/form-data">
                @method('PUT')
        @else
            <form method="POST" action="{{ route('settings.store') }}" enctype="multipart/form-data">
        @endif

          @csrf

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Setting Name') }}</label>
              <input type="text" id="setting_name" class="form-control" name="setting_name" class="form-control{{ $errors->has('setting_name') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->setting_name : old('setting_name') }}" required placeholder="Setting Name">
              @if ($errors->has('setting_name'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('setting_name') }}</strong>
              </span>
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Setting Value') }}</label>
              <input type="text" id="setting_value" class="form-control" name="setting_value" class="form-control{{ $errors->has('setting_value') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->setting_value : old('setting_value') }}" required placeholder="Setting Value">
              @if ($errors->has('setting_value'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('setting_value') }}</strong>
              </span>
              @endif
            </div>
          </div>
          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           