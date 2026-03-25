<x-app-layout>
  @section('title', 'Blogs')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Blogs") }}</h4>
    @can('blog.create')
    <a href="{{ route('blogs.create') }}" class="btn btn-success text-white">
      <i class="fa fa-plus fa-xs"></i> {{ _('Add Blog') }}
    </a>
    @endcan
  </div>
  @include('layouts.alerts')
  <div class="card mb-4">
    <div class="card-body">

      <table class="table table-striped" id="dataTable">
        <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Name</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @if(count($aRows) > 0)
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->name }}</td>
            <td>
              @can('blog.edit')
              <a href="{{ route('blogs.edit',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></i></a>
              @endcan
              @can('blog.delete')
              <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i></i>
              </a>
              <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('blogs.destroy',$aRow->id) }}" method="post" style="display: none;">
                {{ method_field('DELETE') }}
                {{ csrf_field() }}

              </form>
              @endcan

            </td>
          </tr>
          @endforeach
          @else
          <tr>
            <td>
            <td></td>
            <td>No records found</td>
            </td>
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