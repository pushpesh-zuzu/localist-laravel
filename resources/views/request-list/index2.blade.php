<x-app-layout>
    <x-slot name="header">{{ __('Request List') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Request List') }}</strong>
      </div>
      <div class="card-body">
       <div class="row">
        <div class="col-md-12">
          <table id="dataTable" class="table table-bordered table-striped">
            <thead>
              <th width="20px;">#</th>
              <th>Customer</th>
              <th>Loacation</th>
              <th>DateTime</th>
              <th>Category</th>              
              <th>Credits</th>             
              <th>Urgent</th>
              <th>High</th>
              <th>Verified</th>              
              <th>Additional</th>
              <th>Frequent</th>
              <th>Status</th>
              <th>Que/Ans</th>
              <th>Additional Dtails</th>
              
            </thead>
            <tbody>            
              @foreach($aRows as $aKey => $aRow)
              @if(!empty($aRow->customer->name))
            <tr>
              <th scope="row">{{ $aKey+1 }}</th>
              <td>{{ $aRow->customer->name }}</td>
              <td>{{ $aRow->city }}</td>
              <td>{{ date('m/d/Y h:i a', strtotime($aRow->created_at)) }}</td>
              <td>{{ $aRow->category->name }}</td>
              <td>{{ $aRow->credit_score }}</td>
              
              <td style="min-width: 300px;">{{ $aRow->is_urgent }}</td>
              <td>{{ $aRow->is_high_hiring }}</td>
              <td>{{ $aRow->is_phone_verified }}</td>
              <td>{{ $aRow->has_additional_details }}</td>
              <td>{{ $aRow->is_frequent_user }}</td>
              <td>{{ $aRow->status }}</td>
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
              <td>{{ $aRow->details }}</td>
  
            </tr>
              @endif
            @endforeach
            </tbody>
          </table>
        </div>
       </div>
      </div>
    </div>

    

</x-app-layout>           

