<x-app-layout>
  <x-slot name="header">{{ __("Admin Users") }} </x-slot>
  @include('layouts.alerts')
  <div class="card mb-4">
    <div class="card-header">
      <strong>{{ __("Admin Users") }}</strong>
      @can('adminuser.create')
      <a href="{{route('admin-users.create')}}" class="btn btn-info" style="float: right;margin-right:3px;"><i class="fa fa-plus fa-xs"></i> Add </a>
      @endcan
    </div>
    <div class="card-body">
      @if(count($admins) > 0)
      <table class="table table-striped" id="dataTable">
        <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">User Role</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($admins as $admin)
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