<x-app-layout>
  @section('title', 'Lead Buyers (Complete List)')
 <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Lead Buyers (Complete List)") }}</h4>
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
        <form method="GET" action="{{ route('seller.complete') }}" class="row g-3 align-items-end mb-4">

          <div class="col-md-2">
            <label class="form-label small custom-label ms-2" style="color: black;">From Date</label>
            <input type="date" name="from_date"
              value="{{ request('from_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-2">
            <label class="form-label small custom-label ms-2">To Date</label>
            <input type="date" name="to_date"
              value="{{ request('to_date') }}"
              class="form-control rounded-pill shadow-sm px-3">
          </div>

          <div class="col-md-4 d-flex gap-2">
            <button type="submit"
              class="btn btn-primary px-3 rounded-pill shadow-sm">
              🔍 Filter
            </button>

            <a href="{{ route('seller.complete') }}"
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
                <!-- <th scope="col">Entry URL</th>
              <th scope="col">User IP</th> -->
                <th scope="col">Last Login</th>
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
                <td class="text text-center">{{ $aRow->total_credit }}</td>
                <!-- <td style="word-break: break-all; max-width: 200px;">
                {{ $aRow->entry_url ?? '' }}
              </td>
              <td>{{ $aRow->user_ip_address ?? '' }}</td> -->
                <td>{{ $aRow->lastLogin?->login_at ? \Carbon\Carbon::parse($aRow->lastLogin->login_at)->format('m/d/Y h:i a') : '' }} </td>
                <td>{{ $aRow->form_status == 1 ? 'Complete' : 'InComplete' }}</td>
                <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
                <td>{{ $aRow->zoho_record_id  ? 'Inserted'     : 'Not-inserted'; }}</td>
                <td>
                    <a href="javascript:void(0)"
                      data-user-email="{{ $aRow->email }}"
                      class="text text-primary login-link"
                      title="Get Login Link">
                      <i class="fa-solid fa-link"></i>
                    </a> 

                  @can('leadbuyers.sendtozoho')
                  @if(!$aRow->zoho_record_id && !empty($aRow->name) && !empty($aRow->email))
                  <a href="{{ route('zoho.seller.send', ['type' => 'complete', 'id' => $aRow->id]) }}" class="text-primary text-decoration-none">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Send to Zoho |
                  </a>
                  @endif
                  @endcan

                  @can('leadbuyers.add-credit')
                  <a href="javascript:void(0)"
                    class="text text-success view-credit"
                    data-user-id="{{ $aRow->id }}"
                    title="Add Credit">
                    <i class="bi bi-plus-circle"></i>
                  </a>
                  <a href="javascript:void(0)"
                    class="text text-success view-autobid-setting"
                    data-user-id="{{ $aRow->id }}"
                    title="Autobid Setting">
                    <i class="bi bi-gear"></i>
                  </a>

                  @endcan
                  @can('leadbuyers.credit-deduction')
                  <a href="javascript:void(0)"
                    class="text text-danger deduct-credit"
                    data-user-id="{{ $aRow->id }}"
                    title="Deduct Credit">
                    <i class="bi bi-dash-circle"></i>
                  </a>
                  @endcan
                  @can('leadbuyers.bids')
                  <a href="{{ route('seller.sellerBids',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-chess-pawn" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Bids"></i></a>
                  @endcan
                  @can('leadbuyers.loginhistory')
                  <a href="{{ route('seller.sellerLogin',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-history" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Login History"></i></a>
                  @endcan
                  @can('leadbuyers.creditplans')
                  <a href="{{ route('seller.creditPlans',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Credit Plans"><i class="bi bi-list-task nav-icon"></i></a>
                  @endcan
                  @can('leadbuyers.suggested-questions')
                  <a href="{{ route('seller.suggestedQuestions',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Suggested Questions"><i class="bi bi-question-circle nav-icon"></i></a>
                  @endcan
                  @can('leadbuyers.services')
                  <a href="{{ route('seller.services',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Services"><i class="bi bi-person-lines-fill"></i></a>
                  @endcan
                  @can('leadbuyers.view-details')
                  <a href="{{ route('seller.show.custom',['type' => 'complete', 'id' => $aRow->id]) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View Details"> <i class="bi bi-eye"></i></a>
                  @endcan
                  @can('leadbuyers.view-public-profile')
                  @php
                  $postloginBaseUrl = rtrim(\App\Helpers\CustomHelper::setting_value('postlogin_react_base_url'), '/');
                  $slug = strtolower(preg_replace('/\s+/', '-', trim($aRow->name)));
                  @endphp

                  <a href="{{ $postloginBaseUrl . '/view-profile/' . $slug . '/' . $aRow->id }}"
                    target="_blank"
                    data-coreui-toggle="tooltip"
                    data-coreui-placement="top"
                    title="View Public Profile">
                    <i class="bi bi-person-badge"></i>
                  </a>
                  @endcan
                  @can('leadbuyers.custom-reviews')
                  <a href="javascript:void(0)"
                    onclick="openCustomReviewModal('{{ $aRow->id }}')"
                    class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Custom Reviews"
                    title="Custom Reviews">
                    <i class="fa-solid fa-star"></i>
                  </a>
                  @endcan
                  @can('leadbuyers.complete-delete')
                  <a href="javascript:void(0)"
                    onclick="deleteUser('{{ $aRow->id }}', 'complete', '')"
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

    <div class="modal fade" id="creditModal" tabindex="-1" aria-labelledby="creditModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="creditForm">
          @csrf
          <input type="hidden" id="user_id" name="user_id">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="creditModalLabel">Update Lead Buyer Credit</h5>
              <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="creditMessage"></div>
              <div>
                <label class="form-label mb-3"><strong>Current Credit:<strong></label>
                <span id="current_credit" class="badge bg-primary fs-6 ms-1">0</span>
              </div>
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


    <div class="modal fade" id="autobidSettingsModal" tabindex="-1" aria-labelledby="autobidSettingsModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="autobidSettingsForm">
          @csrf
          <input type="hidden" id="autobid_settings_user_id" name="autobid_settings_user_id">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="autobidSettingsModalLabel">Update Lead Buyer Autobid Settings</h5>
              <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-12">
                  <p id="autobidSettingsMessage"></p>
                </div>
                <div class="col-md-12">
                  <label class="form-label mb-3"><strong>Autobid Limit:<strong></label>
                  <span id="cur_autobid_limit" class="badge bg-primary fs-6 ms-1">0</span>
                </div>
                <div class="col-md-12">
                  <label class="form-label mb-3"><strong>Autobid Batch Hour Limit:<strong></label>
                  <span id="cur_autobid_batch_hour_limit" class="badge bg-primary fs-6 ms-1">0</span>
                </div>
              </div>
              <hr />
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="autobid_limit" class="form-label">Autobid Limit (0 for Global)</label>
                  <input type="number" class="form-control" id="autobid_limit" name="autobid_limit" required min="0">
                </div>
                <div class="col-md-12">
                  <label for="autobid_batch_hour_limit" class="form-label">Autobid Batch Hour Limit (0 for Global)</label>
                  <input type="number" class="form-control" id="autobid_batch_hour_limit" name="autobid_batch_hour_limit" required min="0">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Update Settings</button>
              <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
            </div>
          </div>
        </form>
      </div>
    </div>


    <!-- Custom Reviews Modal -->
    <div class="modal fade" id="customReviewModal" tabindex="-1" aria-labelledby="customReviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="customReviewModalLabel">Custom Reviews</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <form id="customReviewForm">
              <input type="hidden" name="user_id" id="row_id">

              <div class="table-responsive">
                <table class="table table-bordered align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Review Platform</th>
                      <th>Number of Reviews</th>
                      <th>Score</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Facebook</td>
                      <td><input type="text" name="facebook_reviews" class="form-control" placeholder="Enter number"></td>
                      <td><input type="number" name="facebook_score" min="0" max="5" class="form-control" placeholder="Enter score"></td>
                    </tr>
                    <tr>
                      <td>Google</td>
                      <td><input type="text" name="google_reviews" class="form-control" placeholder="Enter number"></td>
                      <td><input type="number" name="google_score" min="0" max="5" class="form-control" placeholder="Enter score"></td>
                    </tr>
                    <tr>
                      <td>Trust Pilot</td>
                      <td><input type="text" name="trustpilot_reviews" class="form-control" placeholder="Enter number"></td>
                      <td><input type="number" name="trustpilot_score" min="0" max="5" class="form-control" placeholder="Enter score"></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </form>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="saveCustomReview()">Save Changes</button>
          </div>
        </div>
      </div>
    </div>




    <div class="modal fade" id="creditDeductionModal" tabindex="-1" aria-labelledby="creditDeductionModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="creditDeductionForm">
          @csrf
          <input type="hidden" id="deduct_user_id" name="user_id">

          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="creditDeductionModalLabel">Deduct Lead Buyer Credit</h5>
              <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <div id="creditDeductionMessage"></div>

              <div class="mb-3">
                <label class="form-label"><strong>Current Credit:</strong></label>
                <span id="deduct_current_credit" class="badge bg-primary fs-6 ms-1">0</span>
              </div>

              <div class="mb-3">
                <label for="deduct_credit" class="form-label">Deduct Credit</label>
                <input type="number" class="form-control" id="deduct_credit" name="deduct_credit" required min="1">
              </div>

            </div>

            <div class="modal-footer">
              <button type="submit" class="btn btn-danger">Deduct Credit</button>
              <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
            </div>
          </div>
        </form>
      </div>
    </div>


    <!-- Credit Modal -->

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
      $('#dataTable').DataTable({
        destroy: true,
        dom: '<"top-toolbar d-flex justify-content-between align-items-center"lBf>rtip',
        buttons: [
          @can('leadbuyers.complete-excelexport') {
            text: 'Export Excel',
            className: "btn btn-success btn-sm",
            action: function(e, dt, node, config) {
              let start = $('#from_date').val();
              let end = $('#to_date').val();
              let search = dt.search(); // Use dt instead of dataTable

              let query = $.param({
                start_date: start,
                end_date: end,
                search: search
              });
              window.location.href = "{{ route('export.com.seller.excel') }}" + "?" + query;

              e.preventDefault();
            }
          },
          @endcan
          @can('leadbuyers.complete-csvexport') {
            text: 'Export CSV',
            className: "btn btn-info btn-sm",
            action: function(e, dt, node, config) {
              let start = $('#from_date').val();
              let end = $('#to_date').val();
              let search = dt.search(); // Use dt instead of dataTable

              let query = $.param({
                start_date: start,
                end_date: end,
                search: search
              });
              window.location.href = "{{ route('export.com.seller.csv') }}" + "?" + query;

              e.preventDefault();
            }
          }
          @endcan
        ],
        "language": {
          "emptyTable": "No records found"
        },
        columnDefs: [{
            targets: 0,
            searchable: false,
            orderable: false,
            targets: [3, 4, 5, 6, 7, 8]
          } // disable search on the first (#) column
        ],
        order: [
        [0, 'desc']
      ],
      });

      $(document).on("click", ".deduct-credit", function() {
        let userId = $(this).data("user-id");
        let baseUrl = $("#_url").val();
        $("#deduct_user_id").val('');
        $("#deduct-credit").text('');
        $('#creditDeductionMessage').html('');
        $.ajax({
          url: baseUrl + "/seller/get-credit/" + userId, // Route to get user credit
          type: "GET",
          success: function(res) {
            if (res.success) {

              $("#deduct_user_id").val(userId);
              $("#deduct_current_credit").text(res.data.total_credit);
              $("#deduct_credit").val("");
              var creditModalE = document.getElementById("creditDeductionModal");
              var creditModalShow = new coreui.Modal(creditModalE);
              creditModalShow.show();
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



      $(document).on("click", ".view-credit", function() {
        let userId = $(this).data("user-id");
        let baseUrl = $("#_url").val();
        $("#user_id").val('');
        $("#current_credit").text('');
        $('#creditMessage').html('');
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

      $('#creditForm').submit(function(e) {
        e.preventDefault();

        let userId = $('#user_id').val();
        let addCredit = $('#add_credit').val();


        $('#creditMessage').html('');
        $('#add_credit').removeClass('is-invalid');
        $('#addCreditError').remove();


        if (!userId) {
          $('#creditMessage').html(
            `<div class="alert alert-danger">User ID is missing. Please try again.</div>`
          );
          return;
        }

        $.ajax({
          url: "{{ route('seller.addCredit') }}",
          type: "POST",
          data: {
            _token: $('input[name=_token]').val(),
            user_id: userId,
            add_credit: addCredit
          },
          success: function(response) {
            if (response.success) {
              $('#creditMessage').html(
                `<div class="alert alert-success">${response.message || 'Credit updated successfully!'}</div>`
              );
              $('#current_credit').text(response.new_credit);
              $('#add_credit').val('');
            } else {
              $('#creditMessage').html(
                `<div class="alert alert-warning">${response.message || 'Something went wrong.'}</div>`
              );
            }
          },
          error: function(xhr) {
            if (xhr.status === 422) {
              // Validation errors
              let errors = xhr.responseJSON.errors;
              if (errors.add_credit) {
                $('#add_credit').addClass('is-invalid');
                $('#add_credit').after(
                  `<div id="addCreditError" class="invalid-feedback">${errors.add_credit[0]}</div>`
                );
              }
            } else if (xhr.status === 403) {
              // Permission denied (no access)
              let message = xhr.responseJSON?.message || 'You do not have permission to perform this action.';
              $('#creditMessage').html(
                `<div class="alert alert-danger">${message}</div>`
              );
            } else {
              // Other server errors
              $('#creditMessage').html(
                `<div class="alert alert-danger">Server error occurred. Please try again.</div>`
              );
            }
          }
        });
      });

      $(document).on("click", ".view-autobid-setting", function() {
        let autobidSettingsUserId = $(this).data("user-id");
        let baseUrl = $("#_url").val();
        $("#autobid_settings_user_id").val('');
        $("#cur_autobid_limit").text('');
        $("#cur_autobid_batch_hour_limit").text('');
        $('#autobidSettingsMessage').html('');
        $.ajax({
          url: baseUrl + "/seller/get-autobid-settings/" + autobidSettingsUserId, // Route to get user credit
          type: "GET",
          success: function(res) {
            if (res.success) {
              let curAutobitLimit = res.data.autobid_limit > 0 ? res.data.autobid_limit : 'Global';
              let curAutobidBatchHourLimit = res.data.autobid_batch_hour_limit > 0 ? res.data.autobid_batch_hour_limit + ' hr(s)' : 'Global';
              $("#autobid_settings_user_id").val(autobidSettingsUserId);
              $("#cur_autobid_limit").text(curAutobitLimit);
              $("#cur_autobid_batch_hour_limit").text(curAutobidBatchHourLimit);
              $("#autobid_limit").val("");
              $("#autobid_batch_hour_limit").val("");
              var autobidSettingsModalEl = document.getElementById("autobidSettingsModal");
              var autobidSettingsModal = new coreui.Modal(autobidSettingsModalEl);
              autobidSettingsModal.show();
            } else {
              alert(res.message || "Failed to fetch user autobid settings.");
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

      $('#autobidSettingsForm').submit(function(e) {
        e.preventDefault();

        let autobidSettingsUserId = $('#autobid_settings_user_id').val();
        let autobidLimit = $('#autobid_limit').val();
        let autobidBatchHourLimit = $('#autobid_batch_hour_limit').val();


        $('#autobidSettingsMessage').html('');
        $('#autobid_limit').removeClass('is-invalid');
        $('#autobid_batch_hour_limit').removeClass('is-invalid');
        $('#addAutobidLimitError').remove();
        $('#addAutobidBatchHourLimitError').remove();


        if (!autobidSettingsUserId) {
          $('#autobidSettingsMessage').html(
            `<div class="alert alert-danger">User ID is missing. Please try again.</div>`
          );
          return;
        }

        $.ajax({
          url: "{{ route('seller.updateAutobidSettings') }}",
          type: "POST",
          data: {
            _token: $('input[name=_token]').val(),
            user_id: autobidSettingsUserId,
            autobid_limit: autobidLimit,
            autobid_batch_hour_limit: autobidBatchHourLimit,
          },
          success: function(response) {
            if (response.success) {
              $('#autobidSettingsMessage').html(
                `<div class="alert alert-success">${response.message || 'Autobid settings updated successfully!'}</div>`
              );
              let curAutobitLimit = response.data.autobid_limit > 0 ? response.data.autobid_limit : 'Global';
              let curAutobidBatchHourLimit = response.data.autobid_batch_hour_limit > 0 ? response.data.autobid_batch_hour_limit + ' hr(s)' : 'Global';
              $("#autobid_settings_user_id").val(autobidSettingsUserId);
              $("#cur_autobid_limit").text(curAutobitLimit);
              $("#cur_autobid_batch_hour_limit").text(curAutobidBatchHourLimit);
              $("#autobid_limit").val("");
              $("#autobid_batch_hour_limit").val("");
            } else {
              $('#autobidSettingsMessage').html(
                `<div class="alert alert-warning">${response.message || 'Something went wrong.'}</div>`
              );
            }
          },
          error: function(xhr) {
            if (xhr.status === 422) {
              // Validation errors
              let errors = xhr.responseJSON.errors;
              if (errors.autobid_limit) {
                $('#autobid_limit').addClass('is-invalid');
                $('#autobid_limit').after(
                  `<div id="addAutobidLimitError" class="invalid-feedback">${errors.autobid_limit[0]}</div>`
                );
              }
              if (errors.autobid_batch_hour_limit) {
                $('#autobid_batch_hour_limit').addClass('is-invalid');
                $('#autobid_batch_hour_limit').after(
                  `<div id="addAutobidBatchHourLimitError" class="invalid-feedback">${errors.autobid_batch_hour_limit[0]}</div>`
                );
              }
            } else if (xhr.status === 403) {
              // Permission denied (no access)
              let message = xhr.responseJSON?.message || 'You do not have permission to perform this action.';
              $('#autobidSettingsMessage').html(
                `<div class="alert alert-danger">${message}</div>`
              );
            } else {
              // Other server errors
              $('#autobidSettingsMessage').html(
                `<div class="alert alert-danger">Server error occurred. Please try again.</div>`
              );
            }
          }
        });

      });

      $('#creditDeductionForm').submit(function(e) {
        e.preventDefault();

        let userId = $('#deduct_user_id').val();
        let deductCredit = $('#deduct_credit').val();


        $('#creditDeductionMessage').html('');
        $('#deduct_credit').removeClass('is-invalid');
        $('#addCreditError').remove();


        if (!userId) {
          $('#creditMessage').html(
            `<div class="alert alert-danger">User ID is missing. Please try again.</div>`
          );
          return;
        }

        $.ajax({
          url: "{{ route('seller.deductCredits') }}",
          type: "POST",
          data: {
            _token: $('input[name=_token]').val(),
            user_id: userId,
            deduct_credit: deductCredit
          },
          success: function(response) {
            if (response.success) {
              $('#creditDeductionMessage').html(
                `<div class="alert alert-success">${response.message || 'Credit updated successfully!'}</div>`
              );
              $('#deduct_current_credit').text(response.new_credit);
              $('#deduct_credit').val('');
            } else {
              $('#creditDeductionMessage').html(
                `<div class="alert alert-warning">${response.message || 'Something went wrong.'}</div>`
              );
            }
          },
          error: function(xhr) {
            if (xhr.status === 422) {
              // Validation errors
              let errors = xhr.responseJSON.errors;
              if (errors.add_credit) {
                $('#deduct_credit').addClass('is-invalid');
                $('#deduct_credit').after(
                  `<div id="addCreditError" class="invalid-feedback">${errors.add_credit[0]}</div>`
                );
              }
            } else if (xhr.status === 403) {
              // Permission denied (no access)
              let message = xhr.responseJSON?.message || 'You do not have permission to perform this action.';
              $('#creditDeductionMessage').html(
                `<div class="alert alert-danger">${message}</div>`
              );
            } else {
              // Other server errors
              $('#creditDeductionMessage').html(
                `<div class="alert alert-danger">Server error occurred. Please try again.</div>`
              );
            }
          }
        });
      });
    </script>
    <script src="{{ asset('coreui/js/common.js') }}"></script>
    <script>
      // Function to open modal and set row id
      function openCustomReviewModal(rowId) {
        document.getElementById('row_id').value = rowId;
        const modal = new coreui.Modal(document.getElementById('customReviewModal'));
        modal.show();
      }

      // Example save function (you can adjust to use AJAX)
      function saveCustomReview() {
        const form = document.getElementById('customReviewForm');
        const formData = new FormData(form);

        // Example: log data
        console.log(Object.fromEntries(formData.entries()));

        // Example AJAX request (Laravel route)
        $.ajax({
          url: "{{ route('seller.save.custom.review') }}",
          type: 'POST',
          data: formData,
          dataType: 'JSON',
          processData: false, // Important for FormData
          contentType: false, // Important for FormData
          headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
          },
          success: function(response) {
            if (response.success === true) {
              alert('Saved successfully!'); // You can customize this
              const modalEl = document.getElementById('customReviewModal');
              const modal = coreui.Modal.getInstance(modalEl);
              modal.hide();
            } else {
              // Handle validation errors from server
              console.log('Validation errors:', response.html);
              alert('Validation failed. Check console for details.');
            }
          },
          error: function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
          }
        });
      }
    </script>

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