<x-app-layout>
    <x-slot name="header">{{ __('Quote Customers (Contact Form List)') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Quote Customers') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-striped" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Name</th>
            <th scope="col">Phone</th>
            <th scope="col">Type</th>
            <th scope="col">Message</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->full_name }}</td>
            <td>{{ $aRow->phone }}</td>
            <td class="text text-center">{{ $aRow->user_type }}</td>
            <td class="text text-center">{{ $aRow->message }}</td>
            <td style="color: {{ $aRow->status == 1 ? 'green' : 'red' }}">
                {{ $aRow->status == 1 ? 'Viewed' : 'Not Viewed' }}
            </td>

            <td>
                <a href="{{ route('buyer.show_contact_form',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
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
