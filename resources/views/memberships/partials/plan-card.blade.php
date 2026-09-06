<article class="ms-plan" style="margin-bottom:.85rem">
    <div class="ms-plan__main">
        @if($plan->journal)
            @if($plan->journal->logoUrl())
                <img src="{{ $plan->journal->logoUrl() }}" alt="{{ $plan->journal->title }}" class="ms-plan__logo">
            @else
                <span class="ms-plan__initials" aria-hidden="true">{{ $plan->journal->displayInitials() }}</span>
            @endif
        @endif
        <div class="ms-plan__body">
            <div class="ms-plan__features">
                <span class="mp-badge mp-badge--{{ $plan->scope }}">{{ $plan->scope }}</span>
                @if($plan->journal)
                    <span class="ms-chip">{{ $plan->journal->title }}</span>
                    @if($plan->journal->is_featured)
                        <span class="ms-badge-featured">Featured</span>
                    @endif
                @endif
                <span class="ms-chip">{{ $plan->duration_days }} days</span>
            </div>
            <h3 class="ms-access__title" style="margin-top:.55rem">{{ $plan->name }}</h3>
            <p class="ms-plan__price">
                ₦{{ number_format($plan->price_amount) }}
                <span>/ {{ strtoupper($plan->currency ?: 'NGN') }}</span>
            </p>
        </div>
    </div>
    <form
        method="POST"
        action="{{ route('payments.memberships.buy', $plan) }}"
        x-data="{ submitting: false }"
        @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
    >
        @csrf
        <button type="submit" class="mp-btn mp-btn-primary" style="padding:.62rem 1rem;font-size:.82rem;white-space:nowrap" :disabled="submitting">
            <span class="mp-spinner" x-show="submitting" x-cloak></span>
            <span x-text="submitting ? 'Redirecting…' : 'Subscribe'"></span>
        </button>
    </form>
</article>
