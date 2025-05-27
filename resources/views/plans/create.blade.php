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
            <div class="col-md-4">
              <label class="form-label" for="category_id">{{ __('Category') }}</label>
              <select required id="category_id"  name="category_id" class="form-control select2{{ $errors->has('category_id') ? ' is-invalid' : '' }}" >
                <option value="">Select Any</option>
                @foreach($category as $c)
                  <option value="{{$c->id}}" @if($aRow->category_id == $c->id) selected @endif>{{$c->name}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label" for="name">{{ __('Plan Name') }}</label>
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
              <input type="text" id="description" name="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}"  value="{{ $aRow ? $aRow->description : old('description') }}" placeholder="plan info"/>
             
              @if ($errors->has('description'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('description') }}</strong>
              </span>
              @endif
            </div>
          </div>


          <div class="row mb-3">
            
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
            <div class="col-md-4">
              <label class="form-label" for="plan_type">{{ __('Plan Type') }}</label>
              <select required id="plan_type"  name="plan_type" class="form-control{{ $errors->has('plan_type') ? ' is-invalid' : '' }}" >
                <option value="">Select Any</option>
                <option value="normal" @if($aRow && $aRow->plan_type == 'normal') selected  @endif>Normal Plan</option>
                <option value="starter" @if($aRow && $aRow->plan_type == 'starter') selected  @endif>Starter Pack</option>
              </select>
             
              
            </div>

          </div>




          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           