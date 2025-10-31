<x-app-layout>
  <x-slot name="header">
    @if($aRow)
    {{ __('Update User') }}
    @else
    {{ __('Add User') }}
    @endif
  </x-slot>

  <div class="card mb-4">
    <div class="card-header">
      <strong>
        @if($aRow)
        {{ __('Update User') }}
        @else
        {{ __('Add User') }}
        @endif
      </strong>
    </div>

    <div class="card-body">
      @if($aRow)
      <form method="POST" action="{{ route('admin-users.update', $aRow->id) }}">
        @method('PUT')
        @else
        <form method="POST" action="{{ route('admin-users.store') }}">
          @endif
          @csrf

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Name') }} <span class="text-danger">*</span></label>
              <input type="text" id="name" name="name"
                class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                value="{{ old('name', $aRow->name ?? '') }}" required placeholder="Enter name">
              @error('name')
              <span class="invalid-feedback d-block">{{ $message }}</span>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="email">{{ __('Email') }} <span class="text-danger">*</span></label>
              <input type="email" id="email" name="email"
                class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                value="{{ old('email', $aRow->email ?? '') }}" required placeholder="Enter email">
              @error('email')
              <span class="invalid-feedback d-block">{{ $message }}</span>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="password">
                {{ $aRow ? __('New Password (leave blank to keep same)') : __('Password') }}
              </label>
              <input type="password" id="password" name="password"
                class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                {{ $aRow ? '' : 'required' }} placeholder="Enter password">
              @error('password')
              <span class="invalid-feedback d-block">{{ $message }}</span>
              @enderror
            </div>


            <div class="col-md-6">
              <label class="form-label" for="role_id">{{ __('Select Role') }} <span class="text-danger">*</span></label>
              <select id="role_id" name="role_id"
                class="form-control {{ $errors->has('role_id') ? 'is-invalid' : '' }}" required>
                <option value="">-- Select Role --</option>
                @foreach($roles as $id => $name)
                <option value="{{ $id }}" {{ (old('role_id', $aRow->role_id ?? '') == $id) ? 'selected' : '' }}>
                  {{ $name }}
                </option>
                @endforeach
              </select>
              @error('role_id')
              <span class="invalid-feedback d-block">{{ $message }}</span>
              @enderror
            </div>



          </div>



          <button type="submit" class="btn btn-dark mt-4">
            @if($aRow) {{ __('Update') }} @else {{ __('Save') }} @endif
          </button>
        </form>
    </div>
  </div>
</x-app-layout>