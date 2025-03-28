<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <!-- Vendors styles-->
        <link rel="stylesheet" href="{{ asset('coreui/node_modules/simplebar/dist/simplebar.css') }}">
        <link rel="stylesheet" href="{{ asset('coreui/css/simplebar.css') }}">
        <!-- Main styles for this application-->
        <link href="{{ asset('coreui/css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('coreui/css/examples.css') }}" rel="stylesheet">
        <script src="{{ asset('coreui/js/config.js') }}"></script>
        <script src="{{ asset('coreui/js/color-modes.js') }}"></script>
        
  </head>
  <body>
  <div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-md-6 bg-primary">
          <div class="d-flex align-items-center justify-content-center vh-100">
              {{-- <img src="{{ asset('coreui/assets/brand/logo.png') }}"  /> --}}
              <h3 class="text-white">{{ config('app.name', 'Laravel') }} Login </h3>
          </div>

           
        </div>
        <div class="col-md-6 bg-white  min-vh-100">
          <div class="d-flex align-items-center justify-content-center vh-100">
            {{ $slot }}    
          </div>               
        </div> 
      </div>
    </div>
  </div>

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

  </body>
</html>