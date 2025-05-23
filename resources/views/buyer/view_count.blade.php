<x-app-layout>
    <x-slot name="header">{{ 'View Counts' . (!empty($user) ? " ($user)" : '') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('View Counts') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-bordered" id="dataTable">
            <thead>
                <tr>
                <th>#</th>
                <th>seller</th>
                <th>Lead</th>
                <th>Ip Address</th>
                <th class="text-center">View Count</th>
                <th class="text-center">Random Count</th>
                <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aRows as $index => $row)
                <tr>
                    <th>{{ $index + 1 }}</th>
                    <td>{{ $row['seller'] }}</td>
                    <td>{{ $row['leadname'] }}</td>
                    <td>{{ $row['ip_address'] }}</td>
                    <td class="text-center">{{ $row['visitors_count'] }}</td>
                    <td class="text-center">{{ $row['random_count'] }}</td>
                    <td>{{ $row['date'] }}</td>
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