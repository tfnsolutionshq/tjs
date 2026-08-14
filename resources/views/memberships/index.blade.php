@extends('layouts.member')

@section('title', 'Memberships | '.config('tjs.name'))
@section('page_title', 'Memberships')
@section('page_subtitle', 'Your access plans and available upgrades')

@section('content')
@if (session('status'))
    <div class="mp-flash">{{ session('status') }}</div>
@endif

<div class="mp-grid mp-grid--2">
    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Active memberships</h2>
                <p class="mp-card__desc">Current access to members-only content.</p>
            </div>
        </div>
        <div class="mp-card__body">
            @forelse($activeMemberships as $membership)
                <div class="mp-row">
                    <div>
                        <p class="mp-row__title">{{ $membership->plan?->name ?? 'Membership' }}</p>
                        <p class="mp-row__meta">
                            Ends {{ optional($membership->ends_at)->format('M j, Y') }}
                            @if($membership->journal) · {{ $membership->journal->title }} @endif
                        </p>
                    </div>
                    <span class="mp-badge mp-badge--{{ $membership->scope === 'platform' ? 'platform' : 'journal' }}">{{ $membership->scope }}</span>
                </div>
            @empty
                <p class="mp-empty">You do not have an active membership yet.</p>
            @endforelse
        </div>
    </section>

    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Available plans</h2>
                <p class="mp-card__desc">Unlock members-only full text for a journal or the whole platform.</p>
            </div>
        </div>
        <div class="mp-card__body" style="display:grid;gap:.65rem">
            @forelse($plans as $plan)
                <div class="mp-row" style="padding:.9rem 1rem;border:1px solid var(--line);border-radius:.85rem;background:#f8fafc;border-bottom:1px solid var(--line)">
                    <div>
                        <p class="mp-row__title">{{ $plan->name }}</p>
                        <p class="mp-row__meta">
                            <span class="mp-badge mp-badge--{{ $plan->scope }}">{{ $plan->scope }}</span>
                            @if($plan->journal) {{ $plan->journal->title }} · @endif
                            ₦{{ number_format($plan->price_amount) }} · {{ $plan->duration_days }} days
                        </p>
                    </div>
                    <form method="POST" action="{{ route('payments.memberships.buy', $plan) }}" x-data="{ submitting: false }" @submit="if (submitting) { $event.preventDefault() } else { submitting = true }">
                        @csrf
                        <button type="submit" class="mp-btn mp-btn-primary" style="padding:.55rem .85rem;font-size:.8rem" :disabled="submitting">
                            <span class="mp-spinner" x-show="submitting" x-cloak></span>
                            <span x-text="submitting ? 'Redirecting…' : 'Become a member'"></span>
                        </button>
                    </form>
                </div>
            @empty
                <p class="mp-empty">No membership plans are available right now.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
