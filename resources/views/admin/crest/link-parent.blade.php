@extends('layouts.admin.layout')
@section('content')
<div>
    <h1 class="admin-h1 my-3">Link parent to student</h1>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/crest/link-parent') }}" class="bg-white border p-4 max-w-lg">
        @csrf
        <label class="block text-sm mt-2">Parent name</label>
        <input class="border px-2 py-1 w-full" name="name" required>
        <label class="block text-sm mt-2">Parent email</label>
        <input class="border px-2 py-1 w-full" type="email" name="email" required>
        <label class="block text-sm mt-2">Temporary password</label>
        <input class="border px-2 py-1 w-full" name="password" value="password" required>
        <label class="block text-sm mt-2">Child (this school only)</label>
        <select class="border px-2 py-1 w-full" name="student_id" required>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->name }}</option>
            @endforeach
        </select>
        <button class="mt-4 bg-gray-900 text-white text-sm px-4 py-2">Link parent</button>
    </form>
</div>
@endsection
