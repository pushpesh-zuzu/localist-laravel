<x-app-layout>
    <x-slot name="header">{{ __('Seller Information') }} </x-slot>
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
              <td style="width: 180px;">Name</td>
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
              <td>Country</td>
              <td>{{ $aRows->country }}</td>
            </tr>
            
            <tr>
              <td>Zipcode</td>
              <td>{{ $aRows->zipcode }}</td>
            </tr>
            <tr>
              <td>Building/ House name</td>
              <td>{{ $aRows->apartment }}</td>
            </tr>
            <tr>
              <td>Registration Date</td>
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
             <tr>
              <td>Street Address</td>
              <td>{{ $aRows->address }}</td>
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
            <tr>
              <td>Company Email</td>
              <td>{{ !empty($aRows->company_email) ? $aRows->company_email : 'N/A' }}</td>
            </tr>
            <tr>
              <td>Company Phone</td>
              <td>{{ !empty($aRows->company_phone) ? $aRows->company_phone : 'N/A' }} </td>
            </tr>
            <tr>
              <td>Business Location</td>
              <td>{{!empty($aRows->company_location) ? $aRows->company_location : 'N/A' }} </td>
            </tr>
            <tr>
              <td>Business Location Reason</td>
              <td>{{!empty($aRows->company_location_reason) ? $aRows->company_location_reason : 'N/A' }} </td>
            </tr>
            <tr>
              <td>Years In Business</td>
              <td>{{!empty($aRows->company_total_years) ? $aRows->company_total_years : 'N/A' }} </td>
            </tr>
            <tr>
              <td>Company Description</td>
              <td>{{!empty($aRows->about_company) ? $aRows->about_company : 'N/A' }} </td>
            </tr>
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
              <td>{{ optional($aRows->userDetails)->company_youtube_link ?? 'N/A' }}</td>
            </tr>
            <tr>
              <td>Facebook</td>
              <td>{{ optional($aRows->userDetails)->fb_link ?? 'N/A' }}</td>
            </tr>
            <tr>
              <td>Twitter</td>
              <td>{{ optional($aRows->userDetails)->twitter_link ?? 'N/A' }}</td>
            </tr>
            <tr>
              <td>TikTok</td>
              <td>{{ optional($aRows->userDetails)->tiktok_link ?? 'N/A' }}</td>
            </tr>
            <tr>
              <td>Instagram</td>
              <td>{{ optional($aRows->userDetails)->insta_link ?? 'N/A' }}</td>
            </tr>
            <tr>
              <td>Linkedin</td>
              <td>{{ optional($aRows->userDetails)->linkedin_link ?? 'N/A' }}</td>
            </tr>
            <tr>
              <td>Extra Links</td>
              <td>{{ optional($aRows->userDetails)->extra_links ?? 'N/A' }}</td>
            </tr>
            
            <tr>
              <td>Accreditations</td>
              <td><a href="{{ route('seller.sellerAccreditations',$aRows->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a></td>
            </tr>
            <tr>
              <td>Services</td>
              <td><a href="{{ route('seller.services',$aRows->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a></td>
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