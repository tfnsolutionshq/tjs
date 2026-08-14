<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-[#2f7de1] border border-transparent rounded-md font-semibold text-sm text-white tracking-wide hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#2f7de1]/40 focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>
