<x-app-layout>
  <x-slot name="header">{{ __('Quote Customers (InComplete List)') }} </x-slot>
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
      <strong>{{ __('Quote Customers') }}</strong>
    </div>

    <div class="card-body">
      <div class="container mb-5">
        <form id="filterForm" class="row g-3 justify-content-center align-items-end">
          <div class="col-12 col-md-3">
            <label for="from_date" class="form-label">From Date</label>
            <input type="date" id="from_date" name="from_date" class="form-control" placeholder="dd-mm-yyyy">
          </div>

          <div class="col-12 col-md-3">
            <label for="to_date" class="form-label">To Date</label>
            <input type="date" id="to_date" name="to_date" class="form-control" placeholder="dd-mm-yyyy">
          </div>

          <div class="col-12 col-md-3 d-flex gap-2 mt-2 mt-md-0">
            <button type="button" id="filterBtn" class="btn btn-primary ">Filter</button>
            <button type="button" id="resetBtn" class="btn btn-secondary">Reset</button>
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-striped table-bordered" id="dataTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Postcode</th>
              <th>Services</th>
              {{-- <th>Score</th> --}}
               <!-- <th>Entry URL</th>
              <th>User IP</th> -->
              <th>Date</th>
              <th>Zoho Status</th>
              <th>Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

</x-app-layout>
<script>
  function loadDataTable() {
    let from_date = $('#from_date').val();
    let to_date = $('#to_date').val();

    if ($.fn.dataTable.isDataTable('#dataTable')) {

      table.clear().destroy();
    }

    table = $("#dataTable").DataTable({
      destroy: true,
      responsive: true,
      processing: true,
      serverSide: true,
      autoWidth: false,
      searchDelay: 500,
      ajax: {
        url: '{{ route("buyer.incompletelist") }}',
        data: function(d) {
          d.from_date = from_date;
          d.to_date = to_date;
        }
      },
      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          orderable: false,
          searchable: false
        },
        {
          data: 'name',
          name: 'name'
        },
        {
          data: 'email',
          name: 'email'
        },
        {
          data: 'phone',
          name: 'phone'
        },
        {
          data: 'status',
          name: 'status'
        },
        {
          data: 'zipcode',
          name: 'zipcode',
          orderable: false,
          searchable: true
        },
        {
          data: 'services',
          name: 'services',
          title: 'Services',
          orderable: false,
          searchable: true,

        },       
        {
          data: 'date',
          name: 'date',
          orderable: false,
          searchable: true
        },
        {
          data: 'zoho_status',
          name: 'zoho_status',
          orderable: false,
          searchable: true
        },

        

        {
          data: 'action',
          name: 'action',
          orderable: false,
          searchable: false
        },
      ],
      columnDefs: [{
          targets: 6,
          width: "250px"
        } // column index 6 = services
      ],
      dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
      buttons: [
         @can('quotecustomers.incom-excelexport')
        {
            text: 'Export Excel',
            className: "btn btn-success btn-sm",
            action: function(e, dt, node, config) {
                let start = $('#from_date').val();
                let end = $('#to_date').val();
                let search = dt.search(); // Use dt instead of dataTable
                let type = 'customer-incomplete-list';
                let query = $.param({ type: type, start_date: start, end_date: end, search: search });
                window.location.href = "{{ route('export.buyer.excel') }}" + "?" + query;
                
                e.preventDefault();
            }
        },    
         @endcan
        @can('quotecustomers.incom-csvexport')
         {
            text: 'Export CSV',
            className: "btn btn-info btn-sm",
            action: function(e, dt, node, config) {
                let start = $('#from_date').val();
                let end = $('#to_date').val();
                 let type = 'customer-incomplete-list';
                let search = dt.search(); // Use dt instead of dataTable
                
                let query = $.param({ type: type,start_date: start, end_date: end, search: search });
                window.location.href = "{{ route('export.buyer.csv') }}" + "?" + query;
                
                e.preventDefault();
            }
        }
         @endcan
      ],


      "language": {
        "emptyTable": "No records found"
      }
    });
  }

  $(document).ready(function() {
    loadDataTable();

    $('#filterBtn').on('click', function() {
      loadDataTable();
    });

    $('#resetBtn').on('click', function() {
      $('#from_date').val('');
      $('#to_date').val('');
      loadDataTable();
    });
  });
</script>

<script src="{{ asset('coreui/js/common.js') }}"></script>