<x-app-layout>
  <x-slot name="header">{{ __('Quote Customers (InComplete List)') }} </x-slot>

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
              <th>Score</th>
               <!-- <th>Entry URL</th>
              <th>User IP</th> -->
              <th>Date</th>
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
          searchable: true,

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
      buttons: [{
          extend: 'excelHtml5',
          text: 'Export Excel',
          title: 'Quote Customers - Incomplete List',
          className: "buttons-excel btn btn-success btn-sm",
          exportOptions: {
            columns: ':not(:eq(9))', // Exclude "Action" column (7th column)
            modifier: {
              order: 'index',
              page: 'all',
              search: 'applied'
            }

          },
          action: function(e, dt, button, config) {
            var self = this;
            var oldStart = dt.settings()[0]._iDisplayStart;

            // Pre-fetch all data
            dt.one('preXhr', function(e, s, data) {
              data.start = 0;
              data.length = -1; // tell server to return ALL rows
            });

            dt.one('xhr', function(e, s, json) {
              var oldData = dt.rows().data();
              dt.clear();
              dt.rows.add(json.data).draw();

              // Call default excelHtml5 action
              $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);

              // Restore old page
              dt.clear();
              dt.rows.add(oldData).draw();
              dt.settings()[0]._iDisplayStart = oldStart;
              dt.draw(false);
            });

            dt.ajax.reload();
          }
        },
        {
          extend: 'csvHtml5',
          text: 'Export CSV',
          title: 'Quote Customers - Incomplete List',
          className: "buttons-csv btn btn-info btn-sm",
          exportOptions: {
            columns: ':not(:eq(9))',
            modifier: {
              order: 'index',
              page: 'all',
              search: 'applied'
            },
            format: {
              body: function(data, row, column, node) {
                if (typeof data === 'string') {
                  return data.replace(/<br\s*\/?>/gi, "\n").replace(/<\/?[^>]+(>|$)/g, "");
                }
                return data;
              }
            }
          },
          action: function(e, dt, button, config) {
            var self = this;
            var oldStart = dt.settings()[0]._iDisplayStart;

            dt.one('preXhr', function(e, s, data) {
              data.start = 0;
              data.length = -1;
            });

            dt.one('xhr', function(e, s, json) {
              var oldData = dt.rows().data();
              dt.clear();
              dt.rows.add(json.data).draw();

              $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config);

              dt.clear();
              dt.rows.add(oldData).draw();
              dt.settings()[0]._iDisplayStart = oldStart;
              dt.draw(false);
            });

            dt.ajax.reload();
          }
        },
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