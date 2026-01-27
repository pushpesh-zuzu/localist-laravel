<x-app-layout>
  <x-slot name="header">{{ __('Lead Buyers Login History') }} </x-slot>
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
      <strong>{{ __('Lead Buyers Login History') }}</strong>
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
      ajax: {
        url: '{{ route("seller.allloginhistorylist") }}',
        data: function(d) {
          d.from_date = $('#from_date').val();
          d.to_date = $('#to_date').val();
        }
      },
      columns: [{
          data: 'DT_RowIndex',
          orderable: false,
          searchable: false
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
          orderable: false,
          searchable: true
        },
        {
          data: 'last_device',
          name: 'last_device',
          orderable: false,
          searchable: true
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
         @can('leadbuyers.allloginhistory-excelexport')
        {
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
        @can('leadbuyers.allloginhistory-csvexport')
        {
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