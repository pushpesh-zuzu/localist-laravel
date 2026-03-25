<x-app-layout>
    @section('title', 'Sectors')
    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
        <h4 class="mb-0">{{ __("Sectors") }}</h4>
        @can('sector.create')
        <a href="{{ route('sectors.create') }}" class="btn btn-success text-white">
            <i class="fa fa-plus fa-xs"></i> {{ _('Add Sector') }}
        </a>
        @endcan
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
                            @can('sector.edit')
                            <a href="{{route('sectors.edit',$sl['id'])}}" title="edit"><i class="fas fa-edit"></i></a> &nbsp;
                            @endcan
                            @can('sector.delete')
                            <a href="javascript:void(0);" onclick="jQuery(this).parent('td').find('#delete-form').submit();" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete">
                                <i class="fas fa-trash"></i></i>
                            </a>
                            <form id="delete-form" onsubmit="return confirm('Are you sure to delete?');" action="{{ route('sectors.destroy',$sl['id']) }}" method="post" style="display: none;">
                                {{ method_field('DELETE') }}
                                {{ csrf_field() }}
                            </form>
                            @endcan
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
        $(document).ready(function() {
            $('#sectorsTable').DataTable({
                paging: true,
                searching: true,
                info: true,
                pageLength: 50,
                ordering: false, // prevent breaking nested order
                dom: 'lfrtip', // length menu, filter, table, info, pagination
                rowCallback: function(row, data, index) {
                    // Keep styling on nested rows if needed
                    if ($(row).hasClass('child-row')) {
                        $(row).css('background-color', '#f9f9f9');
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>