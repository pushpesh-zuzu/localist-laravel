<x-app-layout>
   
    <x-slot name="header">{{ 'Services' . (!empty($user) ? " ($user)" : '') }}</x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Services') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-bordered" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Title</th>
            <th scope="col">Description</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td >{{ $aRow->title }}</td>
            <td >{!! $aRow->description !!}</td>
            
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