<x-app-layout>
    <x-slot name="header">{{ __('View Contact Form Details') }} </x-slot>
  <div class="row">
    <div class="col-md-12 col-xl-12 col-sm-12">
        <div class="card mb-4">
          <div class="card-header">
              <strong>{{ __('Personal Details') }}</strong>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4"><b>Name: </b> {{ $aRows->full_name ?? '' }}</div>

              <div class="col-md-4"><b>Mobile: </b> {{ $aRows->phone ?? '' }}</div>
              <div class="col-md-4"><b>Company: </b> {{ $aRows->company ?? '' }}</div>

            </div>

          </div>
        </div>
        <div class="card mb-4">
          <div class="card-header">
              <strong>{{ __('Message') }}</strong>
          </div>
          <div class="card-body">

            <div class="row">
                <div class="col-md-12">{{ $aRows->message ?? '' }}</div>
            </div>
          </div>
        </div>
    </div>
  </div>



</x-app-layout>
