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
    <title>{{ optional($tenantSchool)->name ?? config('app.name') }}</title>
    <!-- Scripts -->
    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/vendor.js') }}"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/crest.css') }}" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600&family=Instrument+Serif&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-paper.css') }}?v={{ @filemtime(public_path('css/ob-paper.css')) }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-saas.css') }}?v={{ @filemtime(public_path('css/ob-saas.css')) }}" rel="stylesheet">
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
