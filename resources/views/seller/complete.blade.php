<x-app-layout>
  <x-slot name="header">{{ __('Lead Buyers (Complete List)') }} </x-slot>

  <div class="card mb-4">
    <div class="card-header">
      <strong>{{ __('Lead Buyers') }}</strong>
    </div>
    <div class="card-body">
      <div class="container mb-5">
        <form method="GET" action="{{ route('seller.complete') }}" class="row g-3 justify-content-center align-items-end">
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
            <a href="{{ route('seller.complete') }}" class="btn btn-secondary">Reset</a>
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
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($aRows as $aKey => $aRow)
            <tr id="userid{{ $aRow->id }}">
              <th scope="row">{{ $aKey+1 }}</th>
              <td>{{ $aRow->name }}</td>
              <td>{{ $aRow->email }}</td>
              <td class="text text-center">{{ $aRow->total_credit }}</td>
              <td>{{ $aRow->form_status == 1 ? 'Complete' : 'InComplete' }}</td>
              <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
              <td>

               <!-- <a href="javascript:void(0)"
                  class="text text-success view-credit"
                  data-user-id="{{ $aRow->id }}"
                  title="Add Credit">
                  <i class="bi bi-plus-circle"></i>
                </a> -->
                <a href="{{ route('seller.sellerBids',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-chess-pawn" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Bids"></i></a>
                <a href="{{ route('seller.sellerLogin',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-history" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Login History"></i></a>
                <a href="{{ route('seller.creditPlans',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Credit Plans"><i class="bi bi-list-task nav-icon"></i></a>
                <a href="{{ route('seller.suggestedQuestions',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Suggested Questions"><i class="bi bi-question-circle nav-icon"></i></a>
                <a href="{{ route('seller.services',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Services"><i class="bi bi-person-lines-fill"></i></a>
                <a href="{{ route('seller.show.custom',['type' => 'complete', 'id' => $aRow->id]) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
                <a href="javascript:void(0)"
                  onclick="deleteUser('{{ $aRow->id }}', 'complete', '')"
                  class="text text-danger"
                  title="Delete">
                  <i class="fa-solid fa-trash"></i>
                </a>    
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="creditModal" tabindex="-1" aria-labelledby="creditModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="creditForm">
        @csrf
        <input type="hidden" id="user_id" name="user_id" value="1"> <!-- example user ID -->
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="creditModalLabel">Update User Credit</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Current Credit: <span id="current_credit">100</span></p> <!-- example value -->
            <div class="mb-3">
              <label for="add_credit" class="form-label">Add Credit</label>
              <input type="number" class="form-control" id="add_credit" name="add_credit" required min="1">
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Update Credit</button>
            <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
          </div>
        </div>
      </form>
    </div>
  </div>



</x-app-layout>


<!-- Credit Modal -->


<script>
  $('#dataTable').DataTable({
    destroy: true,
    dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
    buttons: [{
        extend: 'excelHtml5',
        text: 'Export Excel',
        title: 'Lead Buyers - Complete List',
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
      {
        extend: 'csvHtml5',
        text: 'Export CSV',
        title: 'Lead Buyers - Complete List',
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
    ],
    "language": {
      "emptyTable": "No records found"
    }
  });

  $(document).on("click", ".view-credit", function() {
    let userId = $(this).data("user-id");
    let baseUrl = $("#_url").val();
    $.ajax({
      url: baseUrl + "/seller/get-credit/" + userId, // Route to get user credit
      type: "GET",
      success: function(res) {
        if (res.success) {

          $("#user_id").val(userId);
          $("#current_credit").text(res.data.total_credit);
          $("#add_credit").val("");
          var creditModalEl = document.getElementById("creditModal");
          var creditModal = new coreui.Modal(creditModalEl);
          creditModal.show();
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
</script>
<script src="{{ asset('coreui/js/common.js') }}"></script>