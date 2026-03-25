<x-app-layout>
  @section('title', 'Admin Users')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Admin Users") }}</h4>
    @can('adminuser.create')
    <a href="{{ route('admin-users.create') }}" class="btn btn-success text-white">
      <i class="fa fa-plus fa-xs"></i> Add
    </a>
    @endcan
  </div>
  @include('layouts.alerts')
  <div class="card mb-4">
    <div class="card-body">
      @if(count($admins) > 0)
      <table class="table table-bordered table-striped" id="dataTable">
        <thead class="premium-thead">
          <tr>
            <th scope="col" width="20px;">S.No</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">User Role</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($admins as $admin)

          @if(auth()->user()->role_id != 7 && $admin->role_id == 7)
          @continue
          @endif
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $admin->name }}</td>
            <td>{{ $admin->email }}</td>
            <td>{{ $admin->role->name ?? '—' }}</td>
            <td>
              @if($admin->id != 1)
              @can('adminuser.edit')
              <a href="{{ route('admin-users.edit', $admin->id) }}" class="btn btn-sm btn-info">Edit</a>
              @endcan

              @can('adminuser.delete')
              <form method="POST" action="{{ route('admin-users.destroy', $admin->id) }}" class="d-inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger"
                  onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
              </form>
              @endcan
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
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
      paging: false,
      searching: false,
      info: false,
      lengthChange: false,
      ordering: false,
      order: []
    });

    // 🔥 REMOVE sorting classes manually
    $('#dataTable thead th').removeClass('sorting sorting_asc sorting_desc');

  });
</script>