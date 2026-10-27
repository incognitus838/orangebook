@php
    $school = $school ?? optional(Auth::user())->school;
    $groups = $groups ?? collect();
    $people = $people ?? collect();
    $classes = $classes ?? collect();
    $labels = $school?->roleGroupLabels() ?? ['teachers' => 'Teachers', 'students' => 'Students', 'staff' => 'All staff'];
@endphp
<label class="ob-check"><input type="checkbox" name="everyone" value="1" id="target-everyone"> Everyone in this {{ strtolower($school?->orgWord() ?? 'organisation') }}</label>
<p class="ob-help" style="margin-top:12px">Or pick groups, a {{ strtolower($school?->classWordOne() ?? 'class') }}, or named people.</p>
<label>Groups</label>
<div class="ob-checks">
    @foreach($labels as $value => $label)
        <label class="ob-check"><input type="checkbox" name="groups[]" value="{{ $value }}"> {{ $label }}</label>
    @endforeach
    @foreach($groups as $group)
        <label class="ob-check"><input type="checkbox" name="group_ids[]" value="{{ $group->id }}"> {{ $group->name }}</label>
    @endforeach
</div>
<label>{{ $school?->classWordOne() ?? 'Class' }}</label>
<div class="ob-form-row">
    <select name="class_id">
        <option value="">No {{ strtolower($school?->classWordOne() ?? 'class') }}</option>
        @foreach($classes as $class)
            <option value="{{ $class->id }}">{{ $class->StandardSection }}</option>
        @endforeach
    </select>
    <select name="class_who">
        @if($school?->usesParents())
            <option value="parents">Parents of that class</option>
            <option value="students">{{ $school->learnerWord() }} of that class</option>
            <option value="both">{{ $school->learnerWord() }} and parents</option>
        @else
            <option value="students">{{ $school->learnerWord() }} of that cohort</option>
        @endif
    </select>
</div>
<details class="ob-people-fold">
    <summary>Named people</summary>
    @include('crest.board._people_pick', ['people' => $people, 'name' => 'user_ids', 'withEmail' => false])
</details>
