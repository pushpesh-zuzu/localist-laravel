<x-app-layout>
    <x-slot name="header">{{ __('View Quote Customers') }} </x-slot>
  <div class="row">
    <div class="col-md-12 col-xl-12 col-sm-12">
        <div class="card mb-4">
          <div class="card-header">
              <strong>{{ __('Personal Details') }}</strong>
          </div>
          <div class="card-body">
            <table class="table table-striped">
              <tbody>
              <tr>
                <td>Name</td>
                <td>{{ $aRows->name }}</td>
              </tr>
              <tr>
                <td>Email</td>
                <td>{{ $aRows->email }}</td>
              </tr>
              <tr>
                <td>Mobile</td>
                <td>{{ $aRows->phone }}</td>
              </tr>
              <!-- <tr>
                <td>Dob</td>
                <td>{{ $aRows->dob }}</td>
              </tr> -->
              <tr>
                <td>City</td>
                <td>{{ $aRows->city }}</td>
              </tr>
              <tr>
                <td>State</td>
                <td>{{ $aRows->state }}</td>
              </tr>
              <tr>
                <td>Zipcode</td>
                <td>{{ $aRows->zipcode }}</td>
              </tr>
              <tr>
                <td>Apartment</td>
                <td>{{ $aRows->apartment }}</td>
              </tr>
              <tr>
                <td>Registration</td>
                <td>{{ $aRows->created_at->format('d-m-Y') }}</td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 col-xl-6 col-sm-12">
        <div class="card mb-4">
          <div class="card-header">
              <strong>{{ __('Job Posted') }}</strong>
          </div>
          <div class="card-body">
            <table class="table table-striped">
            <thead>
              <tr>
              <th rowspan="2" scope="col" width="20px;">#</th>
              <th rowspan="2" scope="col">Customer</th>
              <th rowspan="2" scope="col">Service</th>
              <th rowspan="2" scope="col">Postcode</th>
              <th rowspan="2" scope="col">Phone</th>
              <th rowspan="2" scope="col">Questions</th>
              <th rowspan="2" scope="col">Details</th>
              <th rowspan="2" scope="col">Score</th>
              <th rowspan="2" scope="col">Receive Online</th>
              <th rowspan="2" scope="col">Prof. Letin</th>
              <th colspan="5" style="text-align:center;">Badges</th>
              <th rowspan="2" scope="col">Status</th>
              <th rowspan="2" scope="col">Date</th>
            </tr>
            <tr>
              <th scope="col">Urgent</th>
              <th scope="col">High Hiring Intent</th>
              <th scope="col">Phone Verified</th>
              <th scope="col">Frequent User</th>
              <th scope="col">Additional Dtails</th>
          </tr>
            </thead>
            <tbody>
              @foreach($aRows->leadRequests as $aKey => $aRow)
              <tr>
                <th scope="row">{{ $aKey+1 }}</th>
                <td>{{ $aRow->name }}</td>
                <td>{{ isset($aRow->category) ? $aRow->category['name'] : '' }}</td>
                <td>{{ $aRow->postcode }}</td>
                <td>{{ $aRow->phone }}</td>
                <td style="min-width: 400px;">
                <?php
                    $rel = "";
                    $quesArr = json_decode($aRow->questions,true);
                    $i =1;
                    foreach($quesArr as $q){
                        $rel .= "<b>Q$i.</b>" .$q['ques'] ."<br>";
                        $rel .="<b>Ans: &nbsp;</b>" .$q['ans'] ."<br><br>";
                        $i++;
                    }
                ?>
                {!! $rel !!}
                </td >
                <td style="min-width: 300px;">{{ $aRow->details }}</td>
                <td>{{ $aRow->credit_score }}</td>
                <td>{{ $aRow->recevive_online }}</td>
                <td>{{ $aRow->professional_letin }}</td>
                <td>{{ $aRow->is_urgent }}</td>
                <td>{{ $aRow->is_high_hiring }}</td>
                <td>{{ $aRow->is_phone_verified }}</td>
                <td>{{ $aRow->is_frequent_user }}</td>
                <td>{{ $aRow->has_additional_details }}</td>
                <td>{{ $aRow->status }}</td>
                <td>{{ date('m/d/Y h:i a', strtotime($aRow->created_at)) }}</td>
    
              </tr>
              @endforeach
            
            </tbody>
             
            </table>
          </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-6 col-sm-12">
      <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Job Posted(other Details)') }}</strong>
        </div>
        <div class="card-body">
          <table class="table table-striped">
            <tbody>
            @foreach($aRows->leadRequests as $aKey => $aRow)
            <tr>
              <td>Recevive Online</td>
              <td>{{ isset($aRow->recevive_online) && $aRow->recevive_online == 1 ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
              <td>Professional Letin</td>
              <td>{{ isset($aRow->professional_letin) ? $aRow->professional_letin : '' }}</td>
            </tr>
            <tr>
              <td>Urgent Request</td>
              <td>{{ isset($aRow->is_urgent) ? $aRow->is_urgent : '' }}</td>
            </tr>
            <tr>
              <td>High Hiring</td>
              <td>{{ isset($aRow->is_high_hiring) ? $aRow->is_high_hiring : '' }}</td>
            </tr>
            <tr>
              <td>Phone Verified</td>
              <td>{{ isset($aRow->is_phone_verified) ? $aRow->is_phone_verified : '' }}</td>
            </tr>
            <tr>
              <td>Frequent User</td>
              <td>{{ isset($aRow->is_frequent_user) ? $aRow->is_frequent_user : '' }}</td>
            </tr>
            <tr>
              <td>Additional Details</td>
              <td>{{ isset($aRow->has_additional_details) ? $aRow->has_additional_details : '' }}</td>
            </tr>
           
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
  </div>

  </div>
   
 
</x-app-layout>           