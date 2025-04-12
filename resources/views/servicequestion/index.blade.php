<x-app-layout>
    <x-slot name="header">{{ __('Service Questions') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Questions & Answers') }}</strong>
          <a href="{{ route('servicequestion.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Questions') }}</a>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
          <table class="table table-bordered" id="dataTable">
            <thead>
            <tr>
              <th scope="col" width="20px;">#</th>
              <th scope="col">Category</th>
              <th scope="col">Questions</th>
              <th scope="col">Status</th>
              <th scope="col">Action</th>
            </tr>
            </thead>
            <tbody>
          
            @foreach($aRows as $aKey => $aRow)
            <tr>
              <th scope="row">{{ $aKey+1 }}</th>
              <td>{{ $aRow->name ?? '' }}</td>
              <td>
                @if(count($aRow->servQuestions)>0)
                  @foreach($aRow->servQuestions as $ques)
                      <span class="fw-bold">Ques:</span> {{$ques['questions']}}<br/>
                      <span class="fw-bold">Soln:</span> {{$ques['answer']}}</br/>
                      @if (!$loop->last)
                          <hr>
                      @endif
                  @endforeach
                @endif  
               </td>
              <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
              <td>
                  <a href="{{ route('servicequestion.edit',$aRow->id) }}"><i class="icon  cil-pencil"></i></a>
                  <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();"><i class="icon cil-trash"></i>
                  </a>
                  <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('servicequestion.destroy',$aRow->id) }}" method="post" style="display: none;">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                  </form>
              </td>
            </tr>
            @endforeach
            </tbody>
          </table>
        @else 
          <p style="text-align:center">No records found</p>
        @endif
      </div>
    </div>
 
</x-app-layout>           