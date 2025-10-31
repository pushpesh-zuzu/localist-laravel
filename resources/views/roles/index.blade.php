<x-app-layout>

    <x-slot name="header">{{__("Roles")}} </x-slot>
      @include('layouts.alerts')
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Roles') }}</strong>
            @can('role.create')
            <a href="{{route('roles.create')}}" class="btn btn-info" style="float: right;margin-right:3px;"><i class="fa fa-plus fa-xs"></i> Add </a>
            @endcan
        </div>
        <div class="card-body">         
            <table id="roletable" class="table table-bordered table-striped">
                <thead>
                    <th>
                        #
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
            stateSave: true,
            ajax: url + '/roles',
            columns: [{
                    data: 'id',
                    name: 'id',
                    searchable: true,
                    orderable: true
                },
                {
                    data: 'name',
                    name: 'roles.name',
                    searchable: true,
                    orderable: true
                },
                {
                    data: 'action',
                    name: 'action',
                    searchable: false,
                    orderable: false
                }
            ],

            order: [
                [1, 'ASC']
            ]
        });

    });
</script>

<script>
    setTimeout(() => {
        document.getElementById('alertMsg')?.remove();
    }, 3000);
</script>