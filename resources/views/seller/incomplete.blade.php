<x-app-layout>
  <x-slot name="header">{{ __('Lead Buyers (Incomplete List)') }} </x-slot>
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
      <strong>{{ __('Lead Buyers') }}</strong>
    </div>
    <div class="card-body">

      <div class="container mb-5">
        <form method="GET" action="{{ route('seller.incomplete') }}" class="row g-3 justify-content-center align-items-end mb-4">
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
            <a href="{{ route('seller.incomplete') }}" class="btn btn-secondary">Reset</a>
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
              <th scope="col">Total Credit</th>              
              <th scope="col">Registration Status</th>
              <th scope="col">Status</th>
               <th>Zoho Status</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($aRows as $aKey => $aRow)
            <tr id="userid{{ $aRow->id }}">
              <th scope="row">{{ $aKey+1 }}</th>
              <td>{{ $aRow->name }}</td>
              <td>{{ $aRow->email }}</td>
              <td class="text text-center">{{ $aRow->total_credit  }}</td>
             
              <td>{{ $aRow->form_status == 1 ? 'Complete' : 'InComplete' }}</td>
              
              <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
                <td>{{ $aRow->zoho_record_id  ? 'Inserted'     : 'Not-inserted'; }}</td>
              <td>
               @can('leadbuyers.incomplete-sendtozoho')
                @if(!$aRow->zoho_record_id && !empty($aRow->name) && !empty($aRow->email))
                    <a href="{{ route('zoho.seller.send', ['type' => 'abandoned', 'id' => $aRow->id]) }}" class="text-primary text-decoration-none">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Send to Zoho |
                    </a>
                @endif
               @endcan
              
                @can('leadbuyers.incomplete-view-details')
                <a href="{{ route('seller.show.custom',['type' => 'abandoned', 'id' => $aRow->id]) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
                @endcan
                @can('leadbuyers.incomplete-delete')
                 <a href="javascript:void(0)"
                  onclick="deleteUser('{{ $aRow->id }}', 'abandoned', '')"
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
       @can('leadbuyers.incomplete-excelexport')
      {
        extend: 'excelHtml5',
        text: 'Export Excel',
        title: 'Lead Buyers - Incomplete List',
        className: "buttons-excel btn btn-success btn-sm",
        exportOptions: {
          columns: ':not(:eq(7))', // Exclude "Action" column (7th column)
          modifier: {
            order: 'index',
            page: 'all',
            search: 'applied'
          }
        }
      },
       @endcan
        @can('leadbuyers.incomplete-csvexport')
      {
        extend: 'csvHtml5',
        text: 'Export CSV',
        title: 'Lead Buyers - Incomplete List',
        className: "buttons-csv btn btn-info btn-sm",
        exportOptions: {
          columns: ':not(:eq(7))',
          modifier: {
            order: 'index',
            page: 'all',
            search: 'applied'
          }
        }
      },
       @endcan
    ]
  });
</script>

<script src="{{ asset('coreui/js/common.js') }}"></script>