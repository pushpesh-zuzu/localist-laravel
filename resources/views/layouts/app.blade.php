<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title> @isset($header) {{ $header }} | @endisset{{ config('app.name', 'Laravel') }}</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Scripts -->
            @vite(['resources/css/app.css', 'resources/js/app.js']) 

        <link rel="stylesheet" href="{{ asset('coreui/node_modules/simplebar/dist/simplebar.css') }}">
        <link rel="stylesheet" href="{{ asset('coreui/css/simplebar.css') }}">
        <!-- Main styles for this application-->
        <link href="{{ asset('coreui/css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('coreui/css/examples.css') }}" rel="stylesheet">
        <script src="{{ asset('coreui/js/config.js') }}"></script>
        <script src="{{ asset('coreui/js/color-modes.js') }}"></script>
        <link href="{{ asset('coreui/node_modules/@coreui/chartjs/dist/css/coreui-chartjs.css') }}" rel="stylesheet">  
        <link href="{{ asset('coreui/node_modules/@coreui/icons/css/free.min.css') }}" rel="stylesheet"> 
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"  />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

        <!-- DataTables with Bootstrap 5 styling -->
        <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">

        <style>
          th.dtr-control:before {
              content: "-";
              background-color: #d33333;
          }
        </style>
        <style>
          .setting-item:hover .hover-buttons {
            display: flex !important;
            margin-right:20px;
          }
        </style>
        
  </head>
  <body>
  @include('layouts.left')  

    <div class="wrapper d-flex flex-column min-vh-100">
    @include('layouts.navigation')
      
      <div class="body flex-grow-1">
        <div class="container-fluid px-4">
            @isset($header)
                <h2 class="mb-4">{{ $header }}</h2>
            @endisset
            {{ $slot }}
        </div>
      </div>
      <footer class="footer px-4">

      </footer>
    </div>
    <input type="hidden" id="_url" value="{{url('/')}}">
    <!-- CoreUI and necessary plugins-->
    <script src="{{ asset('coreui/node_modules/@coreui/coreui/dist/js/coreui.bundle.min.js') }}"></script>
    <script src="{{ asset('coreui/node_modules/simplebar/dist/simplebar.min.js') }}"></script>
    <script>
      const header = document.querySelector('header.header');
      
      document.addEventListener('scroll', () => {
        if (header) {
          header.classList.toggle('shadow-sm', document.documentElement.scrollTop > 0);
        }
      });
      
    </script>
    <!-- Plugins and scripts required by this view-->
    <script src="{{ asset('coreui/node_modules/chart.js/dist/chart.umd.js') }}"></script>
    <script src="{{ asset('coreui/node_modules/@coreui/chartjs/dist/js/coreui-chartjs.js') }}"></script>
    <script src="{{ asset('coreui/node_modules/@coreui/utils/dist/umd/index.js') }}"></script>
   
    
    <style>
      .dt-input
      {
        appearance: auto!important;
        background: none;
      }
      </style>
      <script>
        function generateCouponCode(length = 8) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < length; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('coupon_code').value = code;
        }
        const tooltipTriggerList = document.querySelectorAll('[data-coreui-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new coreui.Tooltip(tooltipTriggerEl))
      </script>

      <!-- DataTables core -->
      <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
      <!-- DataTables Responsive -->
      <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
      <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
      <!-- DataTables Buttons -->
      <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
      <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
      <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
      <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
      <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
      <!-- JSZip and pdfmake for Excel/PDF buttons -->
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
      <script> 
        let table = new DataTable('#dataTable');
      </script>

  @stack('scripts')
  </body>
</html>