<x-app-layout>
    <x-slot name="header">{{ __('Menus') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Menus') }}</strong>
          <a href="{{ route('menus.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Menu') }}</a>
      </div>
      <div class="card-body">
        @if(session()->has('success'))
        <div class="alert alert-success">{{ session()->get('success') }}</div>
        @endif
        @if(session()->has('error'))
        <div class="alert alert-danger">{{ session()->get('error') }}</div>
        @endif
        @if(count($aRows) > 0)
        <table class="table table-striped" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Menu Name</th>
            <th scope="col">Page</th>
            <th scope="col">Parent Menu</th>
            <th scope="col">Menu Slug</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->menu_name }}</td>
            <td>{{ $aRow->pages->page_title ?? '' }}</td>
            <td>{{ $aRow->parent->menu_name ?? '' }}</td>
            <td>{{ $aRow->menu_slug }}</td>
            <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
            <td>
                <a href="{{ route('menus.edit',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></i></a>
                <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i></i>
                </a>
                <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('menus.destroy',$aRow->id) }}" method="post" style="display: none;">
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