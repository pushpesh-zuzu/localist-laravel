<x-app-layout>
    <x-slot name="header">@if($aRow) {{ __('Update Plan') }} @else {{ __('Add Plan') }} @endif  </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>@if($aRow) {{ __('Update Plan') }} @else {{ __('Add Plan') }} @endif </strong>
      </div>
      <div class="card-body">
          @if($aRow)
            <form method="POST"  action="{{ route('plans.update',$aRow->id) }}" enctype="multipart/form-data">
          @method('PUT')
          @else
            <form method="POST"  action="{{ route('plans.store') }}" enctype="multipart/form-data">
          @endif 

          @csrf

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Name') }}</label>
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
            <div class="col-md-4">
              <label class="form-label" for="terms_months">{{ __('Terms') }}</label>
              <select required id="terms_months"  name="terms_months" class="form-control{{ $errors->has('terms_months') ? ' is-invalid' : '' }}" >
                <option value="">Select Any</option>
                <option value="1" @if($aRow && $aRow->terms_months == 1) selected  @endif>Monthly</option>
                <option value="3" @if($aRow && $aRow->terms_months == 3) selected  @endif>3 Months</option>
                <option value="6" @if($aRow && $aRow->terms_months == 6) selected  @endif>6 Months</option>
                <option value="12" @if($aRow && $aRow->terms_months == 12) selected  @endif>Yearly</option>
              </select>
             
              
            </div>
            <div class="col-md-4">
              <label class="form-label" for="price">{{ __('Price') }}</label>
              <input  required type="text" id="price" name="price" class="form-control{{ $errors->has('price') ? ' is-invalid' : '' }}"  value="{{ $aRow ? $aRow->price : old('price') }}"/>
             
              @if ($errors->has('price'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('price') }}</strong>
              </span>
              @endif
            </div>

            <div class="col-md-4">
              <label class="form-label" for="price">{{ __('No of Credits') }}</label>
              <input required type="number" min="0" id="no_of_leads" name="no_of_leads" class="form-control{{ $errors->has('no_of_leads') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->no_of_leads : old('no_of_leads') }}" />
             
              @if ($errors->has('no_of_leads'))
              <span class="invalid-feedback  d-block" role="alert">
                <strong>{{ $errors->first('no_of_leads') }}</strong>
              </span>
              @endif
            </div>


          </div>




          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           