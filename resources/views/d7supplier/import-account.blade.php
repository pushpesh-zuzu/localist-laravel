<x-app-layout>
  @section('title', 'Zoho Import')

  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0"></h4>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <h4 class="mb-0">{{ __("Zoho Account Import") }}</h4>

    <a href="{{ asset('samples/zoho_account_sample.csv') }}" class="btn btn-success text-white">
      <i class="fa fa-plus fa-xs"></i> {{ _('Download Sample File') }}
    </a>

  </div>

  {{-- Success Message --}}
  @if(session('success'))
  <div class="alert alert-success">
    {{ session('success') }}
  </div>
  @endif

  {{-- Error Message --}}
  @if(session('error'))
  <div class="alert alert-danger">
    {{ session('error') }}
  </div>
  @endif

  <div class="card">
    <div class="card-body">

      {{-- 🔽 Sample Download + Notes --}}
      <div class="mb-3">
        <div class="mt-2 text-muted" style="font-size: 14px;">
          <strong>Note:</strong>
          <ul class="mb-0 text-danger">
            <li><b>Name</b> field is mandatory</li>
            <li>File format must be <b>CSV or XLSX</b></li>
            <li>Do not change column headers</li>
          </ul>
        </div>
      </div>

      <form id="importForm" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label class="form-label">Upload Excel File</label>
          <input type="file" name="file" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">
          Import to Zoho
        </button>

      </form>

      <div id="importStatus" class="mt-3"></div>

    </div>
  </div>

</x-app-layout>

<script>
  $(document).ready(function () {

    $('#importForm').on('submit', function (e) {
        e.preventDefault();

        let form = $('#importForm')[0];
        let formData = new FormData(form);

        // 🔽 disable button
        $('#importBtn').prop('disabled', true).text('Importing...');

        $('#importStatus').html('<div class="text-info">Importing...</div>');

        $.ajax({
            url: "{{ route('zoho.import') }}",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,

            success: function (res) {
                $('#importStatus').html('<div class="text-success">Import completed</div>');
                
                form.reset();

                // 🔽 enable button
                $('#importBtn').prop('disabled', false).text('Import to Zoho');
            },

            error: function (err) {
                $('#importStatus').html('<div class="text-danger">Import failed</div>');

                // 🔽 enable button
                $('#importBtn').prop('disabled', false).text('Import to Zoho');
            }
        });
    });

});
</script>