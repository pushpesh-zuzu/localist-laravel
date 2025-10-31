<x-app-layout>

    <x-slot name="header">{{__("Roles")}} </x-slot>
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Roles') }}</strong>
            <a href="{{route('roles.index')}}" class="btn btn-danger" style="float: right;"><i class="fa fa-arrow-left fa-xs"></i> {{ __('Back') }}</a>
        </div>
        <div class="card-body">
            <div class="card-body">
                <form action="{{ route('roles.update',$role->id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="form-group col-md-4">
                        <label for="name" class="text-dark mb-1">{{__("Role name")}} <span class="text-danger">*</span></label>
                        <input name="name" type="text" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="{{ __('Enter role name') }}" value="{{ $role->name }}" required autofocus>
                        <input type="hidden" name="guard" value="admin">
                        @error('name')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <p class="text-dark mt-3 mb-2"> <b>{{ __('Assign Permissions to role') }}</b> </p>
                        <table class="permissionTable table table-bordered">
                            <th>
                                {{__("Section")}}
                            </th>

                            <th>
                                <label>
                                    <input class="grand_selectall" type="checkbox">
                                    {{__('Select All') }}
                                </label>
                            </th>

                            <th>
                                {{__("Available permissions")}}
                            </th>

                            <tbody>
                                @foreach($custom_permission as $heading => $permissions)
                                @php
                                // Disable only if heading is "Manage Role"
                                $isManageRole = (strtolower($heading) === 'manage role');
                                @endphp
                                <tr>
                                    <td class="text-nowrap" style="min-width:200px"><b>{{ $heading }}</b></td>
                                    <td class="text-nowrap" style="min-width:200px">
                                        <label>
                                            <input class="selectall" type="checkbox" {{ $isManageRole ? 'disabled readonly' : '' }}>
                                            {{ __('Select All') }}
                                        </label>
                                    </td>
                                    <td>
                                        @forelse($permissions as $permission)
                                        @php
                                        $disabledPermissions = ['role.create', 'role.view', 'role.edit', 'role.delete'];
                                        $isDisabled = ($role->id == 1 && in_array($permission->name, $disabledPermissions));
                                        @endphp
                                        <label style="margin-right: 1rem;">
                                            <input
                                                name="permissions[]"
                                                type="checkbox"
                                                value="{{ $permission->id }}"
                                                {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}
                                                @if($isDisabled)
                                                onclick="return false;"
                                                @else
                                                class="permissioncheckbox"
                                                @endif>
                                            {{ $permission->title }}
                                        </label>
                                        @empty
                                        {{ __('No permission in this group!') }}
                                        @endforelse
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle"></i>
                            {{ __("Update")}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
<script src="{{ asset('coreui/js/permission.js') }}" type="text/javascript"></script>