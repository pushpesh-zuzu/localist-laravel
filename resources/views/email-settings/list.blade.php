<x-app-layout>
    <x-slot name="header">{{ __('Email Setting') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Email Setting') }}</strong>
          <a href="{{ route('email-settings.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Email Setting') }}</a>
      </div>
      <div class="card-body">
        @if(session()->has('success'))
        <div class="alert alert-success">{{ session()->get('success') }}</div>
        @endif
        @if(session()->has('error'))
        <div class="alert alert-danger">{{ session()->get('error') }}</div>
        @endif
        @php
            // echo "<pre>";
            // print_r($settings);
        @endphp
        <div class="row" style="margin-left:10%; margin-right:10%;">
            {{-- @if(in_array('Send Welcome Email', array_column($settings, 'setting_name')))
                @php
                    $s1 = collect($settings)->firstWhere('setting_name', 'Send Welcome Email')['setting_value'] ?? 0;
                @endphp
                <div class="d-flex justify-content-between align-items-center p-3">
                    <label class="form-check-label mb-0">Send Welcome Email</label>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="newLeadsSwitch" data-setting="Send Welcome Email" data-value="{{$s1}}" @if($s1) checked @endif>
                    </div>
                </div>
            @endif --}}
            
            @foreach($settings as $s)
                <div class="d-flex justify-content-between align-items-center p-3">
                    <label class="form-check-label mb-0">{{$s['setting_name']}}</label>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input toggle" type="checkbox" role="switch" data-id="{{$s['id']}}" data-setting="{{$s['setting_name']}}" data-value="{{$s['setting_value']}}" @if($s['setting_value']) checked @endif>
                    </div>
                </div>
            @endforeach
                                    
        </div>
      </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function () {
                $('.toggle').on('change', function (e) {
                    const $checkbox = $(this);
                    const id = $checkbox.data('id');
                    const settingName = $checkbox.data('setting');

                    // Get intended new value
                    const newValue = $checkbox.is(':checked') ? 1 : 0;

                    // Prevent toggle until confirmed
                    e.preventDefault();

                    $.ajax({
                        url: "{{ route('email-settings.change-status') }}",
                        type: 'POST',
                        data: {
                            id: id,
                            setting_name: settingName,
                            setting_value: newValue,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                        // Only toggle on success
                            $checkbox.prop('checked', newValue === 1);
                            console.log('Setting updated:', response);
                        },
                        error: function (xhr) {
                            // Revert the toggle
                            $checkbox.prop('checked', newValue !== 1);
                            console.error('Error updating setting:', xhr.responseText);
                        }
                    });

                    // Temporarily disable checkbox to prevent rapid toggle
                    $checkbox.prop('disabled', true);
                    setTimeout(() => $checkbox.prop('disabled', false), 500);
                });
            });
        </script>

    @endpush
</x-app-layout>           