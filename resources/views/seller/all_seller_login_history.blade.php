<x-app-layout>
  @section('title', 'Lead Buyers Login History')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Lead Buyers Login History") }}</h4>
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
                <th style="text-align:center;">IP Address (Last Login)</th>
                <th style="text-align:center;">Logged-in Device (Last Login)</th>
                <th style="text-align:center;">First Login</th>
                <th style="text-align:center;">Last Login</th>
                <th style="text-align:center;">Total Logins</th>
              </tr>
            </thead>
          </table>
        </div>
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
      processing: true,
      serverSide: true,
      responsive: true,
      autoWidth: true,
      order: [],

      columnDefs: [{
        orderable: false,
        targets: [0, 1, 2, 3, 4, 5, 6, 7]
      }],
      ajax: {
        url: '{{ route("seller.allloginhistorylist") }}',
        data: function(d) {
          d.from_date = $('#from_date').val();
          d.to_date = $('#to_date').val();
        }
      },
      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          searchable: false,
          orderable: false,
        },
        {
          data: 'name',
          name: 'users.name'
        },
        {
          data: 'email',
          name: 'users.email'
        },
        {
          data: 'last_ip',
          name: 'last_ip',
        },
        {
          data: 'last_device',
          name: 'last_device',
        },
        {
          data: 'first_login_time',
          name: 'first_login_time'
        },
        {
          data: 'last_login_time',
          name: 'last_login_time'
        },
        {
          data: 'total_logins_count',
          name: 'total_logins_count'
        }
      ],
      dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
      buttons: [
        @can('leadbuyers.allloginhistory-excelexport') {
          text: 'Export Excel',
          className: "btn btn-success btn-sm",
          action: function(e, dt, node, config) {
            let start = $('#from_date').val();
            let end = $('#to_date').val();
            let search = dt.search(); // Use dt instead of dataTable            
            let userType = '1';
            let query = $.param({
              userType: userType,
              start_date: start,
              end_date: end,
              search: search
            });
            window.location.href = "{{ route('export.login.history.excel') }}" + "?" + query;
            e.preventDefault();
          }
        },
        @endcan
        @can('leadbuyers.allloginhistory-csvexport') {
          text: 'Export CSV',
          className: "btn btn-info btn-sm",
          action: function(e, dt, node, config) {
            let start = $('#from_date').val();
            let end = $('#to_date').val();
            let userType = '1';
            let search = dt.search(); // Use dt instead of dataTable
            let query = $.param({
              userType: userType,
              start_date: start,
              end_date: end,
              search: search
            });
            window.location.href = "{{ route('export.login.history.csv') }}" + "?" + query;
            e.preventDefault();
          }
        }
        @endcan

      ],
      columnDefs: [{
        orderable: false,
        targets: [0, 1, 2, 3, 4, 5, 6, 7]
      }],

      language: {
        emptyTable: "No login records found"
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