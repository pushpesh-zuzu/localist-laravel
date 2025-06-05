<x-app-layout>
    <x-slot name="header">{{ __('Settings') }} </x-slot>

    <div class="row">
      <div>
        <a href="{{ route('settings.create') }}" class="btn btn-secondary btn-sm float-end">Add Setting</a>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        @if(session()->has('success'))
          <div class="alert alert-success">{{ session()->get('success') }}</div>
        @endif
        @if(session()->has('error'))
          <div class="alert alert-danger">{{ session()->get('error') }}</div>
        @endif
      </div>
    </div>
    <div class="row mt-2">
      @foreach($settings as $s)
        <div class="col-md-6 mb-1 setting-item position-relative">
          <div class="card">
            <div class="card-body">
              <div>
                <strong>{{ucwords(str_replace('_', ' ', $s->setting_name))}}</strong> ({{$s->setting_name}})
              </div>
              <div>
                {{$s->setting_value}}
              </div>
              
            </div>
          </div>
          <!-- Hover buttons -->
          <div class="hover-buttons position-absolute top-0 end-0 p-2 d-none">
            <a href="{{ route('settings.edit',$s->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></i></a>
            <form method='POST' onsubmit="return confirm('Are you sure to delete?');" action="{{ route('settings.destroy', $s->id) }}">
                @csrf
                @method('DELETE')
                <button style="color:red; margin-left:3px;" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon  cil-trash"></i></button>
            </form>
          </div>
        </div>

      @endforeach
    </div>
 
</x-app-layout>           