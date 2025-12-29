<x-app-layout>
  <x-slot name="header">{{ __('D7 Lead Suppliers') }} </x-slot>
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
      <strong>{{ __('D7 Lead Suppliers') }}</strong>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-bordered" id="dataTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Category</th>
              <th>Service</th>
              <th>Supplier Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Address</th>
              <th>Region</th>
              <th>ZIP Code</th>
              <!-- <th>Country</th> -->
              <th>Website</th>
              <!-- <th>Google Rating</th>
              <th>Google Reviews</th> -->
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


    var d7LeadSupplierRoute = "{{ route('d7LeadSupplierList') }}";
    $("#dataTable").DataTable({
      destroy: true,
      responsive: true,
      processing: true,
      serverSide: true,
      autoWidth: false,
      ajax: d7LeadSupplierRoute,
      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          title: '#',
          orderable: false,
          searchable: false
        },
        {
          data: 'category',
          name: 'category',
        },
         {
          data: 'lead_service',
          name: 'lead_service',
        },
        {
          data: 'name',
          name: 'name',
        },
        {
          data: 'phone',
          name: 'phone',
        },

        {
          data: 'email',
          name: 'email',
        },

        {
          data: 'address1',
          name: 'address1',
        },
        {
          data: 'region',
          name: 'region',
        },
        {
          data: 'zip',
          name: 'zip',
        },

        {
          data: 'website',
          name: 'website',
        },

        {
          data: 'created_at',
          name: 'created_at',
        }
      ],

      columnDefs: [{
          targets: 1,
          width: "200px"
        }, // Supplier Name
        {
          targets: 2,
          width: "220px"
        }, // Email
      ],

      dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',

      buttons: [
        @can('d7leadsuppliers.excelexport') {
          text: 'Export Excel',
          className: "btn btn-success btn-sm",
          action: function(e, dt) {
            window.location.href = "{{ route('d7-lead-supplier.excel') }}";
            e.preventDefault();
          }
        },
        @endcan

        @can('d7leadsuppliers.csvexport') {
          text: 'Export CSV',
          className: "btn btn-info btn-sm",
          action: function(e, dt) {
            window.location.href = "{{ route('d7-lead-supplier.csv') }}";
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


  });
</script>