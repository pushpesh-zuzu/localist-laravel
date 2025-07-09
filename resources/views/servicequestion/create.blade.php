<x-app-layout>
    <x-slot name="header">@if($aRow) {{ __('Update Service Questions') }} @else {{ __('Add Questions') }} @endif  </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>@if($aRow) {{ __('Update Questions') }} @else {{ __('Add Questions') }} @endif </strong>
      </div>
      <div class="card-body">
          @if($aRow)
            <form method="POST"  action="{{ route('servicequestion.update',$aRow->id) }}" enctype="multipart/form-data">
          @method('PUT')
          @else
            <form method="POST"  action="{{ route('servicequestion.store') }}" enctype="multipart/form-data">
          @endif 

          @csrf

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label" for="name">{{ __('Category') }}</label>
              <select name="category" class="form-control{{ $errors->has('category') ? ' is-invalid' : '' }}" required>
                <option value="">Select Category</option>
                @if(count($categories) > 0)
                    @foreach($categories as $value)
                        <option value="{{$value->id}}" 
                            @if(isset($aRow->category) && $aRow->category == $value->id) selected @endif>
                            {{$value->name}}
                        </option>
                    @endforeach
                @endif
            </select>
            @if ($errors->has('category'))
                <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $errors->first('category') }}</strong>
                </span>
            @endif
            </div>
            
          </div>
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
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label" for="answer">{{ __('Answer') }}</label>
              <textarea class="form-control" id="answer" rows="3" name="answer" class="form-control{{ $errors->has('answer') ? ' is-invalid' : '' }}" 
              placeholder="Answer">{{ $aRow ? $aRow->answer : old('answer') }}</textarea>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label" for="option_type">{{ __('Options Selection') }}</label>
              <select name="option_type" class="form-control{{ $errors->has('option_type') ? ' is-invalid' : '' }}" required>
                <option value="single" @if(isset($aRow->option_type) && $aRow->option_type == 'single') selected @endif> Single </option>
                <option value="multiple" @if(isset($aRow->option_type) && $aRow->option_type == 'multiple') selected @endif> Multiple </option>
            </select>
            @if ($errors->has('option_type'))
                <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $errors->first('option_type') }}</strong>
                </span>
            @endif
            </div>
            
          </div>
         


          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           