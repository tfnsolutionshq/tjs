<div class="pf-card__head">
    <h2 class="pf-card__title" style="color:#991b1b">Delete account</h2>
    <p class="pf-card__desc" style="color:#b91c1c">
        Permanently removes your account. This cannot be undone.
    </p>
</div>

<div class="pf-card__body" x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <button type="button" class="pf-btn pf-btn-danger" @click="open = !open" style="width:fit-content">
        Delete account…
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms style="display:grid;gap:.85rem;padding-top:.35rem;border-top:1px solid #fecaca;margin-top:.35rem">
        <p style="margin:0;font-size:.84rem;color:#7f1d1d;line-height:1.45">
            Enter your password to confirm permanent deletion of your account and related personal data.
        </p>

        <form
            method="post"
            action="{{ route('profile.destroy') }}"
            style="display:grid;gap:.75rem;max-width:22rem"
            x-data="{ submitting: false }"
            @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
        >
            @csrf
            @method('delete')

            <div class="pf-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" class="pf-input" placeholder="Current password" required :readonly="submitting">
                @error('password', 'userDeletion')
                    <p class="pf-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="pf-actions">
                <button type="button" class="pf-btn pf-btn-secondary" @click="open = false">Cancel</button>
                <button type="submit" class="pf-btn pf-btn-danger" style="background:#b91c1c;color:#fff;border-color:#b91c1c" :disabled="submitting">
                    <span x-text="submitting ? 'Deleting…' : 'Permanently delete'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
