<x-app-layout>
  @section('title', 'Purchase Invoice History')

  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __('Purchase Invoice History') }}</h4>
  </div>
  @include('layouts.alerts')
  <div class="card mb-4">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-bordered" id="dataTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Invoice Number</th>
              <th>Name</th>
              <th>Email</th>
              <th>Plan</th>
              <th>Amount</th>
              <th>Vat</th>
              <th>Total Amount</th>
              <th>Created Date</th>
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

    if ($.fn.dataTable.isDataTable('#dataTable')) {
      table.clear().destroy();
    }


    var d7LeadSupplierRoute = "{{ route('purchase.invoice.history') }}";
    $("#dataTable").DataTable({
      destroy: true,
      responsive: true,
      processing: true,
      serverSide: true,
      autoWidth: false,
      ordering: false,
      order: [],
      ajax: d7LeadSupplierRoute,
      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          title: 'S.No',
          searchable: false, orderable: false ,

        },
        {
          data: 'invoice_number',
          name: 'invoice_number',
        },
        {
          data: 'user_name',
          name: 'user.name',
        },
        {
          data: 'user_email',
          name: 'user.email',
        },
        {
          data: 'details',
          name: 'details',
        },
        {
          data: 'amount',
          name: 'amount',
        },

        {
          data: 'vat',
          name: 'vat',
        },

        {
          data: 'total_amount',
          name: 'total_amount',
        },


        {
          data: 'created_at',
          name: 'created_at',
        },
        {
          data: 'action',
          name: 'action',
          orderable: false,
          searchable: false
        }
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