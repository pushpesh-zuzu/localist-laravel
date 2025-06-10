<x-app-layout>
    <x-slot name="header">{{ __('View Lead Buyers') }} </x-slot>
  <div class="row">
    <div class="col-md-6 col-xl-6 col-sm-12">
      <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Personal Details') }}</strong>
        </div>
        <div class="card-body">
          <table class="table table-striped table-bordered">
            <tbody>
            <tr>
              <td style="width: 100px;">Name</td>
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
            <tr>
              <td>Last Login</td>
              <td>{{ $aRows->last_login }}</td>
            </tr>
            <tr>
              <td>Total Credit</td>
              <td>{{ $aRows->total_credit }}</td>
            </tr>
            
            
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-6 col-sm-12">
      <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Billing Details') }}</strong>
        </div>
        <div class="card-body">
          <table class="table table-striped table-bordered">
            <tbody>
            <tr>
              <td style="width: 200px;">Contact Name</td>
              <td>{{ optional($aRows->userDetails)->billing_contact_name ?? '' }}</td>
            </tr>
            <tr>
              <td>Address1</td>
              <td>{{ optional($aRows->userDetails)->billing_address1 ?? '' }}</td>
            </tr>
            <tr>
              <td>Address2</td>
              <td>{{ optional($aRows->userDetails)->billing_address2 ?? '' }}</td>
            </tr>
            <tr>
              <td>City</td>
              <td>{{ optional($aRows->userDetails)->billing_city ?? '' }}</td>
            </tr>
            <tr>
              <td>Postcode</td>
              <td>{{ optional($aRows->userDetails)->billing_postcode ?? '' }}</td>
            </tr>
            <tr>
              <td>Mobile</td>
              <td>{{ optional($aRows->userDetails)->billing_phone ?? '' }}</td>
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
            <strong>{{ __('Company Details') }}</strong>
        </div>
        <div class="card-body">
          <table class="table table-striped table-bordered">
            <tbody>
            <tr>
              <td style="width: 200px;">Company Name</td>
              <td>{{ $aRows->company_name }}</td>
            </tr>
            <tr>
              <td>Company Size</td>
              <td>{{ $aRows->company_size }}</td>
            </tr>
            <tr>
              <td>Company Sales Team</td>
              <td>{{ $aRows->company_sales_team == 1 ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
              <td>Company Website</td>
              <td>{{ $aRows->company_website }}</td>
            </tr>
            <!-- <tr>
              <td>Company Email</td>
              <td>{{ $aRows->company_email }}</td>
            </tr> -->
            <!-- <tr>
              <td>Mobile</td>
              <td>{{ $aRows->company_phone }}</td>
            </tr> -->
            <!-- <tr>
              <td>Company Location</td>
              <td>{{ $aRows->company_location }}</td>
            </tr>
            <tr>
              <td>Location Reason</td>
              <td>{{ $aRows->company_location_reason }}</td>
            </tr>
            <tr>
              <td>Total Years</td>
              <td>{{ $aRows->company_total_years }}</td>
            </tr>
            <tr>
              <td>About</td>
              <td>{{ $aRows->about_company }}</td>
            </tr> -->
            <!-- <tr>
              <td>Company Logo</td>
              <td>
                @if($aRows && $aRows->banner_image) 
                      <img src="{{ \App\Helpers\CustomHelper::displayImage($aRows->company_logo, 'users') }}" height="100" width="100" class="mt-2" />                
                    @endif
              </td>
            </tr> -->
          
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-6 col-sm-12">
      <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Other Details') }}</strong>
        </div>
        <div class="card-body">
          <table class="table table-striped table-bordered">
            <tbody>
            <tr>
              <td style="width: 200px;">Youtube Link</td>
              <td>{{ optional($aRows->userDetails)->company_youtube_link ?? '' }}</td>
            </tr>
            <tr>
              <td>Facebook Link</td>
              <td>{{ optional($aRows->userDetails)->fb_link ?? '' }}</td>
            </tr>
            <tr>
              <td>Twitter Link</td>
              <td>{{ optional($aRows->userDetails)->twitter_link ?? '' }}</td>
            </tr>
            <tr>
              <td>Other Link</td>
              <td>{{ optional($aRows->userDetails)->link_desc ?? '' }}</td>
            </tr>
            <tr>
              <td>Accreditations</td>
              <td><a href="{{ route('seller.sellerAccreditations',$aRows->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a></td>
            </tr>
            <tr>
              <td>Profile Services</td>
              <td><a href="{{ route('seller.sellerProfileServices',$aRows->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a></td>
            </tr>
            <!-- <tr>
              <td>Company Photos</td>
              <td>
                @php
                    $photos = explode(',', optional($aRows->userDetails)->company_photos);
                @endphp
                @if (!empty($photos))
                  @foreach ($photos as $photo)
                    
                          <img src="{{ \App\Helpers\CustomHelper::displayImage($photo, 'users') }}" height="100" width="100" class="mt-2 me-2" />
                  @endforeach
                @endif
              </td>
            </tr> -->
            </tbody>
          </table>
        </div>
      </div>
    </div> 
  </div> 
 
</x-app-layout>           