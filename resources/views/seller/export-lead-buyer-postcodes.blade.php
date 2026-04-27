<x-app-layout>
  @section('title', 'Export Lead Buyer Postcodes')

  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __('Export Lead Buyer Postcodes') }}</h4>
  </div>
  @include('layouts.alerts')
  <div class="card mb-4">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-bordered" id="dataTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Service Type</th>
              <th>Postcode</th>
              <th>Radius</th>
              <th>City</th>
              <th>Type</th>
              <th>Created Date</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

</x-app-layout>
<script>
  function loadDataTable() {

    if ($.fn.dataTable.isDataTable('#dataTable')) {
      table.clear().destroy();
    }


    var d7LeadRoute = "{{ route('export.leadbuyer.service.postcodes') }}";
    $("#dataTable").DataTable({
      destroy: true,
      responsive: true,
      processing: true,
      serverSide: true,
      autoWidth: false,
      ordering: false,
      order: [],
      ajax: d7LeadRoute,
      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          title: 'S.No',
          searchable: false,
          orderable: false,

        },

        {
          data: 'service_type',
          name: 'serviceCategory.name',
        },

        {
          data: 'postcode',
          name: 'postcode',
        },
        {
          data: 'miles',
          name: 'miles',
        },

        {
          data: 'city',
          name: 'city',
        },
        {
          data: 'type',
          name: 'type',
        },


        {
          data: 'created_at',
          name: 'created_at',
        },

      ],

      columnDefs: [{
          targets: 1,
          width: "200px",
        }, // Supplier Name
        {
          targets: 2,
          width: "220px"
        }, // Email
      ],

      dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
      buttons: [
        @can('leadbuyerpostcodes.excelexport') {
          text: 'Export Excel',
          className: "btn btn-success btn-sm",
          action: function(e, dt, node, config) {

            let search = dt.search(); // Use dt instead of dataTable

            let query = $.param({
              search: search
            });
            window.location.href = "{{ route('export.service.postcodes.excel') }}" + "?" + query;

            e.preventDefault();
          }
        },
        @endcan
        @can('leadbuyerpostcodes.csvexport') {
          text: 'Export CSV',
          className: "btn btn-info btn-sm",
          action: function(e, dt, node, config) {

            let search = dt.search(); // Use dt instead of dataTable

            let query = $.param({

              search: search
            });
            window.location.href = "{{ route('export.service.postcodes.csv') }}" + "?" + query;

            e.preventDefault();
          }
        }

        @endcan
      ],
      language: {
        emptyTable: "No suppliers found"
      }
    });

  }

  $(document).ready(function() {
    loadDataTable();
    $('#dataTable thead th').removeClass('sorting sorting_asc sorting_desc');
  });
</script>