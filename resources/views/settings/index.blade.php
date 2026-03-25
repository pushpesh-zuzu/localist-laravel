<x-app-layout>
  @section('title', 'Settings')
  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Settings") }}</h4>
    @can('generalsettings.create')
    <a href="{{ route('settings.create') }}" class="btn btn-success text-white">
      <i class="fa fa-plus fa-xs"></i> {{ _('Add Setting') }}
    </a>
    @endcan
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

        @can('generalsettings.edit')
        <a href="{{ route('settings.edit',$s->id) }}" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Edit"><i class="icon  cil-pencil"></i></i></a>
        @endcan
        @can('generalsettings.delete')
        <form method='POST' onsubmit="return confirm('Are you sure to delete?');" action="{{ route('settings.destroy', $s->id) }}">
          @csrf
          @method('DELETE')
          <button style="color:red; margin-left:3px;" data-coreui-toggle="tooltip" data-coreui-placement="top" data-coreui-original-title="Delete"><i class="icon  cil-trash"></i></button>
        </form>
        @endcan
      </div>
    </div>

    @endforeach
  </div>

</x-app-layout>