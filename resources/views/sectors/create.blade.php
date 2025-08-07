<x-app-layout>
    <x-slot name="header">@if($sector) {{ __('Update Sector') }} @else {{ __('Add Sector') }} @endif  </x-slot>

    <div class="card mb-4">
      
      <div class="card-body">
          @if($sector)
            <form method="POST"  action="{{ url('sectors/' . $sector['id']) }}" enctype="multipart/form-data">
            @method('PUT')
          @else
            <form method="POST"  action="{{ route('sectors.store') }}" enctype="multipart/form-data">
          @endif 

          @csrf

          <div class="row mb-3">
            <div class="col-md-5 mb-3">
                <div class="form-group">
                    <label class="required">Sector Name (Mega Menu)</label>
                    <input type="text" name="name" value="{{ $sector ? $sector['name'] : old('name') }}" required
                        class="form-control {{$errors->has('name')?'is-invalid':''}}" placeholder="Sector Name">
                </div>                            
            </div>
            @if(count($sectorsList) > 0)
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                        <input class="form-label" type="checkbox" name="is_subsector" id="is_subsector" value="yes" {{ !empty($sector['parent_id']) ? 'checked' : '' }}>
                        <label for="is_subsector" class="custom-control-label">Add as sub sector</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group" id="parent_cat_div" @if(empty($sector['parent_id'])) style="display:none;" @endif >
                        <label>Parent Sector <span class="required">*</span></label>
                        <select class="form-control select2" name="pr_id" id="pr_id" style="width: 100%;">
                            <option selected="selected" disabled>Select Sector</option>
                            @foreach($sectorsList as $sl)
                                <option value="{{$sl->id}}" {{ isset($sector['parent_id']) && $sector['parent_id'] == $sl->id ? 'selected' : '' }}>{{$sl->name}}</option>
                            @endforeach                                                                
                        </select>
                    </div>
                </div>                        
            @endif
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Home Page Display Name</label>
                    <input type="text" name="homepage_display_name" value="{{ $sector ? $sector['homepage_display_name'] : old('homepage_display_name') }}" required
                        class="form-control {{$errors->has('homepage_display_name')?'is-invalid':''}}" placeholder="Home Page Display Name">
                </div>                            
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="banner_title">{{ __('Banner Title') }}</label>
              <input type="text" id="banner_title" class="form-control" name="banner_title" class="form-control{{ $errors->has('banner_title') ? ' is-invalid' : '' }}" 
              value="{{ $sector ? $sector['banner_title'] : old('banner_title') }}"  placeholder="Banner Title" required>
              @if ($errors->has('banner_title'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('banner_title') }}</strong>
              </span>
              @endif
            </div>

            
            
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label" for="description">{{ __('Description') }}</label>
              <textarea class="form-control" id="description" rows="3" name="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" 
              placeholder="Description">{{ $sector ? $sector['description'] : old('description') }}</textarea>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="is_home">{{ __('Show at Home') }}</label>
              <input type="radio" id="is_home"  @if($sector && $sector['is_home'] == 1) checked  @endif  name="is_home"  value="1" > Yes
              <input type="radio" id="is_home"  @if($sector && $sector['is_home'] == 0) checked  @endif  name="is_home"  value="0" checked> No
            </div>
            <div class="col-md-6">
              <label class="form-label" for="is_popular">{{ __('Is Popular Service') }}</label>
              <input type="radio" id="is_popular"  @if($sector && $sector['is_popular'] == 1) checked  @endif  name="is_popular"  value="1" > Yes
              <input type="radio" id="is_popular"  @if($sector && $sector['is_popular'] == 0) checked  @endif  name="is_popular"  value="0" checked> No
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="category_icon">{{ __('Category Icon') }}</label>
              <input type="file" id="name" class="form-control" name="category_icon" class="form-control{{ $errors->has('category_icon') ? ' is-invalid' : '' }}" />
              @if($sector && $sector['category_icon']) 
                <img src="{{ \App\Helpers\CustomHelper::displayImage($sector['category_icon'], 'category') }}" height="100" width="100" class="mt-2" />                
              @endif
              @if ($errors->has('category_icon'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('category_icon') }}</strong>
              </span>
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label" for="banner_image">{{ __('Banner Image') }}</label>
              <input type="file" id="name" class="form-control" name="banner_image" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" />
              @if($sector && $sector['banner_image']) 
                <img src="{{ \App\Helpers\CustomHelper::displayImage($sector['banner_image'], 'category') }}" height="100" width="100" class="mt-2" />                
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
              value="{{ $sector ? $sector['seo_title'] : old('seo_title') }}" placeholder="Seo Title">              
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label" for="seo_description">{{ __('Seo Description') }}</label>
              <textarea class="form-control" id="seo_description" rows="3" name="seo_description" class="form-control{{ $errors->has('seo_description') ? ' is-invalid' : '' }}" 
              placeholder="Seo Description">{{ $sector ? $sector['seo_description'] : old('seo_description') }}</textarea>

            </div>
          </div>


          <button type="submit" class="btn btn-dark mt-4">@if($sector) Update @else Save @endif </button>
          </form>
      </div>
    </div>

    @push('scripts')
        <script>

            $(document).ready(function(){
                $("#is_subsector").change(function() {
                if(this.checked) {
                    $("#parent_cat_div").show();
                }else{
                    $("#parent_cat_div").hide();
                }

                });
            });
            </script>
    @endpush
</x-app-layout>           