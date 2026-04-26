<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 h-full">

{{-- Sidebar --}}
<aside id="sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
    aria-label="Sidebar">
    <div class="h-full flex flex-col px-3 py-4 overflow-y-auto bg-white border-r border-gray-200">

        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2 mb-6">
            <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white text-sm font-bold shrink-0">G</div>
            <span class="text-base font-semibold text-gray-900 leading-tight">Nhắc Lịch Giỗ</span>
        </a>

        {{-- Nav items --}}
        <ul class="space-y-1 flex-1">
            <li>
                <a href="{{ route('dashboard') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('dashboard'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('dashboard'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('events.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('events.*'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('events.*'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Ngày giỗ
                </a>
            </li>
            <li>
                <a href="{{ route('recipients.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('recipients.*'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('recipients.*'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Người nhận ZNS
                </a>
            </li>
            <li>
                <a href="#"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('prayers.*'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('prayers.*'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Văn khấn
                </a>
            </li>

            <li class="pt-3 mt-2 border-t border-gray-100">
                <a href="{{ route('agent.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('agent.*'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('agent.*'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Chat AI
                    <span class="ms-auto text-xs bg-primary-100 text-primary-700 px-1.5 py-0.5 rounded-full font-medium">Beta</span>
                </a>
            </li>
        </ul>

        {{-- User info + logout --}}
        <div class="pt-3 border-t border-gray-100">
            @auth
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-sm font-semibold shrink-0">
                    {{ mb_substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->phone }}</p>
                </div>
                @php $plan = Auth::user()->fresh()->subscription_plan ?? 'free'; @endphp
                <span @class([
                    'text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0',
                    'bg-gray-100 text-gray-600' => $plan === 'free',
                    'bg-blue-100 text-blue-700' => $plan === 'basic',
                    'bg-amber-100 text-amber-700' => $plan === 'unlimited',
                ])>{{ strtoupper($plan) }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Đăng xuất
                </button>
            </form>
            @endauth
        </div>
    </div>
</aside>

{{-- Mobile overlay --}}
<div id="sidebar-overlay" class="fixed inset-0 z-30 bg-black/50 hidden sm:hidden" onclick="toggleSidebar()"></div>

{{-- Main wrapper --}}
<div class="sm:ml-64 min-h-screen flex flex-col">

    {{-- Top bar (mobile only) --}}
    <header class="sm:hidden sticky top-0 z-20 bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
        <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="font-semibold text-gray-900 text-sm">{{ config('app.name') }}</span>
    </header>

    {{-- Page content --}}
    <main class="flex-1 p-4 sm:p-6 lg:p-8">

        {{-- Flash messages --}}
        @if (session('success'))
        <div id="alert-success" class="flex items-center gap-3 p-4 mb-6 text-sm text-green-800 bg-green-50 rounded-lg border border-green-200" role="alert">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
            <button onclick="this.parentElement.remove()" class="ms-auto -mx-1.5 -my-1.5 p-1.5 text-green-600 hover:bg-green-100 rounded-lg">✕</button>
        </div>
        @endif

        @if (session('error'))
        <div id="alert-error" class="flex items-center gap-3 p-4 mb-6 text-sm text-red-800 bg-red-50 rounded-lg border border-red-200" role="alert">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
            <button onclick="this.parentElement.remove()" class="ms-auto -mx-1.5 -my-1.5 p-1.5 text-red-600 hover:bg-red-100 rounded-lg">✕</button>
        </div>
        @endif

        {{ $slot }}
    </main>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.toggle('hidden');
}
</script>
@stack('scripts')
</body>
</html>
