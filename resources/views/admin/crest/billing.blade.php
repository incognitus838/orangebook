@extends('layouts.admin.layout')
@section('content')
<div>
    <h1 class="admin-h1 my-3">Plan</h1>
    @include('partials.message')
    <p class="text-sm mb-4">
        Current: <strong>{{ optional(optional($subscription)->plan)->display_name ?? 'None' }}</strong>
        · {{ $studentCount }} students
        · {{ optional($subscription)->end_date ? 'Until '.$subscription->end_date : '' }}
    </p>
    <div class="grid md:grid-cols-3 gap-4">
        @foreach($plans as $plan)
            <form method="POST" action="{{ url('/admin/crest/billing') }}" class="bg-white border p-4">
                @csrf
                <input type="hidden" name="plan" value="{{ $plan->name }}">
                <h2 class="font-semibold">{{ $plan->display_name }}</h2>
                <p>{{ $plan->amount > 0 ? '₦'.number_format($plan->amount).' / term' : 'Free '.$plan->cycle.' days' }}</p>
                <p class="text-sm">{{ $plan->no_of_members >= 999999 ? 'Unlimited students' : number_format($plan->no_of_members).' students' }}</p>
                <button class="mt-3 bg-gray-900 text-white text-sm px-3 py-1">Switch</button>
            </form>
        @endforeach
    </div>
</div>
@endsection
