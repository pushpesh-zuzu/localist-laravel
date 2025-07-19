<div class="row" id="remove-section-option-{{$count+1}}" style="margin-top:10px;">
    <div class="col-md-8 d-flex align-items-center gap-2">
        {{-- <strong> {{$count+1}}. </strong> --}}
       <input type="text" class="form-control" placeholder="Question Option" name="ques_opt[]" id="ques_opt" required>
    </div>
    <div class="col-md-2 d-flex align-items-center gap-2">
        <input type="text" class="form-control" placeholder="Next Question Number" name="next_ques[]" id="next_ques" pattern="^\d+$|^last$" title="Enter a number or the word 'last'" required>
        <i class="fa-solid fa-trash" style="color:red; cursor: pointer; " data-request="remove" data-target="#remove-section-option-{{$count+1}}"></i>
    </div>
</div>