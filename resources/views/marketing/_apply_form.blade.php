<form method="POST" action="{{ url('/start') }}" class="crest-form lp-form">
    @csrf
    <label for="kind">This is a</label>
    <select id="kind" name="kind" required>
        <option value="school" @selected(old('kind', 'school') === 'school')>Secondary or primary school</option>
        <option value="ngo" @selected(old('kind') === 'ngo')>NGO or empowerment programme</option>
        <option value="training" @selected(old('kind') === 'training')>Training firm or SME class</option>
    </select>
    <label for="school_name">Organisation name</label>
    <input id="school_name" name="school_name" value="{{ old('school_name') }}" required>
    @error('school_name')<p class="crest-err">{{ $message }}</p>@enderror
    <label for="admin_name">Your name</label>
    <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
    @error('admin_name')<p class="crest-err">{{ $message }}</p>@enderror
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
    @error('email')<p class="crest-err">{{ $message }}</p>@enderror
    <label for="phone">Phone</label>
    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">
    @error('phone')<p class="crest-err">{{ $message }}</p>@enderror
    <label for="student_count">How many learners</label>
    <input id="student_count" type="number" min="1" name="student_count" value="{{ old('student_count') }}" required placeholder="About how many">
    @error('student_count')<p class="crest-err">{{ $message }}</p>@enderror
    <label for="notes">Anything we should know</label>
    <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
    @error('notes')<p class="crest-err">{{ $message }}</p>@enderror
    <button class="crest-btn lp-btn" type="submit">{{ $submit ?? 'Apply for a quote' }}</button>
</form>
