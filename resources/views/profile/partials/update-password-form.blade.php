<div class="pf-card__head">
    <h2 class="pf-card__title">Update password</h2>
    <p class="pf-card__desc">Use a long, unique password for your TJS account.</p>
</div>

<form
    method="post"
    action="{{ route('password.update') }}"
    class="pf-card__body"
    x-data="{ submitting: false }"
    @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
>
    @csrf
    @method('put')

    <div class="pf-field">
        <label for="update_password_current_password">Current password</label>
        <input id="update_password_current_password" name="current_password" type="password" class="pf-input" autocomplete="current-password" :readonly="submitting">
        @error('current_password', 'updatePassword')
            <p class="pf-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="pf-grid pf-grid--2">
        <div class="pf-field">
            <label for="update_password_password">New password</label>
            <input id="update_password_password" name="password" type="password" class="pf-input" autocomplete="new-password" :readonly="submitting">
            @error('password', 'updatePassword')
                <p class="pf-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="pf-field">
            <label for="update_password_password_confirmation">Confirm password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="pf-input" autocomplete="new-password" :readonly="submitting">
        </div>
    </div>

    <div class="pf-actions">
        <button type="submit" class="pf-btn pf-btn-primary" :disabled="submitting" :aria-busy="submitting">
            <span class="pf-spinner" x-show="submitting" x-cloak></span>
            <span x-text="submitting ? 'Updating…' : 'Update password'"></span>
        </button>

        @if (session('status') === 'password-updated')
            <p
                class="pf-saved"
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2500)"
            >Password updated.</p>
        @endif
    </div>
</form>
