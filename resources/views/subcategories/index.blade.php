<x-app-layout>
    <x-slot name="header">{{ __('Sub Sector') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Sub Sector') }}</strong>
          <a href="{{ route('subcategories.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Sub Sector') }}</a>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-striped" id="dataTable">
          <thead>
          <tr>
          <th scope="col" width="20px;">#</th>
            <th scope="col">Parent Category</th>
            <th scope="col">Name</th>
            <th scope="col">Action</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->parent->name  }}</td>
            <td>{{ $aRow->name }}</td>
            <td>
                <a href="{{ route('subcategories.edit',$aRow->id) }}"><i class="icon  cil-pencil"></i></i></a>
                <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();"><i class="icon cil-trash"></i></i>
                </a>
                <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('subcategories.destroy',$aRow->id) }}" method="post" style="display: none;">
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