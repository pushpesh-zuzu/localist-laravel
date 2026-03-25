<x-app-layout>
    @section('title', 'Roles')
    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
        <h4 class="mb-0">{{ __("Roles") }}</h4>
        @can('role.create')
        <a href="{{ route('roles.create') }}" class="btn btn-success text-white">
            <i class="fa fa-plus fa-xs"></i> Add
        </a>
        @endcan
    </div>
    @include('layouts.alerts')
    <div class="card mb-4">
        <div class="card-body">
            <table id="roletable" class="table table-bordered table-striped">
                <thead>
                    <th>
                        S.No
                    </th>
                    <th>
                        {{__("Role Name")}}
                    </th>
                    <th>
                        {{__('Action')}}
                    </th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
<script>
    $(document).ready(function() {
        var url = $("#_url").val();
        var table = $('#roletable').DataTable({
            lengthChange: false,
            responsive: true,
            serverSide: true,
            autoWidth: true,
            paging: false,
            searching: false,
            info: false,
            lengthChange: false,
            ordering: false,
            order: [],
            ajax: url + '/roles',
            columns: [{
                    data: null,
                    name: 'sno',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'name',
                    name: 'roles.name',
                },
                {
                    data: 'action',
                    name: 'action',
                    searchable: false,
                    orderable: false
                }
            ],

            order: [
                [0, 'asc']
            ]
        });

    });
</script>

<script>
    setTimeout(() => {
        document.getElementById('alertMsg')?.remove();
    }, 3000);
</script>