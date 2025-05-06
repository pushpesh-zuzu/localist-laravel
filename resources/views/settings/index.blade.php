<x-app-layout>
    <x-slot name="header">{{ __('Settings') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Settings') }}</strong>
          <a href="{{ route('settings.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Setting') }}</a>
      </div>
      
      <div class="card-body">
      @if(session()->has('success'))
        <div class="alert alert-success">{{ session()->get('success') }}</div>
        @endif
        @if(session()->has('error'))
        <div class="alert alert-danger">{{ session()->get('error') }}</div>
        @endif
        @if(!empty($aRows) > 0)
        <table class="table table-striped table-bordered" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Total Bid </th>
            <th scope="col">Action</th>
          </tr>
          </thead>
          <tbody>
          <tr>
            <th scope="row">1</th>
            <td>{{ $aRows->total_bid }}</td>
            <td>
                <a href="{{ route('settings.edit',$aRows->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></i></a>
                <!-- <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon cil-trash"></i></i>
                </a>
                <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('settings.destroy',$aRows->id) }}" method="post" style="display: none;">
                   {{ method_field('DELETE') }}
                   {{ csrf_field() }}
                       
                </form> -->

            </td>
          </tr>
          </tbody>
        </table>
        @else 
        No records found
        @endif
      </div>
    </div>
 
</x-app-layout>           