<form method="POST" action="{{ $action }}" class="ob-form" enctype="multipart/form-data">
    @csrf
    <img class="ob-avatar" src="{{ $avatarUrl }}" alt="">
    <label>Photo</label>
    <input type="file" name="avatar" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
    @error('avatar')<p class="crest-err">{{ $message }}</p>@enderror
    <p class="ob-help">JPEG or PNG.</p>
    <button class="crest-btn" type="submit" style="margin-top:28px">Save photo</button>
</form>
