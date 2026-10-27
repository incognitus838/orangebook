<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('layouts.partials.favicon')
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ optional($tenantSchool)->name ?? config('app.name') }}</title>
        <!-- Styles -->

        <!-- <link href="{{ asset('css/app.css') }}" rel="stylesheet"> -->
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
        <link href="{{ asset('css/crest.css') }}" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600&family=Instrument+Serif&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-paper.css') }}?v={{ @filemtime(public_path('css/ob-paper.css')) }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-saas.css') }}?v={{ @filemtime(public_path('css/ob-saas.css')) }}" rel="stylesheet">
        @php
            $obSchool = $tenantColor ?? '#1B2A4A';
            if (!is_string($obSchool) || !preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $obSchool)) {
                $obSchool = '#1B2A4A';
            }
        @endphp
        <style>:root { --ob-school: {{ $obSchool }}; --ob-stripe: {{ $obSchool }}; }</style>

         <script>
        window.User = {!! json_encode(optional(auth()->user())->only('id')) !!}
    </script>
   
    <!-- <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script> -->
    
    <!-- new -->
    <script>
       window.AppConfig = {
          gquiz_enabled: @json(config('gquiz.enabled')),
          gexam_enabled: @json(config('gexam.enabled')),
          ginventory_enabled: @json(config('ginventory.enabled')),
          gchat_enabled: @json(config('gchat.enabled')),
          gtransport_enabled: @json(config('gtransport.enabled')),
          gcertificate_enabled: @json(config('gcertificate.enabled')),
          gtimetable_enabled: @json(config('gtimetable.enabled')),
          gvideoroom_enabled: @json(config('gvideoroom.enabled')),
          galumni_enabled: @json(config('galumni.enabled')),
          gfee_enabled: @json(config('gfee.enabled'))
          
       };
    </script>
    <!-- end -->

 <livewire:styles>

    </head>
    <body class="ob-app font-primary antialiased min-h-screen overflow-x-hidden">
        <div id="app">
            @yield('base-navigation')
            <main class="flex w-full h-full min-h-screen ob-frame">
                <div class="sidebar min-h-full">
                    @yield('base-sidebar')
                </div>
                <div class="crest-app-main ob-main grow w-full px-4">
                    @yield('base-content')
                </div>
            </main>
            @yield('base-footer')
        </div>


        <!-- Scripts -->

        <script src="{{ mix('js/manifest.js') }}"></script>
        <script src="{{ mix('js/vendor.js') }}"></script>
        <script src="{{ mix('js/app.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}" ></script>
        <script>
        (function () {
            var h = document.querySelector('.crest-app-main h1, .ob-main h1');
            var t = h && h.textContent.replace(/\s+/g, ' ').trim();
            if (t && document.title.indexOf(t) !== 0) document.title = t + ' · ' + document.title;
        })();
        </script>
        @stack('scripts')

        <livewire:scripts>
            <!-- <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
            <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script src="{{ asset('vendor/livewire-alert/livewire-alert.js') }}"></script> 

        <script>
   window.addEventListener('alert', event => {
        toastr[event.detail.type](event.detail.message,
            event.detail.title ?? ''), toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": 'bottom-right',

            }
    });

   window.addEventListener('registeralert', event => {
        toastr[event.detail.type](event.detail.message,
            event.detail.title ?? ''), toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": 'bottom-right',

            }
    });
</script>

    </body>
    <style>
    .hide-menu {
    display: none;
}
</style>
    <style>
    .page-loading .loading {
  margin: auto;
  height: 100px;
  width: 100px;
  animation: spinner 1.5s linear infinite !important;
}
.page-loading .loading > div {
  height: inherit;
  width: inherit;
  position: absolute;
  animation-name: opacity;
  animation-duration: 1.1s;
  animation-timing-function: ease;
  animation-iteration-count: infinite;
  opacity: 0;
}
.page-loading .loading > div > div {
  height: 11px;
  width: 11px;
  border-radius: 50%;
  background: #9b2c2c;
  position: absolute;
  top: 0%;
  right: 50%;
  transform: translate(50%, 0%);
}
.page-loading .loading > div:nth-child(2) {
  transform: rotate(30deg);
  animation-delay: 0.1s;
}
.page-loading .loading > div:nth-child(3) {
  transform: rotate(60deg);
  animation-delay: 0.2s;
}
.page-loading .loading > div:nth-child(4) {
  transform: rotate(90deg);
  animation-delay: 0.3s;
}
.page-loading .loading > div:nth-child(5) {
  transform: rotate(120deg);
  animation-delay: 0.4s;
}
.page-loading .loading > div:nth-child(6) {
  transform: rotate(150deg);
  animation-delay: 0.5s;
}
.page-loading .loading > div:nth-child(7) {
  transform: rotate(180deg);
  animation-delay: 0.6s;
}
.page-loading .loading > div:nth-child(8) {
  transform: rotate(210deg);
  animation-delay: 0.7s;
}
.page-loading .loading > div:nth-child(9) {
  transform: rotate(240deg);
  animation-delay: 0.8s;
}
.page-loading .loading > div:nth-child(10) {
  transform: rotate(270deg);
  animation-delay: 0.9s;
}
.page-loading .loading > div:nth-child(11) {
  transform: rotate(300deg);
  animation-delay: 1s;
}
.page-loading .loading > div:nth-child(12) {
  transform: rotate(330deg);
  animation-delay: 1.1s;
}
@keyframes opacity {
  0% {
    opacity: 0.2;
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0.2;
  }
}
    </style>
</html>