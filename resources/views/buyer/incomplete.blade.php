<x-app-layout>
  <x-slot name="header">{{ __('Quote Customers (InComplete List)') }} </x-slot>

  <div class="card mb-4">
    <div class="card-header">
      <strong>{{ __('Quote Customers') }}</strong>
    </div>

    <div class="card-body">
      <div class="container my-4">
        <div class="d-flex justify-content-center">
          <form method="GET" action="{{ route('buyer.incompletelist') }}" class="w-100 w-md-75">
            <div class="row g-3 align-items-end justify-content-center">
              <div class="col-12 col-md-3">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="form-control">
              </div>
              <div class="col-12 col-md-3">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="form-control">
              </div>
              <div class="col-12 col-md-3 text-center text-md-start">
                <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0">Filter</button>
                <a href="{{ route('buyer.incompletelist') }}" class="btn btn-secondary">Reset</a>
              </div>
            </div>
          </form>
        </div>
      </div>
      @if(count($aRows) > 0)
      <table class="table table-striped" id="dataTable">
        <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Status</th>
            <th scope="col">Postcode</th>
            <th scope="col">Phone</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->name }}</td>
            <td>{{ $aRow->email }}</td>
            <td>Incomplete</td>
            <td>{{ $aRow->zipcode }}</td>
            <td>{{ $aRow->phone }}</td>
            <!-- <td>{{ $aRow->user_type == 2 ? 'Buyer' : 'Seller, Buyer' }}</td> -->
            <!-- <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td> -->
            <td>
              <a href="{{ route('buyer.show.custom', ['type' => 'abandoned', 'id' => $aRow->id]) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
              {{-- <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i></i>
                </a>
                <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('buyer.destroy',$aRow->id) }}" method="post" style="display: none;">
              {{ method_field('DELETE') }}
              {{ csrf_field() }}
              <input type="hidden" name="type" value="abandoned">
              </form> --}}

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

<!-- <script>
  $('#dataTable').DataTable({
    destroy: true,
    dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
    buttons: [{
        extend: 'excelHtml5',
        text: 'Export Excel',
        title: 'Quote Customers Incomplete List',
        className: "buttons-excel btn btn-success btn-sm",
        exportOptions: {
          columns: ':not(:eq(6))', // Exclude "Action" column (7th column)
          modifier: {
            order: 'index',
            page: 'all',
            search: 'none'
          }
        }
      },
      {
        extend: 'csvHtml5',
        text: 'Export CSV',
        title: 'Quote Customers Incomplete List',
        className: "buttons-csv btn btn-info btn-sm",
        exportOptions: {
          columns: ':not(:eq(6))',
          modifier: {
            order: 'index',
            page: 'all',
            search: 'none'
          }
        }
      },
    ]
  });
</script> -->