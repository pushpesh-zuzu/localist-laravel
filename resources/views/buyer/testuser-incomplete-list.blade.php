<x-app-layout>
  <x-slot name="header">{{ __('Quote Customers (Test Incomplete List)') }} </x-slot>

  <div class="card mb-4">
    <div class="card-header">
      <strong>{{ __('Quote Test Customers') }}</strong>
    </div>

    <div class="card-body">
<div class="container mb-5">
        <form method="GET" action="{{ route('buyer.testuserincompletelist') }}" class="row g-3 justify-content-center align-items-end">
          <div class="col-12 col-md-3">
            <label for="from_date" class="form-label">From Date</label>
            <input type="date" id="from_date" name="from_date" class="form-control" placeholder="dd-mm-yyyy">
          </div>

          <div class="col-12 col-md-3">
            <label for="to_date" class="form-label">To Date</label>
            <input type="date" id="to_date" name="to_date" class="form-control" placeholder="dd-mm-yyyy">
          </div>

          <div class="col-12 col-md-3 d-flex gap-2 mt-2 mt-md-0">
            <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0">Filter</button>
            <a href="{{ route('buyer.testuserincompletelist') }}" class="btn btn-secondary">Reset</a>
          </div>
        </form>
      </div>
      
    <div class="table-responsive">
        <table class="table table-striped table-bordered" id="dataTable">
        <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($testUsers as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->name }}</td>
            <td>{{ $aRow->email }}</td>
            <td>Test</td>           
          </tr>
          @endforeach
        </tbody>
      </table>
     </div>
  </div>

</x-app-layout>


<script>
  $('#dataTable').DataTable({
    destroy: true,
    dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
    buttons: [{
        extend: 'excelHtml5',
        text: 'Export Excel',
        title: 'Quote Customers - Test Incomplete List',
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
        title: 'Quote Customers - Test Incomplete List',
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
    ],
    "language": {
      "emptyTable": "No records found"
    }
  });
</script>