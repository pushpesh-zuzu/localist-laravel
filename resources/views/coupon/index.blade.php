<x-app-layout>
    <x-slot name="header">{{ __('Coupons') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Coupon') }}</strong>
          <a href="{{ route('coupon.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Coupon') }}</a>
      </div>
      <div class="card-body">
      @if(count($aRows) > 0)
        <table class="table table-striped" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Coupon</th>
            <th scope="col">Percentage</th>
            <th scope="col">Valid From</th>
            <th scope="col">Valid To</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
          </thead>
          <tbody>
         
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->coupon_code }}</td>
            <td>{{ $aRow->percentage }}</td>
            <td>{{ $aRow->valid_from }}</td>
            <td>{{ $aRow->valid_to }}</td>
            <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
            <td>
                <a href="{{ route('coupon.edit',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></a>
                <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i>
                </a>
                <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('coupon.destroy',$aRow->id) }}" method="post" style="display: none;">
                   {{ method_field('DELETE') }}
                   {{ csrf_field() }}
                </form>
            </td>
          </tr>
          @endforeach
         
          </tbody>
        </table>
        @else 
                <p style="text-align:center">No records found</p>
           
            
          @endif
      </div>
    </div>
 
</x-app-layout>           