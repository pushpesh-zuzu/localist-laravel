<x-app-layout>
    <x-slot name="header">{{ __('AutoBid') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('AutoBid') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-bordered" id="dataTable">
            <thead>
                <tr>
                <th>#</th>
                <th>Name</th>
                <th>Services</th>
                <th class="text-center">Postcode</th>
                <th class="text-center">Credit Scores</th>
                <th>Dates</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aRows as $index => $row)
                <tr>
                    <th>{{ $index + 1 }}</th>
                    <td>{{ $row['buyer_name'] }}</td>
                    
                    {{-- Combine all related lead data under each customer --}}
                    <td>
                    @foreach($row['leads'] as $lead)
                        <div>{{ $lead->service_name }}</div>
                    @endforeach
                    </td>
                    <td class="text-center">
                    @foreach($row['leads'] as $lead)
                        <div>{{ $lead->postcode }}</div>
                    @endforeach
                    </td>
                    <td class="text-center">
                    @foreach($row['leads'] as $lead)
                        <div>{{ $lead->credit_score }}</div>
                    @endforeach
                    </td>
                    <td>
                    @foreach($row['leads'] as $lead)
                        <div>{{ $lead->created_at->format('d-m-Y') }}</div>
                    @endforeach
                    </td>
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