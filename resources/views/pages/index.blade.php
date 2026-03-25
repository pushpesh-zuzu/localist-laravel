<x-app-layout>
  @section('title', 'Pages')

  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Pages") }}</h4>
    @can('page.create')
    <a href="{{ route('pages.create') }}" class="btn btn-success text-white">
      <i class="fa fa-plus fa-xs"></i> {{ _('Add Pages') }}
    </a>
    @endcan
  </div>


  <div class="card mb-4">
    <div class="card-body">
      @if(count($aRows) > 0)
      <table class="table table-striped" id="dataTable">
        <thead>
          <tr>
            <th scope="col" width="20px;">S.No</th>
            <th scope="col">Page Title</th>
            <th scope="col">Type</th>
            <th scope="col">Slug</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->page_title }}</td>
            <td>@if($aRow->page_type == 1) {{'Page'}} @else {{'Category'}} @endif </td>
            <td>{{ $aRow->slug }}</td>
            <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
            <td>
              @can('page.edit')
              <a href="{{ route('pages.edit',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></i></a>
              @endcan
              @can('page.delete')
              <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i></i>
              </a>
              <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('pages.destroy',$aRow->id) }}" method="post" style="display: none;">
                {{ method_field('DELETE') }}
                {{ csrf_field() }}

              </form>
              @endcan
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