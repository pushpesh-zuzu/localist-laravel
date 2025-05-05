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
              <label class="form-label" for="name">{{ __('Total No. of Bid') }}</label>
              <select name="total_bid" id="total_bid" class="form-control">
                <option value="3" {{ (isset($aRow) && $aRow->total_bid == 3) ? 'selected' : '' }}>3</option>
                <option value="4" {{ (isset($aRow) && $aRow->total_bid == 4) ? 'selected' : '' }}>4</option>
                <option value="5" {{ (isset($aRow) && $aRow->total_bid == 5) ? 'selected' : '' }}>5</option>
                <option value="6" {{ (isset($aRow) && $aRow->total_bid == 6) ? 'selected' : '' }}>6</option>
                <option value="7" {{ (isset($aRow) && $aRow->total_bid == 7) ? 'selected' : '' }}>7</option>
                <option value="8" {{ (isset($aRow) && $aRow->total_bid == 8) ? 'selected' : '' }}>8</option>
                <option value="9" {{ (isset($aRow) && $aRow->total_bid == 9) ? 'selected' : '' }}>9</option>
                <option value="10" {{ (isset($aRow) && $aRow->total_bid == 10) ? 'selected' : '' }}>10</option>
              </select>
              @if ($errors->has('total_bid'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('total_bid') }}</strong>
              </span>
              @endif
            </div>
          </div>
          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           