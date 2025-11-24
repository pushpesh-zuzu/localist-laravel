<x-app-layout>
  <x-slot name="header">{{ __('Quote Customers (Test Complete List)') }} </x-slot>
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
    </div>
   @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
    </div>
   @endif
  <div class="card mb-4">
    <div class="card-header">
      <strong>{{ __('Quote Test Customers') }}</strong>
    </div>

   
    <div class="card-body">

      <div class="container mb-5">
        <form method="GET" action="{{ route('buyer.testusercompletelist') }}" class="row g-3 justify-content-center align-items-end">
          <div class="col-12 col-md-3">
            <label for="from_date" class="form-label">From Date</label>
            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="dd-mm-yyyy">
          </div>

          <div class="col-12 col-md-3">
            <label for="to_date" class="form-label">To Date</label>
            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="dd-mm-yyyy">
          </div>

          <div class="col-12 col-md-3 d-flex gap-2 mt-2 mt-md-0">
            <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0">Filter</button>
            <a href="{{ route('buyer.testusercompletelist') }}" class="btn btn-secondary">Reset</a>
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
              <!-- <th scope="col">Entry URL</th>
              <th scope="col">User IP</th> -->
              <th scope="col">Last Login</th>
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
               
              <!-- <td style="word-break: break-all; max-width: 200px;">
                {{ $aRow->entry_url ?? '' }}
              </td>
              <td>{{ $aRow->user_ip_address ?? '' }}</td> -->

              <td>
                {{ $aRow->lastLogin?->login_at
            ? \Carbon\Carbon::parse($aRow->lastLogin->login_at)->format('m/d/Y h:i A')
            : '' }}
              </td>
               <td>{{ $aRow->zoho_record_id  ? 'Inserted'     : 'Not-inserted'; }}</td>
              <td>Test</td>
         
              <td>

                @can('quotecustomers.test-complete-sendtozoho')
                      @if(!$aRow->zoho_record_id && !empty($aRow->name) && !empty($aRow->email))
                          <a href="{{ route('zoho.send', ['type' => 'complete', 'id' => $aRow->id]) }}" class="text-primary text-decoration-none">
                              <i class="fa-solid fa-cloud-arrow-up"></i> Send to Zoho |
                          </a>
                      @endif
               @endcan

                @can('quotecustomers.test_complete_bids')
                <a href="{{ route('buyer.buyerBids',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-chess-pawn" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Bids"></i></a>
                @endcan
                @can('quotecustomers.test-complete-unique-visitors')
                <a href="{{ route('buyer.viewCount',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-users" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Unique Visitors"></i></a>
                @endcan
                @can('quotecustomers.test_complete_loginhistory')
                <a href="{{ route('buyer.buyerLogin',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-history" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Login History"></i></a>
                @endcan
                @can('quotecustomers.test-complete-view-details')
                <a href="{{ route('buyer.show.custom', ['type' => 'complete', 'id' => $aRow->id]) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
                @endcan
                @can('quotecustomers.test_complete_delete')
                <a href="javascript:void(0)"
                  onclick="deleteUser('{{ $aRow->id }}', 'complete', '')"
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
    dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
    buttons: [
      @can('quotecustomers.test_complete_excel') {
        text: 'Export Excel',
        className: "btn btn-success btn-sm",
        action: function(e, dt, node, config) {
          let start = $('#from_date').val();
          let end = $('#to_date').val();
          let search = dt.search(); // Use dt instead of dataTable
          let type = 'customer-testcomplete-list';
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
      @can('quotecustomers.test_complete_csv') {
        text: 'Export CSV',
        className: "btn btn-info btn-sm",
        action: function(e, dt, node, config) {
          let start = $('#from_date').val();
          let end = $('#to_date').val();
          let type = 'customer-testcomplete-list';
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
    "language": {
      "emptyTable": "No records found"
    }
  });
</script>

<script src="{{ asset('coreui/js/common.js') }}"></script>