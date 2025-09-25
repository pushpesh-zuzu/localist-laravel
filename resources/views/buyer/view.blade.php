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
                      ->where('status','hired')
                      ->count();
                  ?>
                  <b>Number of hirers: </b> {{$hireCount}}
                </div>
                <?php
                  $badges = "";
                  $is_phone_verified =  App\Models\User::where('id',$user_id)->value('phone_verified') == 1 ? 1 : 0;
                  $leadCount = App\Models\LeadRequest::where('customer_id',$user_id)->where('created_at', '>=', Carbon\Carbon::now()->subMonths(3))->count();
                  $is_frequent_user = $leadCount > 0 ? 1: 0;

                  if($is_phone_verified){
                    if(!empty($badges)){
                      $badges .=", ";
                    }
                    $badges .= 'Phone Verified';
                  }

                  if($is_frequent_user){
                    if(!empty($badges)){
                      $badges .=", ";
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
              <div class="col-md-4"><b>Question:  </b> <br> 
                <?php
                  $quesArr = json_decode($aRows->questions, true);
                  $output = '';
                  if (is_array($quesArr)) {
                      foreach ($quesArr as $index => $q) {
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
                  <td>{{ date('m/d/Y h:i a', strtotime($aRow->created_at)) }}</td>
                  <td style="text-align:center;"> <a href="{{ route('buyer.leadDetails',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a></td>
      
                </tr>
                @endforeach
              
              </tbody>
              
              </table>
            </div>
          </div>
      </div>
    

    </div>
  @endif
   
 
</x-app-layout>           