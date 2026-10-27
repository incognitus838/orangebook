@extends('layouts.admin.layout')
@section('content')
@php $school = $school ?? Auth::user()->school; @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $school->classWord() }}</h1>
    </div>
    @include('partials.message')
    <nav class="cal-switch" style="margin-bottom:24px">
        <a href="{{ url('/admin/standardlinks') }}" class="{{ $tab === 'classes' ? 'is-on' : '' }}">{{ $school->classWord() }}</a>
        <a href="{{ url('/admin/standardlinks?tab=levels') }}" class="{{ $tab === 'levels' ? 'is-on' : '' }}">Levels &amp; arms</a>
        <a href="{{ url('/admin/standardlinks?tab=subjects') }}" class="{{ $tab === 'subjects' ? 'is-on' : '' }}">Subjects</a>
    </nav>

    @if($tab === 'levels')
        <form method="POST" action="{{ url('/admin/standardlinks/preset') }}" class="ob-form" style="margin-bottom:24px">
            @csrf
            <p class="ob-form-title">{{ $standards->isEmpty() ? 'Start from a preset' : 'Add missing from a preset' }}</p>
            <select name="variant">
                @foreach($presets as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <button class="crest-btn" type="submit">Apply preset</button>
        </form>
        <form method="POST" action="{{ url('/admin/standardlinks/levels') }}" class="ob-form" style="margin-bottom:24px">
            @csrf
            <p class="ob-form-title">Add a level</p>
            <input type="text" name="name" required placeholder="Name">
            <label><input type="checkbox" name="is_entry" value="1"> Entry level</label>
            <button class="crest-btn" type="submit">Add level</button>
        </form>
        <div class="ob-table-wrap" style="margin-bottom:32px">
            <table class="crest-table">
                <thead><tr><th>Level</th><th>Entry</th><th></th></tr></thead>
                <tbody>
                @forelse($standards->where('status', 1) as $level)
                    <tr>
                        <td>
                            <form method="POST" action="{{ url('/admin/standardlinks/levels/'.$level->id) }}" class="ob-inline">
                                @csrf
                                <input type="text" name="name" value="{{ $level->name }}" required>
                                <label><input type="checkbox" name="is_entry" value="1" @checked($level->is_entry)> Entry</label>
                                <button class="crest-btn ob-btn-secondary" type="submit">Save</button>
                            </form>
                        </td>
                        <td>{{ $level->is_entry ? 'Yes' : '—' }}</td>
                        <td>
                            <form method="POST" action="{{ url('/admin/standardlinks/levels/'.$level->id.'/up') }}" class="ob-inline">@csrf<button class="crest-btn ob-btn-secondary" type="submit">Up</button></form>
                            <form method="POST" action="{{ url('/admin/standardlinks/levels/'.$level->id.'/down') }}" class="ob-inline">@csrf<button class="crest-btn ob-btn-secondary" type="submit">Down</button></form>
                            <form method="POST" action="{{ url('/admin/standardlinks/levels/'.$level->id.'/archive') }}" class="ob-inline">@csrf<button class="crest-btn ob-btn-secondary" type="submit">Archive</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">No levels yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ url('/admin/standardlinks/arms') }}" class="ob-form" style="margin-bottom:24px">
            @csrf
            <p class="ob-form-title">Add an arm</p>
            <input type="text" name="name" required placeholder="A">
            <button class="crest-btn" type="submit">Add arm</button>
        </form>
        <div class="ob-table-wrap">
            <table class="crest-table">
                <thead><tr><th>Arm</th><th></th></tr></thead>
                <tbody>
                @forelse($sections->where('status', 1) as $arm)
                    <tr>
                        <td>
                            <form method="POST" action="{{ url('/admin/standardlinks/arms/'.$arm->id) }}" class="ob-inline">
                                @csrf
                                <input type="text" name="name" value="{{ $arm->name }}" required>
                                <button class="crest-btn ob-btn-secondary" type="submit">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ url('/admin/standardlinks/arms/'.$arm->id.'/archive') }}" class="ob-inline">@csrf<button class="crest-btn ob-btn-secondary" type="submit">Archive</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2">No arms yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @elseif($tab === 'subjects')
        <form method="POST" action="{{ url('/admin/standardlinks/catalog') }}" class="ob-form" style="margin-bottom:24px">
            @csrf
            <p class="ob-form-title">Add subjects</p>
            <input type="text" name="name" placeholder="English Language">
            <input type="text" name="code" placeholder="Code (optional)">
            <select name="type">
                <option value="core">Core</option>
                <option value="elective">Elective</option>
            </select>
            <label>Or paste one subject per line</label>
            <textarea name="bulk" rows="6" placeholder="English Language&#10;Mathematics&#10;Basic Science"></textarea>
            <button class="crest-btn" type="submit">Add to catalogue</button>
        </form>
        <form method="POST" action="{{ url('/admin/standardlinks/catalog/apply') }}" class="ob-form" style="margin-bottom:24px">
            @csrf
            <p class="ob-form-title">Apply subjects to {{ strtolower($school->classWord()) }}</p>
            <label>Subjects</label>
            <div class="ob-pick">
                @foreach($catalog as $item)
                    <label class="ob-check"><input type="checkbox" name="catalog_ids[]" value="{{ $item->id }}"> {{ $item->name }} ({{ $item->type }})</label>
                @endforeach
            </div>
            <label>{{ $school->classWord() }}</label>
            <div class="ob-pick">
                @foreach($classes as $class)
                    <label class="ob-check"><input type="checkbox" name="class_ids[]" value="{{ $class->id }}"> {{ $class->StandardSection }}</label>
                @endforeach
            </div>
            <label>{{ $school->staffWordOne() }} (optional)</label>
            <select name="teacher_id">
                <option value="">Unassigned</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
            <button class="crest-btn" type="submit">Apply subjects</button>
        </form>
        <div class="ob-table-wrap">
            <table class="crest-table">
                <thead><tr><th>Subject</th><th>Type</th><th></th></tr></thead>
                <tbody>
                @forelse($catalog as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->type }}</td>
                        <td>
                            <form method="POST" action="{{ url('/admin/standardlinks/catalog/'.$item->id.'/archive') }}">@csrf<button class="crest-btn ob-btn-secondary" type="submit">Archive</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">No subjects in the catalogue yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @else
        <form method="POST" action="{{ url('/admin/standardlinks') }}" class="ob-form mb-6">
            @csrf
            <p class="ob-form-title">Add {{ strtolower($school->classWordOne()) }}</p>
            <label>Level</label>
            <select name="standard_id" required>
                <option value="">Choose level</option>
                @foreach($standards->where('status', 1) as $standard)
                    <option value="{{ $standard->id }}" @selected(old('standard_id') == $standard->id)>{{ $standard->name }}</option>
                @endforeach
            </select>
            <label>Arms (pick several)</label>
            <div class="ob-pick">
                @foreach($sections->where('status', 1) as $section)
                    <label class="ob-check"><input type="checkbox" name="section_ids[]" value="{{ $section->id }}"> {{ $section->name }}</label>
                @endforeach
            </div>
            <label>Form teacher (optional)</label>
            <select name="class_teacher_id">
                <option value="">No form teacher</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('class_teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            <button class="crest-btn" type="submit">Add {{ strtolower($school->classWord()) }}</button>
        </form>
        <form method="POST" action="{{ url('/admin/standardlinks/copy-year') }}" class="ob-form" style="margin-bottom:24px">
            @csrf
            <button class="crest-btn ob-btn-secondary" type="submit">Copy {{ strtolower($school->classWord()) }} from last year</button>
        </form>
        <div class="ob-table-wrap">
            <table class="crest-table">
                <thead>
                    <tr>
                        <th>{{ $school->classWordOne() }}</th>
                        <th>Form teacher</th>
                        <th>Subjects</th>
                        <th>{{ $school->learnerWord() }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                        <tr>
                            <td>{{ $class->StandardSection }}</td>
                            <td>
                                @if(!$class->class_teacher_id)
                                    <span class="ob-chip is-warn">No form teacher</span>
                                @endif
                                <form method="POST" action="{{ url('/admin/standardlinks/'.$class->id.'/teacher') }}" class="ob-inline">
                                    @csrf
                                    <select name="class_teacher_id" onchange="this.form.submit()">
                                        <option value="">No form teacher</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" @selected((int) $class->class_teacher_id === (int) $teacher->id)>{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>
                                @forelse($class->teacherlink as $link)
                                    <form method="POST" action="{{ url('/admin/standardlinks/'.$class->id.'/subject/'.$link->id.'/teacher') }}" class="ob-chip-form">
                                        @csrf
                                        <span class="ob-chip {{ $link->teacher_id ? '' : 'is-warn' }}">{{ \Illuminate\Support\Str::title(strtolower(optional($link->subject)->name ?? '')) }}</span>
                                        <select name="teacher_id" onchange="this.form.submit()">
                                            <option value="">Unassigned</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" @selected((int) $link->teacher_id === (int) $teacher->id)>{{ $teacher->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ url('/admin/standardlinks/'.$class->id.'/subject/'.$link->id.'/remove') }}" class="ob-chip-form">
                                        @csrf
                                        <button type="submit" class="ob-chip-x" title="Remove">×</button>
                                    </form>
                                @empty
                                    <span style="color:var(--crest-muted)">None yet</span>
                                @endforelse
                                <form method="POST" action="{{ url('/admin/standardlinks/'.$class->id.'/subject') }}" class="ob-subject-add">
                                    @csrf
                                    <input list="subject-names" name="subject_name" placeholder="Subject" required>
                                    <select name="teacher_id">
                                        <option value="">Unassigned</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="crest-btn" type="submit" style="padding:8px 12px;font-size:13px">Add</button>
                                </form>
                            </td>
                            <td>{{ $counts[$class->id] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No {{ strtolower($school->classWord()) }} yet. Add a level and arms, then add classes here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <datalist id="subject-names">
            @foreach($subjects as $subject)
                <option value="{{ $subject->name }}"></option>
            @endforeach
        </datalist>
    @endif
</div>
@endsection
