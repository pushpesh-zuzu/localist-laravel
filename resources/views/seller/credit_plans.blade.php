<x-app-layout>
    <x-slot name="header">{{ 'Credit Plan History' . (!empty($user) ? " ($user)" : '') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Plans List') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-bordered" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Plan</th>
            <th scope="col">Purchase Date</th>
            <th scope="col">Price</th>
            <th scope="col">Total Credits</th>
            <th scope="col">Credits</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td >{{ $aRow->plans->name }}</td>
            <td class="text-center">{{ $aRow->purchase_date }}</td>
            <td class="text-center">{{ $aRow->price }}</td>
            <td class="text-center">{{ $aRow->users->total_credit }}</td>
            <td class="text-center">{{ $aRow->credits }}</td>
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