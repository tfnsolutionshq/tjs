@php
    $notice = \App\Support\SiteNotice::active();
@endphp
@if($notice)
    <style>
        .site-notice {
            width: 100%;
            font-size: .875rem;
            font-weight: 600;
            line-height: 1.45;
            border-bottom: 1px solid rgba(0, 0, 0, .08);
        }
        .site-notice__inner {
            max-width: 72rem;
            margin: 0 auto;
            padding: .65rem 1rem;
            text-align: center;
        }
        .site-notice__text { margin: 0; white-space: pre-line; }
        .site-notice--info {
            background: #eff6ff;
            color: #1e40af;
            border-bottom-color: #bfdbfe;
        }
        .site-notice--warning {
            background: #fffbeb;
            color: #92400e;
            border-bottom-color: #fde68a;
        }
        .site-notice--success {
            background: #ecfdf5;
            color: #047857;
            border-bottom-color: #a7f3d0;
        }
    </style>
    <div
        class="site-notice site-notice--{{ $notice['style'] }}"
        role="status"
        aria-live="polite"
    >
        <div class="site-notice__inner">
            <p class="site-notice__text">{{ $notice['message'] }}</p>
        </div>
    </div>
@endif
