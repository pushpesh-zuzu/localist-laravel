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
            <thead>
              <tr>
                <th rowspan="2" scope="col">Name</th>
                <th rowspan="2" scope="col">Email</th>
                <th rowspan="2" scope="col">Mobile</th>
                <th rowspan="2" scope="col">City</th>
                <th rowspan="2" scope="col">State</th>
                <th rowspan="2" scope="col">Zipcode</th>
                <th rowspan="2" scope="col">Apartment</th>
                <th rowspan="2" scope="col">Registration Date</th>
              </tr>
            </thead>
              <tbody>
              <tr>
                <td>{{ $aRows->name }}</td>
                <td>{{ $aRows->email }}</td>
                <td>{{ $aRows->phone }}</td>
                <td>{{ $aRows->city }}</td>
                <td>{{ $aRows->state }}</td>
                <td>{{ $aRows->zipcode }}</td>
                <td>{{ $aRows->apartment }}</td>
                <td>{{ $aRows->created_at->format('d-m-Y') }}</td>
              </tr>
              </tbody>
            </table>
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