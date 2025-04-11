<x-app-layout>
    <x-slot name="header">{{ __('Request List') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Request List') }}</strong>
      </div>
      <div class="card-body">
      @if(count($aRows) > 0)
        <table class="table table-striped" id="dataTable">
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
            @foreach($aRows as $aKey => $aRow)
              @if(!empty($aRow->customer->name))
            <tr>
              <th scope="row">{{ $aKey+1 }}</th>
              <td>{{ $aRow->customer->name }}</td>
              <td>{{ $aRow->category->name }}</td>
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
              @endif
            @endforeach
           
          </tbody>
        </table>
        @else 
                <p style="text-align:center">No records found</p>
           
            
          @endif
      </div>
    </div>
</x-app-layout>           
