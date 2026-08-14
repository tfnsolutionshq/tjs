<div class="pf-card__head">
    <h2 class="pf-card__title">Profile information</h2>
    <p class="pf-card__desc">Your name, email, and professional details used across TJS.</p>
</div>

<form
    method="post"
    action="{{ route('profile.update') }}"
    class="pf-card__body"
    x-data="{ submitting: false }"
    @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
>
    @csrf
    @method('patch')

    <div class="pf-grid pf-grid--2">
        <div class="pf-field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" class="pf-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" :readonly="submitting">
            @error('name')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" class="pf-input" value="{{ old('email', $user->email) }}" required autocomplete="username" :readonly="submitting">
            @error('email')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field">
            <label for="position">Position / title</label>
            <input id="position" name="position" type="text" class="pf-input" value="{{ old('position', $user->position) }}" placeholder="e.g. Editor-in-Chief" autocomplete="organization-title" :readonly="submitting">
            @error('position')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field">
            <label for="affiliation">Affiliation</label>
            <input id="affiliation" name="affiliation" type="text" class="pf-input" value="{{ old('affiliation', $user->affiliation) }}" placeholder="Institution or organization" autocomplete="organization" :readonly="submitting">
            @error('affiliation')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field" style="grid-column: 1 / -1">
            <label for="orcid">ORCID</label>
            <input id="orcid" name="orcid" type="text" class="pf-input" value="{{ old('orcid', $user->orcid) }}" placeholder="0000-0000-0000-0000" :readonly="submitting">
            <p class="pf-hint">Optional researcher identifier shown on public reviewer pages when applicable.</p>
            @error('orcid')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field" style="grid-column: 1 / -1">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" class="pf-textarea" rows="4" :readonly="submitting" placeholder="Short professional summary…">{{ old('bio', $user->bio) }}</textarea>
            @error('bio')<p class="pf-error">{{ $message }}</p>@enderror
        </div>
    </div>

    @if($user->role === 'reviewer' || $user->isAdmin())
        <div class="pf-toggle">
            <span>
                <p class="pf-toggle__label">List me as a public reviewer</p>
                <p class="pf-toggle__hint">Shows your name on journal reviewer pages when you are assigned as a reviewer.</p>
            </span>
            <input type="hidden" name="is_public_reviewer" value="0">
            <input
                type="checkbox"
                name="is_public_reviewer"
                value="1"
                @checked((string) old('is_public_reviewer', $user->is_public_reviewer ? '1' : '0') === '1')
                @click="if (submitting) $event.preventDefault()"
                style="width:1.1rem;height:1.1rem;margin-top:.15rem;accent-color:#2f7de1"
            >
        </div>
    @endif

    <div class="pf-actions">
        <button type="submit" class="pf-btn pf-btn-primary" :disabled="submitting" :aria-busy="submitting">
            <span class="pf-spinner" x-show="submitting" x-cloak></span>
            <span x-text="submitting ? 'Saving…' : 'Save profile'"></span>
        </button>

        @if (session('status') === 'profile-updated')
            <p
                class="pf-saved"
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2500)"
            >Saved.</p>
        @endif
    </div>
</form>
