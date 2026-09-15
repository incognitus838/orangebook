<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    @include('layouts.partials.favicon')
    <title>{{ optional($tenantSchool)->name ?? config('app.name', 'Crest') }}</title>
    <!-- Scripts -->
    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/vendor.js') }}"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/crest.css') }}" rel="stylesheet">
  </head>
  <body>
    <div id="app">
      <main>
        <div class="empty-layout flex items-center bg-gray-300 min-h-screen">
          @yield('content')
        </div>
      </main>
    </div>
  </body>



</html>
