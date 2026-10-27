@extends($layout ?? 'layouts.admin.layout')
@section('content')
@php $school = $school ?? Auth::user()->school; @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Announcements</h1>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ $action }}" class="ob-form">
        @csrf
        <p class="ob-form-title">Write one</p>
        <label for="template">Template</label>
        <select id="template">
            <option value="">Start from a blank announcement</option>
            @foreach($templates as $key => $tpl)
                <option value="{{ $key }}" data-subject="{{ $tpl['subject'] }}" data-body="{{ $tpl['body'] }}">{{ $tpl['label'] }}</option>
            @endforeach
        </select>
        @include('crest.board._target')
        <p class="ob-help" id="recipient-count">Choose an audience to see how many people this reaches.</p>
        <label for="title">Title</label>
        <input id="title" name="title" required maxlength="120" value="{{ old('title') }}">
        <label for="body">Message</label>
        <textarea id="body" name="body" rows="6" required maxlength="5000">{{ old('body') }}</textarea>
        <label>Delivery</label>
        <select name="delivery" id="delivery">
            <option value="board">Notice board only</option>
            <option value="email">Notice board and email</option>
        </select>
        <p class="ob-help">SMS stays off until this organisation has an SMS setting. Scheduling for later needs a queue worker — skip it for now.</p>
        <button class="crest-btn" type="submit">Post</button>
    </form>

    <form method="GET" action="{{ $action }}" class="ob-filters" style="margin-top:28px">
        <div>
            <label>Channel</label>
            <select name="channel">
                <option value="">All</option>
                <option value="board" @selected(request('channel')==='board')>Board</option>
                <option value="email" @selected(request('channel')==='email')>Email</option>
            </select>
        </div>
        <div>
            <label>{{ $school->classWordOne() }}</label>
            <select name="class_id">
                <option value="">All</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->StandardSection }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Author</label>
            <select name="author">
                <option value="">All</option>
                @foreach($authors as $author)
                    <option value="{{ $author->id }}" @selected((string) request('author') === (string) $author->id)>{{ $author->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="crest-btn ob-btn-secondary" type="submit">Show</button>
    </form>

    @foreach($feed as $row)
        <div class="ob-card" style="margin-top:16px">
            <h2 class="admin-h2">{{ $row->title }}</h2>
            <p class="ob-help">
                {{ $service->audienceLabel($row->audience, $row->standardLink) }}
                · <span class="ob-chip">Board</span>
                @if($row->send_mail_id)<span class="ob-chip">Email</span>@endif
                · {{ optional($row->author)->name ?? 'Staff' }}
                · {{ optional($row->publish_date)->format('d M Y') }}
            </p>
            <p>{{ $row->description }}</p>
            @if($row->full_body && strlen($row->full_body) > 240)
                <details><summary>Read more</summary><p style="white-space:pre-wrap">{{ $row->full_body }}</p></details>
            @endif
            <form method="POST" action="{{ url($action.'/'.$row->id) }}" class="ob-inline">
                @csrf
                <input type="text" name="body" value="{{ $row->full_body ?: $row->description }}" maxlength="5000">
                <button class="crest-btn ob-btn-secondary" type="submit">Save text</button>
            </form>
            <form method="POST" action="{{ url($action.'/'.$row->id.'/archive') }}" class="ob-inline">
                @csrf
                <button class="crest-btn ob-btn-secondary" type="submit">Archive</button>
            </form>
            <form method="POST" action="{{ url($action.'/'.$row->id) }}" class="ob-inline" onsubmit="return confirm('Delete this announcement?')">
                @csrf
                @method('DELETE')
                <button class="crest-btn ob-btn-secondary" type="submit">Delete</button>
            </form>
        </div>
    @endforeach
    {{ $feed->links() }}
</div>
<script>
(function () {
  var templates = document.getElementById('template');
  if (templates) {
    templates.addEventListener('change', function () {
      var opt = templates.options[templates.selectedIndex];
      if (!opt.value) return;
      document.getElementById('title').value = opt.getAttribute('data-subject');
      document.getElementById('body').value = opt.getAttribute('data-body');
    });
  }
  var form = document.querySelector('.ob-form');
  var countEl = document.getElementById('recipient-count');
  var countUrl = @json($countUrl ?? '');
  if (!form || !countEl || !countUrl) return;
  function tally() {
    var data = new FormData(form);
    var qs = new URLSearchParams(data);
    qs.delete('_token');
    fetch(countUrl + '?' + qs.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) { countEl.textContent = (j.count || 0) + ' people in this audience.'; })
      .catch(function () {});
  }
  form.addEventListener('change', tally);
})();
</script>
@endsection
