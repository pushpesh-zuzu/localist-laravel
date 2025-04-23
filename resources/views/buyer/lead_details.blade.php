<x-app-layout>
    <x-slot name="header">{{ 'Lead Details' . (!empty($user) ? " ($user)" : '') }} </x-slot>
  <div class="row">
    <div class="col-md-12 col-xl-12 col-sm-12">
        <div class="card mb-4">
          <div class="card-header">
              <strong>{{ __('Leads') }}</strong>
          </div>
          <div class="card-body">
            <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Questions</th>
                <th>Receive Online</th>
                <th>Prof. Letin</th>
                <th>Urgent</th>
                <th>High Hiring Intent</th>
                <th>Phone Verified</th>
                <th>Frequent User</th>
                <th>Additional Dtails</th>
            </tr>
            </thead>
            <tbody>
              <tr>
              <td style="min-width: 400px;">
                <?php
                    $rel = "";
                    $quesArr = json_decode($aRows->questions,true);
                    $i =1;
                    foreach($quesArr as $q){
                        $rel .= "<b>Q$i.</b>" .$q['ques'] ."<br>";
                        $rel .="<b>Ans: &nbsp;</b>" .$q['ans'] ."<br><br>";
                        $i++;
                    }
                ?>
                {!! $rel !!}
                </td >
                <td style="text-align:center;">{{ isset($aRows->recevive_online) && $aRows->recevive_online == 1 ? 'Yes' : 'No' }}</td>
                <td style="text-align:center;">{{ isset($aRows->professional_letin) && $aRows->professional_letin == 1 ? 'Yes' : 'No' }}</td>
                <td style="text-align:center;">{{ isset($aRows->is_urgent) && $aRows->is_urgent == 1 ? 'Yes' : 'No' }}</td>
                <td style="text-align:center;">{{ isset($aRows->is_high_hiring) && $aRows->is_high_hiring == 1 ? 'Yes' : 'No' }}</td>
                <td style="text-align:center;">{{ isset($aRows->is_phone_verified) && $aRows->is_phone_verified == 1 ? 'Yes' : 'No' }}</td>
                <td style="text-align:center;">{{ isset($aRows->is_frequent_user) && $aRows->is_frequent_user == 1 ? 'Yes' : 'No' }}</td>
                <td style="text-align:center;">{{ isset($aRows->has_additional_details) && $aRows->has_additional_details == 1 ? 'Yes' : 'No' }}</td>
               
              </tr>
            </tbody>
             
            </table>
          </div>
        </div>
    </div>
   

  </div>
   
 
</x-app-layout>           