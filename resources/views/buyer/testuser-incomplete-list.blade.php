<x-app-layout>

  @section('title', 'Quote Customers (Test Incomplete List)')

  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Quote Customers (Test Incomplete List)") }}</h4>
  </div>
  <div class="container-fluid py-2 px-0">
    @if(session('success'))
    <div class="alert alert-success shadow-sm border-0 rounded-3">
      {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger shadow-sm border-0 rounded-3">
      {{ session('error') }}
    </div>
    @endif

    {{-- Card --}}
    <div class="card border-1  rounded-4">
      <div class="card-body p-4">
        {{-- Filter Section --}}
        <form method="GET" action="{{ route('buyer.testuserincompletelist') }}" class="row g-3 align-items-end mb-4">

          <div class="col-md-2">
            <label class="form-label small custom-label ms-2" style="color: black;">From Date</label>
            <input type="date" name="from_date"
              value="{{ request('from_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-2">
            <label class="form-label small custom-label ms-2">To Date</label>
            <input type="date" name="to_date"
              value="{{ request('to_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-4 d-flex gap-2">
            <button type="submit"
              class="btn btn-primary px-3 rounded-pill shadow-sm">
              🔍 Filter
            </button>

            <a href="{{ route('buyer.testuserincompletelist') }}"
              class="btn btn-light border px-3 rounded-pill shadow-sm">
              Reset
            </a>
          </div>
        </form>
        <hr class="my-4">

        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="dataTable">
            <thead>
              <tr>
                <th scope="col" width="20px;">S.No</th>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th>Zoho Status</th>
                <th scope="col">Status</th>
                <th scope="col">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($testUsers as $aKey => $aRow)
              <tr id="userid{{ $aRow->id }}">
                <th scope="row">{{ $aKey+1 }}</th>
                <td>{{ $aRow->name }}</td>
                <td>{{ $aRow->email }}</td>
                <td>{{ $aRow->zoho_record_id  ? 'Inserted'     : 'Not-inserted'; }}</td>
                <td>Test</td>
                <td>
                  @can('quotecustomers.quote_test_incomplete_sendtozoho')
                  @if(!$aRow->zoho_record_id && !empty($aRow->name) && !empty($aRow->email))
                  <a href="{{ route('zoho.send', ['type' => 'abandoned', 'id' => $aRow->id]) }}" class="text-primary text-decoration-none">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Send to Zoho |
                  </a>
                  @endif
                  @endcan
                  @can('quotecustomers.quote_test_incomplete_view')
                  <a href="{{ route('buyer.show.custom', ['type' => 'abandoned', 'id' => $aRow->id]) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
                  @endcan


                  @can('quotecustomers.quote_test_incomplete_delete')
                  <a href="javascript:void(0)"
                    onclick="deleteUser('{{ $aRow->id }}', 'abandoned', '')"
                    class="text text-danger"
                    title="Delete">
                    <i class="fa-solid fa-trash"></i>
                  </a>
                  @endcan
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
</x-app-layout>


<script>
  $('#dataTable').DataTable({
    destroy: true,
    columnDefs: [{
      orderable: false,
      targets: [4, 5]
    }],
    dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
    buttons: [
      @can('quotecustomers.test_incomplete_excel') {
        text: 'Export Excel',
        className: "btn btn-success btn-sm",
        action: function(e, dt, node, config) {
          let start = $('#from_date').val();
          let end = $('#to_date').val();
          let search = dt.search(); // Use dt instead of dataTable
          let type = 'customer-testincomplete-list';
          let query = $.param({
            type: type,
            start_date: start,
            end_date: end,
            search: search
          });
          window.location.href = "{{ route('export.buyer.excel') }}" + "?" + query;

          e.preventDefault();
        }
      },
      @endcan
      @can('quotecustomers.test_incomplete_csv') {
        text: 'Export CSV',
        className: "btn btn-info btn-sm",
        action: function(e, dt, node, config) {
          let start = $('#from_date').val();
          let end = $('#to_date').val();
          let type = 'customer-testincomplete-list';
          let search = dt.search(); // Use dt instead of dataTable

          let query = $.param({
            type: type,
            start_date: start,
            end_date: end,
            search: search
          });
          window.location.href = "{{ route('export.buyer.csv') }}" + "?" + query;

          e.preventDefault();
        }
      },
      @endcan
    ],
    order: [
        [0, 'desc']
      ],
    "language": {
      "emptyTable": "No records found"
    }
  });
</script>

<script src="{{ asset('coreui/js/common.js') }}"></script>