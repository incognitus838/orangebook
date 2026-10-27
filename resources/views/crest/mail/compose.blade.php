@extends($role === 'teacher' ? 'layouts.teacher.layout' : 'layouts.admin.layout')

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Write a circular</h1>
        <a href="{{ $logUrl }}">Sent mail</a>
    </div>
    @include('partials.message')
    <p class="ob-help">
        Only people in this {{ strtolower(optional(Auth::user()->school)->orgWord() ?? 'organisation') }}. They will also see it on the notice board.
    </p>

    <form method="POST" action="{{ $action }}" class="ob-form">
        @csrf
        <label for="template">Template</label>
        <select id="template">
            <option value="">Start from a blank circular</option>
            @foreach($templates as $key => $tpl)
                <option value="{{ $key }}" data-subject="{{ $tpl['subject'] }}" data-body="{{ $tpl['body'] }}">{{ $tpl['label'] }}</option>
            @endforeach
        </select>

        @if(!empty($fullTarget))
            @include('crest.board._target')
        @else
        <label for="audience">To</label>
        <select id="audience" name="audience" required>
            @foreach($audiences ?? [] as $value => $label)
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
                @foreach($parents ?? [] as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }} · {{ $parent->email }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <label for="subject">Subject</label>
        <input id="subject" name="subject" required maxlength="180">

        <label for="body">Message</label>
        <textarea id="body" name="body" rows="6" required maxlength="5000"></textarea>

        <button class="crest-btn" type="submit">Send</button>
    </form>

    @if(isset($mailReady) && !$mailReady)
        <p class="ob-help" style="margin-top:16px">Circulars are kept on this list. They go out by email when mail is switched on.</p>
    @endif

    @if(!empty($recent) && $recent->count())
        <div class="ob-sent mt-8">
            <h2 class="font-semibold mb-2">Recently sent</h2>
            <table class="crest-table">
                <thead>
                    <tr><th>When</th><th>To</th><th>Subject</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($recent as $row)
                        @php
                            $st = strtolower((string) $row->status);
                            $stLabel = $st === 'failed' ? 'Not sent' : ($st === 'delivered' ? 'Sent' : ucfirst((string) $row->status));
                        @endphp
                        <tr>
                            <td>{{ optional($row->fired_at)->format('d M H:i') ?: $row->created_at->format('d M H:i') }}</td>
                            <td>{{ $row->to }}</td>
                            <td>{{ $row->subject }}</td>
                            <td>{{ $stLabel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-sm mt-2"><a href="{{ $logUrl }}">Full sent list</a></p>
        </div>
    @endif
</div>
<script>
(function () {
  var templates = document.getElementById('template');
  if (templates) {
    templates.addEventListener('change', function () {
      var opt = templates.options[templates.selectedIndex];
      if (!opt.value) return;
      document.getElementById('subject').value = opt.getAttribute('data-subject');
      document.getElementById('body').value = opt.getAttribute('data-body');
    });
  }
  var audience = document.getElementById('audience');
  if (!audience) return;
  var classWrap = document.getElementById('class-wrap');
  var parentWrap = document.getElementById('parent-wrap');
  function toggle() {
    var v = audience.value;
    if (classWrap) classWrap.style.display = (v === 'class') ? 'block' : 'none';
    if (parentWrap) parentWrap.style.display = (v === 'parent') ? 'block' : 'none';
  }
  audience.addEventListener('change', toggle);
  toggle();
})();
</script>
@endsection
