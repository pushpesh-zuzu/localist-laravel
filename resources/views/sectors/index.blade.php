<x-app-layout>
    <x-slot name="header">{{ __('Sectors') }} </x-slot>
    <div class="row">
        <div class="md-col-12 mb-4">
            <a href="{{ route('sectors.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Sector') }}</a>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <table id="sectorsTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sectors</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sectorsList as $index => $sl)
                        <tr>
                            <td>
                                <b>{{$index+1}}.</b> 
                                <img src="{{ \App\Helpers\CustomHelper::displayImage($sl['category_icon'], 'category') }}" height="25" width="25" style="display: inline" /> &nbsp;
                                {{$sl['name']}}
                                
                            </td>
                            <td>@if($sl['status']) Active @else Inactive @endif </td>
                            <td>
                                <a href="{{route('sectors.edit',$sl['id'])}}" title="edit"><i class="fas fa-edit"></i></a> &nbsp;
                                <a href=""class="delete_category" data-id="{{$sl['id']}}" title="delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        {{\App\Helpers\CustomHelper::createSectorsRecursive($sl, $index+1)}}
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
  $('#sectorsTable').DataTable({
    paging: true,
    searching: true,
    info: true,
    ordering: false, // prevent breaking nested order
    dom: 'lfrtip', // length menu, filter, table, info, pagination
    rowCallback: function(row, data, index){
      // Keep styling on nested rows if needed
      if($(row).hasClass('child-row')){
        $(row).css('background-color', '#f9f9f9');
      }
    }
  });
});
        </script>
    @endpush
</x-app-layout> 