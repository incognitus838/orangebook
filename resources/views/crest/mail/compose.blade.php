@extends($role === 'teacher' ? 'layouts.teacher.layout' : 'layouts.admin.layout')

@section('content')
<div>
    <h1 class="admin-h1 my-3">Write a circular</h1>
    @include('partials.message')
    <p class="text-sm mb-4" style="color:var(--crest-muted)">Only people in this school. Each parent gets their own copy. <a href="{{ $logUrl }}">Sent mail</a></p>

    <form method="POST" action="{{ $action }}" class="crest-form bg-white border p-4" style="border-color:var(--crest-rule);max-width:640px">
        @csrf
        <label for="template">Template</label>
        <select id="template">
            <option value="">Start from a blank circular</option>
            @foreach($templates as $key => $tpl)
                <option value="{{ $key }}" data-subject="{{ $tpl['subject'] }}" data-body="{{ $tpl['body'] }}">{{ $tpl['label'] }}</option>
            @endforeach
        </select>

        <label for="audience">To</label>
        <select id="audience" name="audience" required>
            @foreach($audiences as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <div id="class-wrap">
            <label for="class_id">Class</label>
            <select id="class_id" name="class_id">
                <option value="">Choose class</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->StandardSection }}</option>
                @endforeach
            </select>
        </div>

        <div id="parent-wrap" style="display:none">
            <label for="parent_id">Parent</label>
            <select id="parent_id" name="parent_id">
                <option value="">Choose parent</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }} · {{ $parent->email }}</option>
                @endforeach
            </select>
        </div>

        <p id="count-line" class="text-sm" style="color:var(--crest-muted);margin:12px 0 0">This will go to — people.</p>

        <label for="subject">Subject</label>
        <input id="subject" name="subject" required maxlength="180">

        <label for="body">Message</label>
        <textarea id="body" name="body" rows="10" required maxlength="5000" style="width:100%;border:0;border-bottom:1px solid var(--crest-rule);font-family:var(--crest-font);font-size:16px;padding:8px 0;background:transparent"></textarea>
        <p class="text-xs" style="color:var(--crest-muted)">You can use {parent_name}, {student_name}, {class}, {school_name}.</p>

        <button class="crest-btn" type="submit" style="margin-top:24px">Send</button>
    </form>
</div>
<script>
(function () {
  var templates = document.getElementById('template');
  var audience = document.getElementById('audience');
  var classWrap = document.getElementById('class-wrap');
  var parentWrap = document.getElementById('parent-wrap');
  var countLine = document.getElementById('count-line');
  var countUrl = @json($countUrl);

  templates.addEventListener('change', function () {
    var opt = templates.options[templates.selectedIndex];
    if (!opt.value) return;
    document.getElementById('subject').value = opt.getAttribute('data-subject');
    document.getElementById('body').value = opt.getAttribute('data-body');
  });

  function toggle() {
    var v = audience.value;
    classWrap.style.display = (v === 'class') ? 'block' : 'none';
    parentWrap.style.display = (v === 'parent') ? 'block' : 'none';
    refreshCount();
  }

  function refreshCount() {
    var params = new URLSearchParams({
      audience: audience.value,
      class_id: document.getElementById('class_id').value,
      parent_id: document.getElementById('parent_id').value
    });
    fetch(countUrl + '?' + params.toString(), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        countLine.textContent = 'This will go to ' + data.count + ' ' + (data.count === 1 ? 'person' : 'people') + '.';
      })
      .catch(function () {});
  }

  audience.addEventListener('change', toggle);
  document.getElementById('class_id').addEventListener('change', refreshCount);
  document.getElementById('parent_id').addEventListener('change', refreshCount);
  toggle();
})();
</script>
@endsection
