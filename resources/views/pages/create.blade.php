<x-app-layout>
	<x-slot name="header">@if($aRow) {{ __('Update Page') }} @else {{ __('Add Page') }} @endif  </x-slot>
	<div class="card mb-4">
		<div class="card-header">
			<strong>@if($aRow) {{ __('Update Page') }} @else {{ __('Add Page') }} @endif </strong>
		</div>
		<div class="card-body">
			@if($aRow)
			<form method="POST"  action="{{ route('pages.update',$aRow->id) }}" enctype="multipart/form-data">
				@method('PUT')
				@else
			<form method="POST"  action="{{ route('pages.store') }}" enctype="multipart/form-data">
				@endif 
				@csrf
                <div class="row mb-3">
                    <div class="col-md-12">
						<label class="form-label" for="page_type">
						{{ __('Page Type') }}</label>
						<select name="page_type" id="page_type" class="form-control" required>
							<!-- <option value="">Select Page</option> -->
							<option value="1" @if(isset($aRow->page_type) && $aRow->page_type == 1) selected @endif>Page</option>
							<option value="2" @if(isset($aRow->page_type) && $aRow->page_type == 2) selected @endif>Category</option>
						</select>
                        @error('page_type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
					</div>
                </div>
				<div class="row mb-3">
                    <div class="col-md-6" id="category_id">
						<label class="form-label" for="name">
						{{ __('Category') }}</label>
						<select name="category_id" class="form-control">
							<option value="">Select Category</option>
							@if(count($categories) > 0)
							@foreach($categories as $value)
							<option value="{{$value->id}}" 
							@if(isset($aRow->category_id) && $aRow->category_id == $value->id) selected @endif>
							{{$value->name}}
							</option>
							@endforeach
							@endif
						</select>
						@if ($errors->has('category_id'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('category_id') }}</strong>
						</span>
						@endif
					</div>
                    <div class="col-md-6" id="page_menu">
						<label class="form-label" for="page_menu">
						{{ __('Page Menu') }}</label>
						<select name="page_menu" class="form-control">
							<option value="">Select Page</option>
							@if(count($pagemenu) > 0)
							@foreach($pagemenu as $value)
                                <option value="{{$value->id}}" 
                                    @if(isset($aRow->page_menu) && $aRow->page_menu == $value->id) selected @endif>
                                    {{$value->page_title}}
                                </option>
							@endforeach
							@endif
						</select>
						@if ($errors->has('page_menu'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('page_menu') }}</strong>
						</span>
						@endif
					</div>
					<div class="col-md-6">
						<label class="form-label" for="page_title">{{ __('Page Name') }}</label>
						<input type="text" id="page_title" class="form-control" name="page_title" class="form-control{{ $errors->has('page_title') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->page_title : old('page_title') }}" required placeholder="Page Title">
						@if ($errors->has('page_title'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('page_title') }}</strong>
						</span>
						@endif
					</div>
				</div>
				<div class="row mb-3">
					<div class="col-md-12">
						<label class="form-label" for="banner_title">{{ __('Slug') }}</label>
						<input type="text" id="slug" class="form-control" name="slug" class="form-control{{ $errors->has('slug') ? ' is-invalid' : '' }}" 
							value="{{ $aRow ? $aRow->slug : old('slug') }}"  placeholder="Enter Slug">
						@if ($errors->has('slug'))
						<span class="invalid-feedback d-block" role="alert">
						<strong>{{ $errors->first('slug') }}</strong>
						</span>
						@endif
					</div>
					
				</div>
                <div class="row mb-3">
					<div class="col-md-12">
						<label class="form-label" for="title_desc">{{ __('Title Description') }}</label>
						<textarea class="form-control" id="title_desc" rows="10" name="title_desc" class="form-control{{ $errors->has('title_desc') ? ' is-invalid' : '' }}" 
							placeholder="">{{ $aRow ? $aRow->title_desc : old('title_desc') }}</textarea>
					</div>
				</div>
                <div class="row mb-3">
					<div class="col-md-12">
						<label class="form-label" for="page_details">{{ __('Page Details') }}</label>
						<textarea class="form-control" id="page_details" rows="10" name="page_details" class="form-control{{ $errors->has('page_details') ? ' is-invalid' : '' }}" 
							placeholder="">{{ $aRow ? $aRow->page_details : old('page_details') }}</textarea>
					</div>
				</div>
                <div class="row mb-5">
					<div class="col-md-6">
						<label class="form-label" for="banner_image">{{ __('Banner Image') }}</label>
						<input type="file" id="banner_image" class="form-control" name="banner_image" class="form-control{{ $errors->has('banner_image') ? ' is-invalid' : '' }}" />
						@if($aRow && $aRow->banner_image) 
						<img src="{{ \App\Helpers\CustomHelper::displayImage($aRow->banner_image, 'pages') }}" height="100" width="100" class="mt-2" />                
						@endif
						@if ($errors->has('banner_image d-block'))
						<span class="invalid-feedback" role="alert">
						<strong>{{ $errors->first('banner_image') }}</strong>
						</span>
						@endif
					</div>
                    <div class="col-md-6">
						<label class="form-label" for="og_image">{{ __('OG Image') }}</label>
						<input type="file" id="og_image" class="form-control" name="og_image" class="form-control{{ $errors->has('og_image') ? ' is-invalid' : '' }}" />
						@if($aRow && $aRow->og_image) 
						<img src="{{ \App\Helpers\CustomHelper::displayImage($aRow->og_image, 'pages') }}" height="100" width="100" class="mt-2" />                
						@endif
						@if ($errors->has('og_image d-block'))
						<span class="invalid-feedback" role="alert">
						<strong>{{ $errors->first('og_image') }}</strong>
						</span>
						@endif
					</div>
				</div>
                <hr>
                <div class="card card-border">
                    <div class="card-header">
                        <h5>Seo Information</h5> 
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label" for="seo_title">{{ __('Seo Title') }}</label>
                                <input type="text" id="seo_title" class="form-control" name="seo_title" class="form-control{{ $errors->has('seo_title') ? ' is-invalid' : '' }}" 
                                    value="{{ $aRow ? $aRow->seo_title : old('seo_title') }}" required placeholder="Seo Title">              
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label" for="seo_keyword">{{ __('Seo Keyword') }}</label>
                                <input type="text" id="seo_keyword" class="form-control" name="seo_keyword" class="form-control{{ $errors->has('seo_keyword') ? ' is-invalid' : '' }}" 
                                    value="{{ $aRow ? $aRow->seo_keyword : old('seo_keyword') }}" required placeholder="Seo Keyword">              
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label" for="seo_description">{{ __('Seo Description') }}</label>
                                <textarea class="form-control" id="seo_description" rows="3" name="seo_description" class="form-control{{ $errors->has('seo_description') ? ' is-invalid' : '' }}" 
                                    placeholder="Seo Description">{{ $aRow ? $aRow->seo_description : old('seo_description') }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <div class="col-md-12">
                                <label class="form-label" for="page_script">{{ __('Page Script') }}</label>
                                <textarea class="form-control" id="page_script" rows="10" name="page_script" class="form-control{{ $errors->has('page_script') ? ' is-invalid' : '' }}" 
                                    placeholder="">{{ $aRow ? $aRow->page_script : old('page_script') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                 <hr>
                <div class="card card-border">
                    <div class="card-header">
                        <h5>Lower Section</h5> 
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label" for="lower_section_title">{{ __('Title') }}</label>
                                <input type="text" id="lower_section_title" class="form-control" name="lower_section_title" class="form-control{{ $errors->has('lower_section_title') ? ' is-invalid' : '' }}" 
                                    value="{{ $aRow ? $aRow->lower_section_title : old('lower_section_title') }}" required placeholder="Title">              
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label" for="lower_section_desc">{{ __('Description') }}</label>
                                <textarea class="form-control" id="lower_section_desc" rows="10" name="lower_section_desc" class="form-control{{ $errors->has('lower_section_desc') ? ' is-invalid' : '' }}" 
                                    placeholder="">{{ $aRow ? $aRow->lower_section_desc : old('lower_section_desc') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="card card-border">
						<div class="card-header">
							<h5>FAQs</h5> </div>
						<div class="card-body">
						   @if(isset($aRow->faqs) && $aRow->faqs !='') 
    							@foreach($aRow->faqs as $value)
    							    <div class="remove_tabs">
        								<div class="row mb-3 ">
        							        <label class="form-label">Questions </label>
            								<div class="col-lg-12">
            									<input type="text" class="form-control required" name="faq_ques[]" value="{{$value->question}}">
            								</div>
        								</div>
        								<div class="row mb-3 ">
            								<div class="col-lg-12">
        										<label class="form-label">Answers</label>
        										<textarea name="faq_ans[]" class="form-control ckeditor" id="tabsId'+tabscounter+'">@if(isset($value->answer) && $value->answer !=''){!!$value->answer!!}@else{{''}}@endif</textarea>
            								</div>
            							</div>
            							<div class="row mb-3">
            								<div class="col-lg-11"></div>
            								<div class="col-lg-1"> 
            									<a href="javascript:void(0)" class="btn btn-danger me-1" onclick="removeTabs($(this))" style="margin-left: auto;">
											<i class="fas fa-trash"></i>
										</a>
            								</div>
            							</div>
    								</div>
    							@endforeach 
					        @endif
							<div class="row mb-3">
							     <label class="form-label">Questions </label>
							     @php $tabscounter = 1; @endphp
								<div class="col-lg-12">
									<input type="text" name="faq_ques[]" id="" class="form-control requireds faq_ques"/> 
								</div>
							</div>
							<div class="row mb-3">
							    <label class="form-label">Answers</label>
								<div class="col-lg-12">
								   
									<textarea  name="faq_ans[]" class="form-control ckeditor" id="tabsId{{$tabscounter++}}"></textarea> 
								</div>
							</div>
							<div class="row mb-3">
								<div class="col-lg-11"></div>
								<div class="col-lg-1"> 
									<a href="javascript:void(0)" class="btn btn-primary me-1" onclick="addCustomeTabs();"><i class="fas fa-plus"></i></a> 
								</div>
							</div>
							<div id="append_tabs"></div>
						</div>
					</div>
				<button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
			</form>
		</div>
	</div>
<script>
    $(document).ready(function() {
        function toggleFields() {
            var type = $('#page_type').val();

            if (type == '1') { // Page
                $('#page_menu').show();
                $('#page_menu select').prop('disabled', false);

                $('#category_id').hide();
                $('#category_id select').prop('required', false).prop('disabled', true);
            } else if (type == '2') { // Category
                $('#category_id').show();
                $('#category_id select').prop('required', true).prop('disabled', false);

                $('#page_menu').hide();
                $('#page_menu select').prop('required', false).prop('disabled', true);
            } else {
                $('#category_id, #page_menu').hide();
                $('#category_id select, #page_menu select').prop('required', false).prop('disabled', true);
            }
        }

        $('#page_type').change(function() {
            toggleFields();
        });

        toggleFields(); // initial call
    });
var tabscounter = {{$tabscounter}};console.log(tabscounter);
    	function addCustomeTabs() {
    		var custome_tabs = $('.requireds').val();
    		if(custome_tabs == ''){
    			$('.faq_ques').css('border','1px solid red');
    		}else{
    			$('.requireds').css('border','');
    			var tabshtmldatas = '<div class="remove_tabs"><div class="row mb-3"><label class="form-label">Questions </label><div class="col-lg-12"><input type="text" name="faq_ques[]" id="" class="form-control requireds faq_ques"/> </div></div><div class="row mb-3"><label class="form-label">Answers </label><div class="col-lg-12"><textarea  name="faq_ans[]" class="form-control ckeditor" id="tabsId'+tabscounter+'"></textarea></div></div><div class="row mb-3"><div class="col-lg-11"></div><div class="col-lg-1"> <a href="javascript:void(0)" class="btn btn-danger me-1" onclick="removeTabs($(this))" style="margin-left: auto;"><i class="fas fa-trash"></i></a></div></div></div></div>';
    			$("#append_tabs").append(tabshtmldatas);
    				CKEDITOR.replace('tabsId'+tabscounter);
    				CKEDITOR.config.allowedContent = true;
    				// $('.requireds').val('');
    				$('.requireds').focus();
    			tabscounter++;
    		}
    	
    }

	function removeTabs(objectElement) {
		var condida = confirm("Are you sure you want to delete?");
		if(condida) {
			objectElement.parents(".remove_tabs").remove();
		}
	}
</script>

</x-app-layout>