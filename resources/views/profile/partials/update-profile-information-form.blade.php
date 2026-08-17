<div class="pf-card__head">
    <h2 class="pf-card__title">Profile information</h2>
    <p class="pf-card__desc">Your photo, contact details, and professional summary used across TJS.</p>
</div>

<form
    method="post"
    action="{{ route('profile.update') }}"
    enctype="multipart/form-data"
    class="pf-card__body"
    x-data="{
        submitting: false,
        preview: @js($user->avatarUrl()),
        fileName: '',
        removeAvatar: false,
        onFile(e) {
            const file = e.target.files?.[0];
            if (!file) return;
            this.removeAvatar = false;
            this.fileName = file.name;
            this.preview = URL.createObjectURL(file);
        },
        clearPhoto() {
            this.removeAvatar = true;
            this.preview = null;
            this.fileName = '';
            const input = this.$refs.avatarInput;
            if (input) input.value = '';
        }
    }"
    @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
>
    @csrf
    @method('patch')
    <input type="hidden" name="remove_avatar" :value="removeAvatar ? 1 : 0">

    <div class="pf-photo">
        <div class="pf-photo__preview" aria-hidden="true">
            <template x-if="preview">
                <img :src="preview" alt="">
            </template>
            <template x-if="!preview">
                <span>{{ $user->avatarInitial() }}</span>
            </template>
        </div>
        <div class="pf-photo__body">
            <p class="pf-photo__title">Profile photo</p>
            <x-form-label for="avatar" field="user.avatar" class="sr-only">Profile photo</x-form-label>
            <div class="pf-photo__actions">
                <label class="pf-btn pf-btn-secondary" style="cursor:pointer;margin:0" :class="submitting && 'is-busy'">
                    <input
                        id="avatar"
                        name="avatar"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                        class="sr-only"
                        x-ref="avatarInput"
                        @change="onFile($event)"
                    >
                    Choose image
                </label>
                <button
                    type="button"
                    class="pf-btn pf-btn-secondary"
                    x-show="preview"
                    x-cloak
                    @click="clearPhoto()"
                    :disabled="submitting"
                >Remove</button>
            </div>
            <p class="pf-hint" x-show="fileName" x-cloak x-text="fileName"></p>
            <div class="pf-chips">
                <span class="pf-chip">JPG / PNG / WEBP / GIF</span>
                <span class="pf-chip">Max 5MB</span>
                <span class="pf-chip">Public reviewer listing</span>
            </div>
            @error('avatar')<p class="pf-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="pf-grid pf-grid--2">
        <div class="pf-field">
            <x-form-label for="name" field="user.name" required>Full name</x-form-label>
            <input id="name" name="name" type="text" class="pf-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" :readonly="submitting">
            @error('name')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field">
            <x-form-label for="email" field="user.email" required>Email</x-form-label>
            <input id="email" name="email" type="email" class="pf-input" value="{{ old('email', $user->email) }}" required autocomplete="username" :readonly="submitting">
            @error('email')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field">
            <x-form-label for="position" field="user.position">Position / title</x-form-label>
            <input id="position" name="position" type="text" class="pf-input" value="{{ old('position', $user->position) }}" placeholder="e.g. Editor-in-Chief" autocomplete="organization-title" :readonly="submitting">
            @error('position')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field">
            <x-form-label for="affiliation" field="user.affiliation">Affiliation</x-form-label>
            <input id="affiliation" name="affiliation" type="text" class="pf-input" value="{{ old('affiliation', $user->affiliation) }}" placeholder="Institution or organization" autocomplete="organization" :readonly="submitting">
            @error('affiliation')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field" style="grid-column: 1 / -1">
            <x-form-label for="orcid" field="user.orcid">ORCID</x-form-label>
            <input id="orcid" name="orcid" type="text" class="pf-input" value="{{ old('orcid', $user->orcid) }}" placeholder="0000-0000-0000-0000" :readonly="submitting">
            @error('orcid')<p class="pf-error">{{ $message }}</p>@enderror
        </div>

        <div class="pf-field" style="grid-column: 1 / -1">
            <x-form-label for="bio" field="user.bio">Bio</x-form-label>
            <textarea id="bio" name="bio" class="pf-textarea" rows="5" :readonly="submitting" placeholder="Short professional summary…">{{ old('bio', $user->bio) }}</textarea>
            @error('bio')<p class="pf-error">{{ $message }}</p>@enderror
        </div>
    </div>

    @if($user->role === 'reviewer' || $user->isAdmin())
        <div class="pf-toggle">
            <span>
                <p class="pf-toggle__label">
                    List me as a public reviewer
                    <x-field-helper :text="\App\Support\FormHelp::get('user.public_reviewer')" />
                </p>
                <p class="pf-toggle__hint">Shows your name and photo on journal reviewer pages when you are assigned as a reviewer.</p>
            </span>
            <input type="hidden" name="is_public_reviewer" value="0">
            <input
                type="checkbox"
                name="is_public_reviewer"
                value="1"
                @checked((string) old('is_public_reviewer', $user->is_public_reviewer ? '1' : '0') === '1')
                @click="if (submitting) $event.preventDefault()"
                style="width:1.1rem;height:1.1rem;margin-top:.15rem;accent-color:#2563eb"
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
