<x-app-layout>
    <x-slot name="header">{{ __('View Quote Customers') }} </x-slot>
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
              <div class="col-md-4"><b>City: </b> {{ $aRows->city }}</div>
              <div class="col-md-4"><b>State: </b> {{ $aRows->state }}</div>
              <div class="col-md-4"><b>Zipcode: </b> {{ $aRows->zipcode }}</div>
              <div class="col-md-4"><b>Apartment: </b> {{ $aRows->apartment }}</div>
              <div class="col-md-4"><b>Registration Date: </b> {{ $aRows->created_at->format('d-m-Y') }}</div>
              <div class="col-md-4"><b>Last Login: </b> {{ $aRows->last_login }}</div>
              <div class="col-md-4"><b>Number of hirers: </b> 0</div>
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
                <b>Badges: </b> {{$badges}} </div>
            </div>
          </div>
        </div>
    </div>
  </div>
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
   
 
</x-app-layout>           