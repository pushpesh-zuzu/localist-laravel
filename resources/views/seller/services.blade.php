<x-app-layout>
    <x-slot name="header">{{ __('User Services') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Services List') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-bordered" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col" width="200px;">Image</th>
            <th scope="col">Sector</th>
            <th scope="col">Leads</th>
            <th scope="col">Autobid</th>
            <th scope="col">Location</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>    @if($aRow && $aRow->banner_image) 
                        <img src="{{ \App\Helpers\CustomHelper::displayImage($aRow->banner_image, 'category') }}" height="100" width="100" class="mt-2" />        
                    @else
                        <img src="{{asset('/images/no_image.jpg')}}" height="100" width="100" class="mt-2" />       
                    @endif
            </td>
            <td >{{ $aRow->name }}</td>
            <td >
              @if(count($aRow->leadpref)>0)
                @foreach($aRow->leadpref as $leads)
                    <span class="fw-bold">Ques:</span> {{$leads['serquestions']['questions']}}<br/>
                    <span class="fw-bold">Soln:</span> {{$leads['answers']}}</br/>
                    @if (!$loop->last)
                        <hr>
                    @endif
                @endforeach
              @endif  
            </td>
            <td >{{ $aRow->autobid  == 1 ? 'Yes' : 'No'}}</td>
            <td >
              @if(count($aRow->locations)>0)
                @foreach($aRow->locations as $location)
                    <span class="fw-bold">Miles:</span> {{$location['miles']}}<br/>
                    <span class="fw-bold">Postcode:</span> {{$location['postcode']}}</br/>
                    <span class="fw-bold">Nation Wide:</span> {{ $location['nation_wide'] == 1 ? 'Yes' : 'No' }}
                    @if (!$loop->last)
                        <hr>
                    @endif
                @endforeach
              @endif  
            </td>
          </tr>
          @endforeach
          </tbody>
        </table>
        @else 
        No records found
        @endif
      </div>
    </div>
 
</x-app-layout>           