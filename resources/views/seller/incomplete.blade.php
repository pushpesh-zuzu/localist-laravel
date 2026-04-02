<x-app-layout>
  @section('title', 'Lead Buyers (Incomplete List)')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Lead Buyers (Incomplete List)") }}</h4>
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
    <div class="card border-1  rounded-4">
      <div class="card-body p-4">
        <form method="GET" action="{{ route('seller.incomplete') }}" class="row g-3 align-items-end mb-4">
          <div class="col-md-2">
            <label class="form-label  small custom-label ms-2">From Date</label>
            <input type="date" name="from_date"
              value="{{ request('from_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-2">
            <label class="form-label  small custom-label ms-2">To Date</label>
            <input type="date" name="to_date"
              value="{{ request('to_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-4 d-flex gap-2">
            <button type="submit"
              class="btn btn-primary px-3 rounded-pill shadow-sm">
              🔍 Filter
            </button>

            <a href="{{ route('seller.incomplete') }}"
              class="btn btn-light border px-3 rounded-pill shadow-sm">
              Reset
            </a>
          </div>
        </form>

        <hr class="my-4">
        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="dataTable">
            <thead>
              <tr>
                <th scope="col" width="20px;">S.No</th>
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
  </div>
</x-app-layout>

<script>
  $('#dataTable').DataTable({
    destroy: true,
    order: [],
    columnDefs: [{
      orderable: false,
      targets: [ 1, 2, 3, 4, 5, 6, 7]
    }],
    dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',


    buttons: [
      @can('leadbuyers.incomplete-excelexport') {
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
      @can('leadbuyers.incomplete-csvexport') {
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
    ],
    order: [
        [0, 'desc']
      ],
  });
</script>

<script src="{{ asset('coreui/js/common.js') }}"></script>