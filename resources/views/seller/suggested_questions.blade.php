<x-app-layout>
   
    <x-slot name="header">{{ 'Suggested Questions' . (!empty($user) ? " ($user)" : '') }}</x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Questions List') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-bordered" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Service</th>
            <th scope="col">Type</th>
            <th scope="col">Answer Type</th>
            <th scope="col">Question</th>
            <th scope="col">Answer</th>
            <th scope="col">Reason</th>
          </tr>
          </thead>
          <tbody>
         

          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ optional($aRow->services)['name'] ?? '' }}</td>
            <td>{{ $aRow->type ?? '' }}</td>
            <td>{{ $aRow->answer_type ?? '' }}</td>
            <td>{{ $aRow->question ?? '' }}</td>
            <td>{{ $aRow->answer ?? '' }}</td>
            <td>{{ $aRow->answer ?? '' }}</td>
            
          </tr>
          @endforeach
          </tbody>
        </table>
        @else 
            No records found
        @endif
      </div>
    </div>
 
</x-app-layout>           