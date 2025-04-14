<x-app-layout>
    <x-slot name="header">{{ __('Lead Buyers (Complete List)') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Lead Buyers') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-striped" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Total Credit</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->name }}</td>
            <td>{{ $aRow->email }}</td>
            <td class="text text-center">{{ $aRow->total_credit }}</td>
            <!-- <td>{{ $aRow->user_type == 1 ? 'Seller' : 'Seller, Buyer' }}</td> -->
            <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
            <td>
              <a href="{{ route('seller.creditPlans',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Credit Plans"><i class="bi bi-list-task nav-icon"></i></a>
              <a href="{{ route('seller.sellerBids',$aRow->id) }}" class="text text-primary"><i class="fa-solid fa-chess-pawn" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Bids"></i></a>
              <a href="{{ route('seller.services',$aRow->id) }}" class="text text-primary" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Services"><i class="bi bi-person-lines-fill"></i></a>
              <a href="{{ route('seller.show',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="View"> <i class="bi bi-eye"></i></a>
              <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i>
                </a>
                <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('seller.destroy',$aRow->id) }}" method="post" style="display: none;">
                   {{ method_field('DELETE') }}
                   {{ csrf_field() }}
                       
                </form>

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