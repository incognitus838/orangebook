<form method="POST" action="{{ $action }}" class="ob-form">
    @csrf
    <label>Name</label>
    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
    @error('name')<p class="crest-err">{{ $message }}</p>@enderror
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
    @error('email')<p class="crest-err">{{ $message }}</p>@enderror
    <label>Phone</label>
    <input type="text" name="phone" value="{{ old('phone', $user->mobile_no) }}">
    @error('phone')<p class="crest-err">{{ $message }}</p>@enderror
    <button class="crest-btn" type="submit" style="margin-top:28px">Save</button>
</form>
