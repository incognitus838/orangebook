<div class="ob-rail ob-rail-desktop parent-sidebar student-sidebar text-white">
  <div class="min-h-full header-wrapper-b hidden lg:block md:block">
   @include('layouts.parent.menu')
  </div>
</div>
<div id="res_sidebar" class="hidden res_sidebar" onclick="if(event.target===this)showsidebar('res_sidebar')">
  <div class="min-h-full header-wrapper-b ob-rail ob-rail-drawer parent-sidebar student-sidebar text-white" onclick="event.stopPropagation()">
   <button type="button" class="crest-rail-close ob-rail-close" onclick="showsidebar('res_sidebar')">Close</button>
   @include('layouts.parent.menu')
  </div>
</div>
