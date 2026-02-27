<x-app-layout>
  <x-slot name="header"> View @if($aRows->form_status == 0) Abandoned @endif Quote Customers </x-slot>
  <div class="row">
    <div class="col-md-12 col-xl-12 col-sm-12">
      <div class="card mb-4">
        <div class="card-header">
          <strong>{{ __('Personal Details') }}</strong>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4"><b>Name: </b> {{ $aRows->name }}</div>
            <div class="col-md-4"><b>Email: </b> {{ $aRows->email }}</div>
            <div class="col-md-4"><b>Mobile: </b> {{ $aRows->phone }}</div>
            <div class="col-md-4"><b>Registration Date: </b> {{ $aRows->created_at->format('d-m-Y') }}</div>
            @if($aRows->form_status == 1)
            <div class="col-md-4"><b>Last Login: </b> {{ !empty($aRows->last_login) ? date('dS M Y \a\t h:i A', strtotime($aRows->last_login)) : 'N/A' }}</div>
            <div class="col-md-4">
              <?php
              $hireCount = App\Models\RecommendedLead::where('buyer_id', $aRows->id)
                ->where('status', 'hired')
                ->count();
              ?>
              <b>Number of hirers: </b> {{$hireCount}}
            </div>
            <?php
            $badges = "";
            $is_phone_verified =  App\Models\User::where('id', $user_id)->value('phone_verified') == 1 ? 1 : 0;
            $leadCount = App\Models\LeadRequest::where('customer_id', $user_id)->where('created_at', '>=', Carbon\Carbon::now()->subMonths(3))->count();
            $is_frequent_user = $leadCount > 0 ? 1 : 0;

            if ($is_phone_verified) {
              if (!empty($badges)) {
                $badges .= ", ";
              }
              $badges .= 'Phone Verified';
            }

            if ($is_frequent_user) {
              if (!empty($badges)) {
                $badges .= ", ";
              }
              $badges .= 'Frequent User';
            }
            ?>
            <div class="col-md-4">
              <b>Badges: </b> {{$badges}}
            </div>
            @endif

            <div class="col-md-4"><b>Campaign Id: </b> {{ $aRows->campaignid }}</div>
            <div class="col-md-4"><b>GCLID: </b> {{ $aRows->gclid }}</div>
            <div class="col-md-4"><b>Keyword: </b> {{ $aRows->keyword }}</div>

            <div class="col-md-4"><b>Campaign: </b> {{ $aRows->campaign }}</div>
            <div class="col-md-4"><b>AdGroup: </b> {{ $aRows->adgroup }}</div>
            <div class="col-md-4"><b>Target Id: </b> {{ $aRows->targetid }}</div>
            <div class="col-md-4"><b>MS Click Id: </b> {{ $aRows->msclickid }}</div>

            <div class="col-md-4"><b>Entry URL: </b> {{ $aRows->entry_url ?? '' }}</div>
            <div class="col-md-4"><b>User IP Address: </b> {{ $aRows->user_ip_address ?? '' }}</div>
            <div class="col-md-4">
              <b>Source: </b>
              @php 
                $platform_source = $aRows->platform_source;
                
                $canUpdatePlatformSource = false;
                if ($type === 'abandoned') {
                    $canUpdatePlatformSource = auth()->user()->can('quotecustomers.incom-update-platform-source');
                } else {
                    $canUpdatePlatformSource = auth()->user()->can('quotecustomers.update-platform-source');
                }
              @endphp
              <select style="width: 85%; display:inline-block;" class="form-control" name="platform_source" id="platform_source" 
                @if(!$canUpdatePlatformSource) disabled @endif onchange="updatePlatformSource(this,{{$aRows->id}})">
                <option value="">Select Source</option>
                <option value="Facebook Form" @if($platform_source === 'Facebook Form') selected @endif>Facebook Form</option>
                <option value="Google Ads" @if($platform_source == 'Google Ads') selected @endif>Google Ads</option>
                <option value="Microsoft Ads" @if($platform_source == 'Microsoft Ads') selected @endif>Microsoft Ads</option>
                <option value="SEO" @if($platform_source == 'SEO') selected @endif>SEO</option>
                <option value="AWin" @if($platform_source == 'AWin') selected @endif>AWin</option>
                <option value="Your Tarmac Driveway" @if($platform_source == 'Your Tarmac Driveway') selected @endif>Your Tarmac Driveway</option>
                <option value="Your Resin Driveway" @if($platform_source == 'Your Resin Driveway') selected @endif>Your Resin Driveway</option>
              </select> 
            </div>
            <div class="col-md-4"><b>Quote Type: </b> {{ $aRows->quote_type ?? '' }}</div>
          </div>



        </div>
      </div>
    </div>
  </div>
  @if($aRows->form_status == 0)
  <div class="row">
    <div class="col-md-12 col-xl-12 col-sm-12">
      <div class="card mb-4">
        <div class="card-header">
          <strong>{{ __('Incomplete Job Info') }}</strong>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4"><b>Service: </b> {{ !empty($aRows->service_id) ? \App\Models\Category::find($aRows->service_id)->name : 'N/A' }}</div>
            <div class="col-md-4"><b>City: </b> {{ $aRows->city ?? 'N/A' }}</div>
            <div class="col-md-4"><b>Zipcode: </b> {{ $aRows->zipcode ?? 'N/A' }}</div>
            <div class="col-md-4"><b>Question: </b> <br>
              <?php
              $quesArr = json_decode($aRows->questions, true);
              $output = '';
              if (is_array($quesArr)) {
                foreach ($quesArr as $index => $q) {
                  if (!is_array($q) || !isset($q['ques'], $q['ans'])) {
                    continue; // skip null or invalid entries
                  }
                  $output .= "<b>Q" . ($index + 1) . ".</b> " . e($q['ques']) . "<br>";
                  $output .= "<b>Ans: </b>" . e($q['ans']) . "<br><br>";
                }
              }
              ?>

              {!! $output ?? 'N/A' !!}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @else
  <div class="row">
    <div class="col-md-12 col-xl-12 col-sm-12">
      <div class="card mb-4">
        <div class="card-header">
          <strong>{{ __('Job Posted') }}</strong>
        </div>
        <div class="card-body">
          <table class="table table-striped">
            <thead>
              <tr>
                <th rowspan="2" scope="col" width="20px;">#</th>
                <th rowspan="2" scope="col">Service</th>
                <th rowspan="2" scope="col">Postcode</th>
                <th rowspan="2" scope="col">Phone</th>
                <th rowspan="2" scope="col">Details</th>
                <th rowspan="2" scope="col">Score</th>
                <th rowspan="2" scope="col">Status</th>
                <th rowspan="2" scope="col">Date</th>
                <th rowspan="2" scope="col">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($aRows->leadRequests as $aKey => $aRow)
              <tr>
                <th scope="row">{{ $aKey+1 }}</th>
                <td>{{ isset($aRow->category) ? $aRow->category['name'] : '' }}</td>
                <td>{{ $aRow->postcode }}</td>
                <td>{{ $aRow->phone }}</td>

                <td style="min-width: 300px;">{{ $aRow->details }}</td>
                <td style="text-align:center;">{{ $aRow->credit_score }}</td>

                <td>{{ $aRow->status }}</td>
                <td>{{ date('d/m/Y h:i a', strtotime($aRow->created_at)) }}</td>
                <td style="text-align:center;"> 
                   @can('quotecustomers.viewjobpostedlist')
                  <a href="{{ route('buyer.leadDetails',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
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
  @endif
  
  @push('scripts')
<script>
  function updatePlatformSource(selectElement, userId) {
    var selectedValue = selectElement.value;
    var type = "{{ $type }}";
    $.ajax({
      url: "{{ route('buyer.updatePlatformSource') }}",
      type: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        user_id: userId,
        platform_source: selectedValue,
        type: type
      },
      success: function(response) {
        if (response.success) {
          alert('Platform source updated successfully.');
        } else {
          alert('Failed to update platform source.');
        }
      },
      error: function(xhr, status, error) {
        console.error(xhr.responseText);
        alert('An error occurred while updating the platform source.');
      }
    });
  }
</script> 
@endpush
</x-app-layout>