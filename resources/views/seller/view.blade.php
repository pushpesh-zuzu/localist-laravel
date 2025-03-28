<x-app-layout>
    <x-slot name="header">{{ __('View Lead Buyers') }} </x-slot>
<div class="row">
<div class="col-md-6 col-xl-6 col-sm-12">
<div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Personal Details') }}</strong>
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <td>Name</td>
            <td>{{ $aRow->name }}</td>
          </tr>
          <tr>
            <td>Email</td>
            <td>{{ $aRow->email }}</td>
          </tr>
          <tr>
            <td>Mobile</td>
            <td>{{ $aRow->phone }}</td>
          </tr>
          <tr>
            <td>Dob</td>
            <td>{{ $aRow->dob }}</td>
          </tr>
          <tr>
            <td>City</td>
            <td>{{ $aRow->city }}</td>
          </tr>
          <tr>
            <td>State</td>
            <td>{{ $aRow->state }}</td>
          </tr>
          <tr>
            <td>Zipcode</td>
            <td>{{ $aRow->zipcode }}</td>
          </tr>
          <tr>
            <td>Apartment</td>
            <td>{{ $aRow->apartment }}</td>
          </tr>
          <tr>
            <td>Registration</td>
            <td>{{ $aRow->created_at->format('d-m-Y') }}</td>
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
          <strong>{{ __('Company Details') }}</strong>
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <td>Company Name</td>
            <td>{{ $aRow->company_name }}</td>
          </tr>
          <tr>
            <td>Company Size</td>
            <td>{{ $aRow->company_size }}</td>
          </tr>
          <tr>
            <td>Company Sales Team</td>
            <td>{{ $aRow->company_sales_team }}</td>
          </tr>
          <tr>
            <td>Company Website</td>
            <td>{{ $aRow->company_website }}</td>
          </tr>
          
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
</div>
</div>
   
 
</x-app-layout>           