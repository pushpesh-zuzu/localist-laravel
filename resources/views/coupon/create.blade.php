<x-app-layout>
    <x-slot name="header">@if($aRow) {{ __('Update Coupons') }} @else {{ __('Add Coupons') }} @endif  </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>@if($aRow) {{ __('Update Coupons') }} @else {{ __('Add Coupons') }} @endif </strong>
      </div>
      <div class="card-body">
          @if($aRow)
            <form method="POST"  action="{{ route('coupon.update',$aRow->id) }}" enctype="multipart/form-data">
          @method('PUT')
          @else
            <form method="POST"  action="{{ route('coupon.store') }}" enctype="multipart/form-data">
          @endif 

          @csrf
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Coupon Code') }}</label>
              <div class="input-group">
              @if(!$aRow)
                    <input type="text" id="coupon_code" class="form-control" name="coupon_code" value="{{ $aRow ? $aRow->coupon_code : old('coupon_code') }}" required placeholder="Enter code">
                    <button type="button" class="btn btn-outline-secondary" onclick="generateCouponCode()">Generate</button>
                @else
                    <input type="text" id="coupon_code" class="form-control" name="coupon_code" value="{{ $aRow ? $aRow->coupon_code : old('coupon_code') }}" required placeholder="Enter code" disabled>
                @endif
              </div>
              @if ($errors->has('coupon_code'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('coupon_code') }}</strong>
              </span>
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Percentage(%)') }}</label>
              <input type="number" id="percentage" class="form-control" name="percentage" class="form-control{{ $errors->has('percentage') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->percentage : old('percentage') }}" required placeholder="">
              @if ($errors->has('percentage'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('percentage') }}</strong>
              </span>
              @endif
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Valid From') }}</label>
              <input type="date" id="valid_from" class="form-control" name="valid_from" class="form-control{{ $errors->has('valid_from') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->valid_from : old('valid_from') }}" required>
              @if ($errors->has('valid_from'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('valid_from') }}</strong>
              </span>
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Valid Upto') }}</label>
              <input type="date" id="valid_to" class="form-control" name="valid_to" class="form-control{{ $errors->has('valid_to') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->valid_to : old('valid_to') }}" required>
              @if ($errors->has('valid_to'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('valid_to') }}</strong>
              </span>
              @endif
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Coupon Limit') }}</label>
              <input type="text" id="coupon_limit" class="form-control" name="coupon_limit" class="form-control{{ $errors->has('coupon_limit') ? ' is-invalid' : '' }}" value="{{ $aRow ? $aRow->coupon_limit : old('coupon_limit') }}" required placeholder="">
              @if ($errors->has('coupon_limit'))
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $errors->first('coupon_limit') }}</strong>
              </span>
              @endif
            </div>
          </div>
          <button type="submit" class="btn btn-dark mt-4">@if($aRow) Update @else Save @endif </button>
          </form>
      </div>
    </div>
 
</x-app-layout>           