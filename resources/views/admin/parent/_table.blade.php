<div class="ob-table-wrap">
    <table class="crest-table ob-register">
        <thead>
            <tr>
                <th>Name</th>
                <th>Children</th>
                <th>Email</th>
                <th>Phone</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($parents as $parent)
                <tr>
                    <td><a href="{{ url('/admin/parent/show') }}/{{ rawurlencode($parent->name) }}">{{ $parent->name }}</a></td>
                    <td>
                        @forelse($parent->childUsers as $child)
                            @php $class = optional(optional($child->studentAcademicLatest)->standardLink)->StandardSection; @endphp
                            <a class="ob-chip" href="{{ url('/admin/students/'.$child->id) }}">{{ $child->name }}@if($class) · {{ $class }}@endif</a>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td>{{ $parent->email }}</td>
                    <td>{{ $parent->mobile_no ?: '—' }}</td>
                    <td>@include('admin.partials.open_desk', ['user' => $parent])</td>
                </tr>
            @empty
                <tr><td colspan="5">No parents for this filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
