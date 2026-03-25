<x-app-layout>
  @section('title', 'Profile Questions')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __('Profile Questions') }}</h4>
    @can('profilequestions.create')
    <a href="{{ route('profilequestion.create') }}" class="btn btn-success text-white">
      <i class="fa fa-plus fa-xs"></i> {{ _('Add Questions') }}
    </a>
    @endcan
  </div>
  @include('layouts.alerts')
  <div class="card mb-4">
    <div class="card-body">
      <table class="table table-striped" id="dataTable">
        <thead>
          <tr>
            <th scope="col" width="20px;">S.No</th>
            <th scope="col">Questions</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @if(count($aRows) > 0)
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td>{{ $aRow->questions }}</td>
            <td>{{ $aRow->status == 1 ? 'Active' : 'Inactive' }}</td>
            <td>
              @can('profilequestions.edit')
              <a href="{{ route('profilequestion.edit',$aRow->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></a>
              @endcan
              @can('profilequestions.delete')
              <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i>
              </a>
              <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('profilequestion.destroy',$aRow->id) }}" method="post" style="display: none;">
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
            <td>No records found</td>
            <td></td>
          </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
  </div>
</x-app-layout>