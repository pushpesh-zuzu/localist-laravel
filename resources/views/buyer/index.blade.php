<x-app-layout>
  @section('title', 'Quote Customers (Complete List)')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Quote Customers (Complete List)") }}</h4>
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
            <thead class="table-light">
              <tr>
                <th>S.No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Postcode</th>
                <th>Services</th>
                <th>Score</th>
                <!-- <th>Entry URL</th>
            <th>User IP</th> -->
                <th>Date</th>
                <th>Last Login</th>
                <th>Zoho Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {{-- Table data will be filled via AJAX --}}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="modal fade" id="showLoginLinkModal" tabindex="-1" aria-labelledby="showLoginLinkModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="showLoginLinkModalLabel">Lead Buyer Login Link</h5>
              <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Login Link</label>
                <div class="input-group">
                  <input type="text" id="login-link-input" class="form-control" readonly>
                  <button class="btn btn-success" id="copy-login-link">
                    Copy
                  </button>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">
                Close
              </button>
            </div>
          </div>
      </div>
    </div>


    @push('scripts')
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
      ajax: {
        url: '{{ route("buyer.index") }}',
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
          name: 'status'
        },
        {
          data: 'postcode',
          name: 'postcode',
          orderable: false,
          searchable: true
        },
        {
          data: 'services',
          name: 'services',
          title: 'Services',
          orderable: false,
          searchable: true
        },
        {
          data: 'score',
          name: 'score',
          orderable: false,
          searchable: true
        },
        // {
        //   data: 'entry_url',
        //   name: 'entry_url',
        //   orderable: false,
        //   searchable: true
        // },
        // {
        //   data: 'user_ip_address',
        //   name: 'user_ip_address',
        //   orderable: false,
        //   searchable: true
        // },
        {
          data: 'date',
          name: 'date',
          orderable: false,
          searchable: true
        },
        {
          data: 'last_login',
          name: 'last_login',
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
        }, {
          targets: 8,
          width: "200px"
        }, // column index 6 = services
      ],
      dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
      buttons: [
        @can('quotecustomers.complete-excelexport') {
          text: 'Export Excel',
          className: "btn btn-success btn-sm",
          action: function(e, dt, node, config) {
            let start = $('#from_date').val();
            let end = $('#to_date').val();
            let search = dt.search(); // Use dt instead of dataTable
            let type = 'customer-complete-list';
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
        @can('quotecustomers.complete-csvexport') {
          text: 'Export CSV',
          className: "btn btn-info btn-sm",
          action: function(e, dt, node, config) {
            let start = $('#from_date').val();
            let end = $('#to_date').val();
            let type = 'customer-complete-list';
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

<script>
      $(document).on("click", ".login-link", function() {
        
        let email = $(this).data("user-email");
        console.log(email);
        let baseUrl = $("#_url").val();
        $("#login-link-input").val('');
        $.ajax({
          url: baseUrl + "/api/users/get-user-login-link", // Route to get login link
          type: "POST",
          data:{
            email : email
          },
          success: function(res) {
            if (res.success) {

              var loginLinkModalE = document.getElementById("showLoginLinkModal");
              var loginLinkModalShow = new coreui.Modal(loginLinkModalE);
              loginLinkModalShow.show();

              $("#login-link-input").val(res.url);
            } else {
              alert(res.message || "Failed to fetch user credit.");
            }
          },
          error: function(xhr) {
            alert("Server error. Please try again.");
          },
          complete: function() {
            $("#loader").fadeOut();
          }
        });
      });

      $(document).on("click", "#copy-login-link", function () {
        let input = document.getElementById("login-link-input");
        input.select();
        input.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(input.value);

        $(this).text("Copied!");
        setTimeout(() => $(this).text("Copy"), 1500);
      });
    </script>
    @endpush

</x-app-layout>
