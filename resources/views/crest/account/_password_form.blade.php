<form method="POST" action="{{ $action }}" class="ob-form">
    @csrf
    <label>Current password</label>
    <input type="password" name="oldpassword" required autocomplete="current-password">
    @error('oldpassword')<p class="crest-err">{{ $message }}</p>@enderror
    <label>New password</label>
    <input type="password" name="newpassword" required minlength="8" autocomplete="new-password">
    @error('newpassword')<p class="crest-err">{{ $message }}</p>@enderror
    <p class="ob-help">At least 8 characters.</p>
    <label>Confirm new password</label>
    <input type="password" name="confirmpassword" required minlength="8" autocomplete="new-password">
    @error('confirmpassword')<p class="crest-err">{{ $message }}</p>@enderror
    <button class="crest-btn" type="submit" style="margin-top:28px">Save password</button>
</form>
