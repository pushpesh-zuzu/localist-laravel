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
                    <input type="text" id="name" name="name" value="{{ $sector ? $sector['name'] : old('name') }}" required
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
                    <input type="text" id="homepage_display_name" name="homepage_display_name" value="{{ $sector ? $sector['homepage_display_name'] : old('homepage_display_name') }}" required
                        class="form-control {{$errors->has('homepage_display_name')?'is-invalid':''}}" placeholder="Home Page Display Name">
                </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="breadcrumb_title">Breadcrumb Title</label>
              <input type="text" id="breadcrumb_title" class="form-control" name="breadcrumb_title" class="form-control{{ $errors->has('breadcrumb_title') ? ' is-invalid' : '' }}"
              value="{{ $sector ? $sector['breadcrumb_title'] : old('breadcrumb_title') }}"  placeholder="Breadcrumb Title" required>
              @if ($errors->has('breadcrumb_title'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('breadcrumb_title') }}</strong>
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
            <div class="col-md-12">
                <label class="form-label">Service Tags (Popular Jobs / Sub Service)</label>
                <div class="flex-wrap gap-2 border rounded p-2" id="tagsContainer" style="display: none;">
                    <!-- Existing tags will render here -->
                </div>
                <input type="hidden" id="tags" name="tags"
                    value="{{ $sector ? $sector['tags'] : old('tags') }}">
                <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addTagModal">
                    Add Tag
                </button>
            </div>
          </div>

        <!-- Modal -->
        <div class="modal fade" id="addTagModal" tabindex="-1" aria-labelledby="addTagModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTagModalLabel">Add New Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="newTagInput" class="form-control" placeholder="Enter new tag">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="saveTagBtn">Save Tag</button>
            </div>
            </div>
        </div>
        </div>



          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label" for="is_home">{{ __('Show at Home') }}</label>
              <input type="radio" id="is_home"  @if($sector && $sector['is_home'] == 1) checked  @endif  name="is_home"  value="1" > Yes
              <input type="radio" id="is_home"  @if($sector && $sector['is_home'] == 0) checked  @endif  name="is_home"  value="0" > No
            </div>
            <div class="col-md-4">
              <label class="form-label" for="is_popular">{{ __('Is Popular Service') }}</label>
              <input type="radio" id="is_popular"  @if($sector && $sector['is_popular'] == 1) checked  @endif  name="is_popular"  value="1" > Yes
              <input type="radio" id="is_popular"  @if($sector && $sector['is_popular'] == 0) checked  @endif  name="is_popular"  value="0" > No
            </div>
            <div class="col-md-4">
              <label class="form-label" for="show_in_search">Show In Search</label>
              <input type="radio" id="show_in_search"  @if($sector && $sector['show_in_search'] == 1) checked  @endif  name="show_in_search"  value="1" > Yes
              <input type="radio" id="show_in_search"  @if($sector && $sector['show_in_search'] == 0) checked  @endif  name="show_in_search"  value="0" > No
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

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="credit_score_model">{{ __('Credit Score Model') }}</label>
              <select name="credit_score_model" class="form-control{{ $errors->has('credit_score_model') ? ' is-invalid' : '' }}" required>
                <option value="" @if(isset($sector['credit_score_model']) && $sector['credit_score_model'] == '') selected @endif> Select Model </option>
                <option value="python" @if(isset($sector['credit_score_model']) && $sector['credit_score_model'] == 'python') selected @endif> Python </option>
                <option value="laravel" @if(isset($sector['credit_score_model']) && $sector['credit_score_model'] == 'laravel') selected @endif> Laravel </option>
              </select>
              @if ($errors->has('credit_score_model'))
                  <span class="invalid-feedback d-block" role="alert">
                      <strong>{{ $errors->first('credit_score_model') }}</strong>
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
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap 5 JS (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
document.addEventListener("DOMContentLoaded", function () {
    let tagsInput = document.getElementById("tags");
    let tagsContainer = document.getElementById("tagsContainer");
    let saveTagBtn = document.getElementById("saveTagBtn");

    // Render existing tags (if any from DB/old input)
    function renderTags() {
        tagsContainer.innerHTML = "";
        let tags = tagsInput.value.split(",").filter(t => t.trim() !== "");

        if (tags.length > 0) {
            tagsContainer.style.display = "flex";
        } else {
            tagsContainer.style.display = "none";
        }

        tags.forEach(tag => {
            let span = document.createElement("span");
            span.className = "badge d-flex align-items-center gap-1";
            span.style.backgroundColor = "#9e9e9e";
            span.innerHTML = tag +
                ` <button type="button" class="btn-close btn-close-white btn-sm ms-2 removeTag" aria-label="Remove"></button>`;

            // Remove handler
            span.querySelector(".removeTag").addEventListener("click", function () {
                let currentTags = tagsInput.value.split(",").filter(t => t.trim() !== "");
                currentTags = currentTags.filter(t => t !== tag);
                tagsInput.value = currentTags.join(",");
                renderTags();
            });

            tagsContainer.appendChild(span);
        });
    }

    // Add new tag
    saveTagBtn.addEventListener("click", function () {
        let newTag = document.getElementById("newTagInput").value.trim();
        if (newTag) {
            let currentTags = tagsInput.value.split(",").filter(t => t.trim() !== "");
            currentTags.push(newTag);
            tagsInput.value = currentTags.join(",");
            document.getElementById("newTagInput").value = "";
            renderTags();

            // Close modal
            var modalEl = document.getElementById("addTagModal");
            var modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
        }
    });

    // Initial render on page load
    renderTags();
});
</script>






        <script>

            $(document).ready(function(){
              $("#is_subsector").change(function() {
              if(this.checked) {
                  $("#parent_cat_div").show();
              }else{
                  $("#parent_cat_div").hide();
              }

              });
              // $('#name').on('focusout', function() {
              //   let value = $(this).val();
              //   $('#homepage_display_name').val(value);
              //   $('#breadcrumb_title').val(value);
              // });

            });
            </script>
    @endpush
</x-app-layout>
