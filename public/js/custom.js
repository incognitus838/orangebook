

$(document).ready(function(){
  $(".profile-click").click(function(){
    $(".user-dtl").toggle();
  });
});


  function show() {
    if($('.create_event').hasclass('hidden'))
    {
      $('.create_event').removeclass('hidden').addclass('block');
    }
    else 
    {
      $('.create_event').removeclass('block').addclass('hidden');
    }
  }


function showsidebar(id){
  var el = document.getElementById(id);
  if (!el) return;
  var open = el.classList.contains('is-open') || (el.classList.contains('block') && !el.classList.contains('hidden'));
  if (open) {
    el.classList.remove('is-open', 'block');
    el.classList.add('hidden');
    document.body.classList.remove('crest-rail-open');
  } else {
    el.classList.add('is-open', 'block');
    el.classList.remove('hidden');
    document.body.classList.add('crest-rail-open');
  }
}
window.showsidebar = showsidebar;
