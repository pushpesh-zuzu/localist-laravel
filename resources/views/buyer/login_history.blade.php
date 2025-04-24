<x-app-layout>
    <x-slot name="header">{{ 'Login History' . (!empty($user) ? " ($user)" : '') }} </x-slot>
  <div class="row">
    <div class="col-md-12 col-xl-12 col-sm-12">
        <div class="card mb-4">
          <div class="card-header">
              <strong>{{ __('Login History') }}</strong>
          </div>
          <div class="card-body">
            @if(count($aRows) > 0)
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Ip</th>
                            <th>Loggedin Device</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($aRows as $index => $aRow)
                            <tr>
                            <td style="text-align:center;">{{ $aRow->ip ?? '' }}</td>
                            <td style="text-align:center;">{{ $aRow->user_agent ?? '' }}</td>
                            <td style="text-align:center;">{{ $aRow->login_at ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else 
                No records found
            @endif
          </div>
        </div>
    </div>
   

  </div>
   
 
</x-app-layout>           