<x-app-layout>  
   @section('title', 'Quote Customers (InComplete List)')
 
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Quote Customers (InComplete List)") }}</h4>
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
        <form id="filterForm" class="row g-3 align-items-end mb-4">

          <div class="col-md-2">
            <label class="form-label small custom-label ms-2" style="color: black;">From Date</label>
            <input type="date" id="from_date" name="from_date"
              value="{{ request('from_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-2">
            <label class="form-label small custom-label ms-2">To Date</label>
            <input type="date" id="to_date" name="to_date"
              value="{{ request('to_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-4 d-flex gap-2">
            <button type="button" id="filterBtn"
              class="btn btn-primary px-3 rounded-pill shadow-sm">
              🔍 Filter
            </button>
            <button type="button" id="resetBtn" class="btn btn-light border px-3 rounded-pill shadow-sm">Reset</button>
          </div>
        </form>
        <hr class="my-4">
        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="dataTable">
            <thead>
              <tr>
                <th>S.No</th>
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
          data: null,
          name: 'id',
          orderable: true,
          searchable: false,
          render: function(data, type, row, meta) {
            let table = $('#dataTable').DataTable();
            let info = table.page.info();          
            let order = table.order();
            let dir = order[0][1]; 

            if (type === 'sort' || type === 'type') {
              return row.id;
            }
            
            if (dir === 'asc') {
              return meta.row + info.start + 1;
            }
          
            return info.recordsTotal - (meta.row + info.start);
          }
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
          name: 'status',
          orderable: false,
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
        @can('quotecustomers.incom-excelexport') {
          text: 'Export Excel',
          className: "btn btn-success btn-sm",
          action: function(e, dt, node, config) {
            let start = $('#from_date').val();
            let end = $('#to_date').val();
            let search = dt.search(); // Use dt instead of dataTable
            let type = 'customer-incomplete-list';
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
        @can('quotecustomers.incom-csvexport') {
          text: 'Export CSV',
          className: "btn btn-info btn-sm",
          action: function(e, dt, node, config) {
            let start = $('#from_date').val();
            let end = $('#to_date').val();
            let type = 'customer-incomplete-list';
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
        }
        @endcan
      ],
 order: [
        [0, 'desc']
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