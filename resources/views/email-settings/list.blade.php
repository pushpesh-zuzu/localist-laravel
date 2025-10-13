<x-app-layout>
    <x-slot name="header">{{ __('Email Setting') }} </x-slot>

    <div class="row">
        <div class="md-col-12 mb-4">
            <a href="{{ route('email-settings.create') }}" class="btn btn-secondary btn-sm float-end">{{ _('Add Email Setting') }}</a>
        </div>
        <div class="md-col-12 mb-4">
            @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
            @endif
            @if(session()->has('error'))
                <div class="alert alert-danger">{{ session()->get('error') }}</div>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <table id="email-settings-table" class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('Setting Name') }}</th>
                        <th>{{ __('Email') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($settings as $s)
                        <tr>
                            <td>{{$s['setting_name']}}</td>
                            <td>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input toggle" type="checkbox" role="switch" data-id="{{$s['id']}}" data-setting="{{$s['setting_name']}}" data-value="{{$s['setting_value']}}" @if($s['setting_value']) checked @endif>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#email-settings-table').DataTable({
                    paging: true,
                    info: false,
                    pageLength: 50,
                    ordering: false, // prevent breaking nested order
                    dom: 'lfrtip', // length menu, filter, table, info, pagination
                });
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