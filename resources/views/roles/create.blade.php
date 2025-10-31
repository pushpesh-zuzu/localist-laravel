<x-app-layout>
    <x-slot name="header">{{__("Create a new role")}} </x-slot>
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Create a new role') }}</strong>
             <a href="{{route('roles.index')}}" class="btn btn-danger" style="float: right;"><i class="fa fa-arrow-left fa-xs"></i> {{ __('Back') }}</a>
        </div>
        <form action="{{ route('roles.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 col-sm-6">
                        <div class="form-group">
                            <label for="name" class="text-dark mb-1">{{__('Role name')}} <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="{{ __('Enter role name') }}" value="{{ old('name') }}" required autofocus>
                            <input type="hidden" name="guard" value="admin">
                            @error('name')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12">
                        <div class="form-group table-responsive mt-4 mb-0">
                            <p class="text-dark"> <b>{{ __('Assign Permissions to role') }}</b> </p>
                            <table class="permissionTable table overflow-x-auto">
                                <th class="text-nowrap" style="min-width:200px">
                                    {{__('Section')}}
                                </th>
                                <th class="text-nowrap">
                                    <label>
                                        <input class="grand_selectall" type="checkbox">
                                        {{__('Select All') }}
                                    </label>
                                </th>
                                <th class="text-nowrap" style="min-width:300px">
                                    {{__("Available permissions")}}
                                </th>
                                <tbody>
                                    @foreach($custom_permission as $heading => $permissions)
                                    <tr>
                                        <td>
                                            <b>{{ $heading }}</b>
                                        </td>
                                        <td>
                                            <label>
                                                <input class="selectall" type="checkbox">
                                                {{ __('Select All') }}
                                            </label>
                                        </td>
                                        <td>
                                            @forelse($permissions as $permission)
                                            <label style="margin-right: 1rem;">
                                                <input
                                                    name="permissions[]"
                                                    class="permissioncheckbox"
                                                    type="checkbox"
                                                    value="{{ $permission->id }}">
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
                        <div class="form-group text-center">
                            <button type="reset" class="btn btn-danger mr-1"><i class="fa fa-ban"></i> {{ __("Reset")}}</button>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle"></i>
                                {{ __("Create")}}</button>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</x-app-layout>
<script src="{{ asset('coreui/js/permission.js') }}" type="text/javascript"></script>