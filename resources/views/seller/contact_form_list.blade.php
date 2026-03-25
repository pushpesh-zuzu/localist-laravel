<x-app-layout>
  @section('title', 'Lead Buyers (Contact Form List)')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Lead Buyers (Contact Form List)") }}</h4>
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
        <form method="GET" action="{{ route('seller.contact_form') }}" class="row g-3 align-items-end mb-4">
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
            <a href="{{ route('seller.contact_form') }}"
              class="btn btn-light border px-3 rounded-pill shadow-sm">
              Reset
            </a>
          </div>
        </form>

        {{-- Divider --}}
        <hr class="my-4">

        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="dataTable">
            <thead>
              <tr>
                <th scope="col" width="20px;">S.No</th>
                <th scope="col">Name</th>
                <th scope="col">Phone</th>
                <th scope="col">Type</th>
                <th scope="col">Message</th>
                <th scope="col">Status</th>
                <th scope="col">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($aRows as $aKey => $aRow)
              <tr>
                <th scope="row">{{ $aKey+1 }}</th>
                <td>{{ $aRow->full_name }}</td>
                <td>{{ $aRow->phone }}</td>
                <td class="text text-center">{{ $aRow->user_type == 1 ? 'Customer' : ($aRow->user_type == 2 ? 'Professional' : 'Unknown') }}</td>
                <td class="text text-center">{{ $aRow->message }}</td>
                <td style="color: {{ $aRow->status == 1 ? 'green' : 'red' }}">
                  {{ $aRow->status == 1 ? 'Viewed' : 'Not Viewed' }}
                </td>
                <td>
                  @can('leadbuyerscontact.view-details')
                  <a href="{{ route('seller.show_contact_form',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
                  @endcan
                  @can('leadbuyerscontact.contact-delete')
                  <form method="POST" action="{{ route('contact.delete', $aRow->id) }}" style="display:inline-block;"
                    onsubmit="return confirm('Are you sure you want to delete this record?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text text-danger" data-coreui-toggle="tooltip" data-coreui-placement="top" title="Delete">
                      <i class="bi bi-trash text-danger"></i>
                    </button>
                  </form>
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
    order: [],

    columnDefs: [{
      orderable: false,
      targets: [0, 1, 2, 3, 4, 5]
    }],
    dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
    buttons: [
      @can('leadbuyers.contact-excelexport') {
        extend: 'excelHtml5',
        text: 'Export Excel',
        title: 'Lead Buyers - Contact Form List',
        className: "buttons-excel btn btn-success btn-sm",
        exportOptions: {
          columns: ':not(:eq(6))', // Exclude "Action" column (7th column)
          modifier: {
            order: 'index',
            page: 'all',
            search: 'applied'
          }
        }
      },
      @endcan
      @can('leadbuyers.contact-csvexport') {
        extend: 'csvHtml5',
        text: 'Export CSV',
        title: 'Lead Buyers - Contact Form List',
        className: "buttons-csv btn btn-info btn-sm",
        exportOptions: {
          columns: ':not(:eq(6))',
          modifier: {
            order: 'index',
            page: 'all',
            search: 'applied'
          }
        }
      },
      @endcan
    ],
    "language": {
      "emptyTable": "No records found"
    }
  });
</script>