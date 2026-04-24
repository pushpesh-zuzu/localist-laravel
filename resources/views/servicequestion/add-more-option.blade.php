<div class="row" id="remove-section-option-{{$count+1}}" style="margin-top:10px;">
    <div class="col-md-7 d-flex align-items-center gap-2">
        {{-- <strong> {{$count+1}}. </strong> --}}
       <input type="text" class="form-control" placeholder="Question Option" name="ques_opt[]" id="ques_opt{{$count+1}}" required>
    </div>
    <div class="col-md-2 d-flex align-items-center gap-2">
        <input type="number" class="form-control" placeholder="Score" name="ques_score[]" id="ques_score{{$count+1}}" min="0" max="100" required>
    </div>
    <div class="col-md-2 d-flex align-items-center gap-2">
        <input type="text" class="form-control" placeholder="Next Question Number" name="next_ques[]" id="next_ques{{$count+1}}" pattern="^\d+$|^last$" title="Enter a number or the word 'last'" required>
        <i class="fa-solid fa-trash" style="color:red; cursor: pointer; " data-request="remove" data-target="#remove-section-option-{{$count+1}}"></i>
    </div>
</div>