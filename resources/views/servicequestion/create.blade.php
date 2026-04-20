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
              <label class="form-label" for="question_no">{{ __('Question Number') }}</label>
              <input type="number" step="1" id="question_no" class="form-control" name="question_no" class="form-control{{ $errors->has('question_no') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->question_no : old('question_no') }}" required placeholder="Question Number">
              @if ($errors->has('question_no'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('question_no') }}</strong>
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
          <h6>Options</h6>
          <div class="row mb-3">
            <div class="col-md-12">
              @php
                $answerArray = array();
                if(!empty($aRow) && !empty($aRow->answer)){
                  $answerArray = json_decode($aRow->answer, true);
                }
              @endphp
              @if(empty($answerArray))
              <div class="row">
                <div class="col-md-7 d-flex align-items-center gap-2">
                    {{-- <strong>1. </strong> --}}
                    <input type="text" class="form-control" placeholder="Question Option" name="ques_opt[]" id="ques_opt" required>
                </div>
                <div class="col-md-2 d-flex align-items-center gap-2">
                    <input type="number" class="form-control" placeholder="Score" name="ques_score[]" id="ques_score" min="0" max="100" required>
                </div>
                <div class="col-md-2 d-flex align-items-center gap-2">
                    <input type="text" class="form-control" placeholder="Next Question Number" name="next_ques[]" id="next_ques" pattern="^\d+$|^last$" title="Enter a number or the word 'last'" required>
                </div>
              </div>
              @else
                  @foreach($answerArray as $index => $ans)
                  <div class="row" id="remove-section-option-pre-{{$index+1}}" style="margin-top:10px;">
                    <div class="col-md-7 d-flex align-items-center gap-2">
                        {{-- <strong> {{$count+1}}. </strong> --}}
                      <input type="hidden" name="old_ques_opt[]" value="{{$ans['option']}}" />
                      <input type="text" class="form-control" placeholder="Question Option" name="ques_opt[]" id="ques_opt{{$index+1}}" value="{{$ans['option']}}" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-center gap-2">
                        <input type="number" class="form-control" placeholder="Score" name="ques_score[]" id="ques_score{{$index+1}}" min="0" max="100" value="{{$ans['score'] ?? 1}}" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-center gap-2">
                        <input type="text" class="form-control" placeholder="Next Question Number" name="next_ques[]" id="next_ques{{$index+1}}" value="{{$ans['next_question']}}" pattern="^\d+$|^last$" title="Enter a number or the word 'last'" required>
                        @if($index > 0)
                          <i class="fa-solid fa-trash" style="color:red; cursor: pointer; " data-request="remove" data-target="#remove-section-option-pre-{{$index+1}}"></i>
                        @endif
                    </div>
                  </div>
                  @endforeach
              @endif
              <div id="consDiv" style="margin-top:10px;"></div>              
              <hr />
              <button  data-url="{{url('servicequestion/add-more-option')}}"   data-request="add-another" data-id="ques_opt" data-target="#consDiv" data-count="{{count($answerArray)}}" type="button" class="btn btn-info">Add More Option</button>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
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
            <div class="col-md-6">
              <label class="form-label" for="question_type">{{ __('Question Type') }}</label>
              <select name="question_type" class="form-control{{ $errors->has('question_type') ? ' is-invalid' : '' }}" required>
                <option value="compulsory" @if(isset($aRow->question_type) && $aRow->question_type == 'compulsory') selected @endif> Compulsory </option>
                <option value="optional" @if(isset($aRow->question_type) && $aRow->question_type == 'optional') selected @endif> Optional </option>
              </select>
              @if ($errors->has('question_type'))
                  <span class="invalid-feedback d-block" role="alert">
                      <strong>{{ $errors->first('question_type') }}</strong>
                  </span>
              @endif
            </div>

            
            
          </div>
         


          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           