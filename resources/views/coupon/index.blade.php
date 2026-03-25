<x-app-layout>
  @section('title', 'Coupons')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Coupons") }}</h4>
    @can('coupons.create')
    <a href="{{ route('coupon.create') }}" class="btn btn-success text-white">
      <i class="fa fa-plus fa-xs"></i> {{ _('Add Coupon') }}
    </a>
    @endcan
  </div>
  <div class="card mb-4">
    <div class="card-body">

      <table class="table table-striped" id="dataTable">
        <thead>
          <tr>
            <th scope="col" width="20px;">S.No</th>
            <th scope="col">Coupon</th>
            <th scope="col">Percentage</th>
            <th scope="col">Valid From</th>
            <th scope="col">Valid To</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @if(count($aRows) > 0)
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->coupon_code }}</td>
            <td>{{ $aRow->percentage }}</td>
            <td>{{ $aRow->valid_from }}</td>
            <td>{{ $aRow->valid_to }}</td>
            <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
            <td>
              @can('coupons.edit')
              <a href="{{ route('coupon.edit',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></a>
              @endcan
              @can('coupons.delete')
              <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i>
              </a>
              <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('coupon.destroy',$aRow->id) }}" method="post" style="display: none;">
                {{ method_field('DELETE') }}
                {{ csrf_field() }}
              </form>
              @endcan
            </td>
          </tr>
          @endforeach
          @else
          <tr>
            <td></td>
            <td></td>
            <td></td>
            <td>
              <p style="text-align:center">No records found</p>
            </td>
            <td></td>
            <td></td>
            <td></td>
          </tr>

          @endif

        </tbody>
      </table>

    </div>
  </div>

</x-app-layout>
<script>
  $(document).ready(function() {

    if ($.fn.DataTable.isDataTable('#dataTable')) {
      $('#dataTable').DataTable().destroy();
    }

    let table = $('#dataTable').DataTable({
      ordering: false,
      order: []
    });

    // 🔥 REMOVE sorting classes manually
    $('#dataTable thead th').removeClass('sorting sorting_asc sorting_desc');

  });
</script>