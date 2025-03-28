<x-app-layout>
    <x-slot name="header">@if($aRow) {{ __('Update Sub Sector') }} @else {{ __('Add Sub Sector') }} @endif  </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>@if($aRow) {{ __('Update Sub Sector') }} @else {{ __('Add Sub Sector') }} @endif </strong>
      </div>
      <div class="card-body">
          @if($aRow)
            <form method="POST"  action="{{ route('subcategories.update',$aRow->id) }}" enctype="multipart/form-data">
          @method('PUT')
          @else
            <form method="POST"  action="{{ route('subcategories.store') }}" enctype="multipart/form-data">
          @endif 

          @csrf


          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Category') }}</label>
              <select name="parent_id" class="form-control" required>
                <option value="">Please Select</option>
                @foreach($aCategories as $id => $name)
                    <option value="{{ $id }}"  {{ isset($aRow->parent_id) && $aRow->parent_id == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
           
              
            </div>

          </div>


          
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
            <div class="col-md-6">
              <label class="form-label" for="category_icon">{{ __('Category Icon') }}</label>
              <input type="file" id="name" class="form-control" name="category_icon" class="form-control{{ $errors->has('category_icon') ? ' is-invalid' : '' }}" />
              @if($aRow && $aRow->category_icon) 
                <img src="{{ \App\Helpers\CustomHelper::displayImage($aRow->category_icon, 'category') }}" height="100" width="100" class="mt-2" />                
              @endif
              @if ($errors->has('category_icon'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('category_icon') }}</strong>
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
            <div class="col-md-12">
              <label class="form-label" for="is_home">{{ __('Show at Home') }}</label>
              <input type="radio" id="is_home"  @if($aRow && $aRow->is_home == 1) checked  @endif  name="is_home"  value="1" > Yes
              <input type="radio" id="is_home"  @if($aRow && $aRow->is_home == 0) checked  @endif  name="is_home"  value="0" > No
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
                <img src="{{ \App\Helpers\CustomHelper::displayImage($aRow->banner_image, 'category') }}" height="100" width="100" class="mt-2" />                
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
 
</x-app-layout>           