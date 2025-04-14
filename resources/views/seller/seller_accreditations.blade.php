<x-app-layout>
   
    <x-slot name="header">{{ 'Accreditations' . (!empty($user) ? " ($user)" : '') }}</x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Accreditations') }}</strong>
      </div>
      <div class="card-body">
        @if(count($aRows) > 0)
        <table class="table table-bordered" id="dataTable">
          <thead>
          <tr>
            <th scope="col" width="20px;">#</th>
            <th scope="col">Attachment</th>
            <th scope="col">Title</th>
          </tr>
          </thead>
          <tbody>
          @foreach($aRows as $aKey => $aRow)
          <tr>
            <th scope="row">{{ $aKey+1 }}</th>
            <td >{{ $aRow->name }}</td>
            <td class="text-center">
                @if (!empty($aRow->image))
                    @php
                        $file = $aRow->image;
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    @endphp
                    @if ($extension === 'pdf')
                        <a href="{{ \App\Helpers\CustomHelper::displayImage($file, 'accreditations') }}" target="_blank" class="btn btn-sm btn-primary mt-2">
                            Download PDF
                        </a>
                    @elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <img src="{{ \App\Helpers\CustomHelper::displayImage($file, 'accreditations') }}" height="100" width="100" class="mt-2 me-2" />
                    @endif
                @endif
            </td>
          </tr>
          @endforeach
          </tbody>
        </table>
        @else 
            No records found
        @endif
      </div>
    </div>
 
</x-app-layout>           