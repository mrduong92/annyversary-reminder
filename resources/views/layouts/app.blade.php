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
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 mb-3">
            <div class="w-7 h-7 rounded-lg bg-primary-600 flex items-center justify-center text-white text-xs font-bold shrink-0">G</div>
            <span class="text-sm font-semibold text-gray-500">Nhắc Lịch Giỗ</span>
        </a>

        {{-- Family Group Switcher --}}
        @auth
        @php $groups = Auth::user()->familyGroups()->orderBy('is_default','desc')->orderBy('name')->get(); @endphp
        <div class="mb-4 px-1" x-data="{ open: false }">
            <button @click="open = !open"
                class="w-full flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-gray-50 transition-colors text-left group">
                <div class="w-7 h-7 rounded-full shrink-0 flex items-center justify-center text-white text-xs font-bold"
                    style="background-color: {{ $activeFamilyGroup->color ?? '#c026d3' }}">
                    {{ mb_substr($activeFamilyGroup->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $activeFamilyGroup->name }}</p>
                    <p class="text-xs text-gray-400">Đang hoạt động</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-cloak @click.outside="open = false"
                class="mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

                {{-- Các gia đình --}}
                @foreach ($groups as $g)
                <div x-data="{ editing: false, name: '{{ addslashes($g->name) }}', color: '{{ $g->color }}' }">

                    {{-- Chế độ xem --}}
                    <div x-show="!editing" class="flex items-center gap-1 pr-1 {{ $g->id === $activeFamilyGroup->id ? 'bg-primary-50' : '' }}">
                        <form method="POST" action="{{ route('family-groups.switch') }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="group_id" value="{{ $g->id }}">
                            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-gray-50 text-left transition-colors">
                                <div class="w-6 h-6 rounded-full shrink-0 flex items-center justify-center text-white text-xs font-bold"
                                    style="background-color: {{ $g->color }}">
                                    {{ mb_substr($g->name, 0, 1) }}
                                </div>
                                <span class="text-sm text-gray-700 flex-1 truncate">{{ $g->name }}</span>
                                @if ($g->id === $activeFamilyGroup->id)
                                    <svg class="w-4 h-4 text-primary-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </button>
                        </form>
                        {{-- Edit button --}}
                        <button @click.stop="editing = true"
                            class="shrink-0 p-1.5 text-gray-300 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Chế độ sửa --}}
                    <div x-show="editing" x-cloak class="p-2 space-y-2">
                        <form method="POST" action="{{ route('family-groups.update', $g) }}" class="space-y-2">
                            @csrf @method('PUT')
                            <div class="flex gap-1.5">
                                <input type="color" name="color" x-model="color"
                                    class="w-8 h-8 rounded border border-gray-200 cursor-pointer shrink-0 p-0.5">
                                <input type="text" name="name" x-model="name" required
                                    class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:ring-1 focus:ring-primary-500 outline-none"
                                    @keydown.escape="editing = false">
                            </div>
                            <div class="flex gap-1.5">
                                <button type="submit"
                                    class="flex-1 text-xs bg-primary-600 text-white rounded-lg py-1.5 hover:bg-primary-700 font-medium">Lưu</button>
                                <button type="button" @click="editing = false"
                                    class="flex-1 text-xs bg-gray-100 text-gray-600 rounded-lg py-1.5 hover:bg-gray-200">Hủy</button>
                            </div>
                        </form>

                        {{-- Toggle Rằm & Mùng 1 --}}
                        <div class="border-t border-gray-100 pt-2 space-y-1">
                            <p class="text-xs text-gray-400 font-medium px-1">Nhắc ZNS hàng tháng</p>
                            @foreach (['remind_ram' => 'Rằm (ngày 15)', 'remind_mung_mot' => 'Mùng 1 (ngày 1)'] as $field => $label)
                            <form method="POST" action="{{ route('family-groups.toggle-reminder') }}" class="flex items-center justify-between px-1">
                                @csrf
                                <input type="hidden" name="field" value="{{ $field }}">
                                <label class="text-xs text-gray-600">{{ $label }}</label>
                                <button type="submit"
                                    class="relative inline-flex h-4 w-7 shrink-0 rounded-full transition-colors {{ $g->$field ? 'bg-primary-500' : 'bg-gray-200' }}">
                                    <span class="inline-block h-3 w-3 mt-0.5 rounded-full bg-white shadow transition-transform {{ $g->$field ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                </button>
                            </form>
                            @endforeach
                        </div>
                        @if ($groups->count() > 1)
                        <form method="POST" action="{{ route('family-groups.destroy', $g) }}"
                            onsubmit="return confirm('Xóa gia đình này? Tất cả ngày giỗ và văn khấn trong nhóm sẽ bị gỡ liên kết.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full text-xs text-red-400 hover:text-red-600 py-1">Xóa nhóm này</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Thêm gia đình mới --}}
                <div class="border-t border-gray-100 p-2" x-data="{ adding: false, name: '', color: '#6366f1' }">
                    <template x-if="!adding">
                        <button @click="adding = true"
                            class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-primary-600 hover:bg-primary-50 rounded-lg transition-colors font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Thêm gia đình
                        </button>
                    </template>
                    <template x-if="adding">
                        <form method="POST" action="{{ route('family-groups.store') }}" class="space-y-2">
                            @csrf
                            <div class="flex gap-1.5">
                                <input type="color" name="color" x-model="color"
                                    class="w-8 h-8 rounded border border-gray-200 cursor-pointer shrink-0 p-0.5">
                                <input type="text" name="name" x-model="name" placeholder="Tên gia đình..." required
                                    class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none">
                            </div>
                            <div class="flex gap-1.5">
                                <button type="submit"
                                    class="flex-1 text-xs bg-primary-600 text-white rounded-lg py-1.5 hover:bg-primary-700 transition-colors font-medium">
                                    Tạo
                                </button>
                                <button type="button" @click="adding = false; name = ''"
                                    class="flex-1 text-xs bg-gray-100 text-gray-600 rounded-lg py-1.5 hover:bg-gray-200 transition-colors">
                                    Hủy
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>
        @endauth

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
                <a href="{{ route('genealogy.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('genealogy.*'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('genealogy.*'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Gia phả
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
{{-- Người nhận thông báo được quản lý trong màn hình Ngày giỗ --}}
            <li>
                <a href="{{ route('prayers.index') }}"
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
            <li>
                <a href="{{ route('documents.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('documents.*'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('documents.*'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Tài liệu gia đình
                </a>
            </li>
            <li class="pt-3 mt-2 border-t border-gray-100">
                <a href="{{ route('settings.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => request()->routeIs('settings.*'),
                        'text-gray-700 hover:bg-gray-100' => !request()->routeIs('settings.*'),
                    ])>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Cài đặt
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
                @php $plan = Auth::user()->fresh()->subscription_plan ?? 'basic'; @endphp
                <a href="{{ route('upgrade') }}" @class([
                    'text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0 hover:opacity-80 transition-opacity',
                    'bg-gray-100 text-gray-600'     => $plan === 'basic',
                    'bg-amber-100 text-amber-700'   => $plan === 'advanced',
                ])>{{ $plan === 'advanced' ? 'Đại Gia Đình' : 'Gia Đình' }}</a>
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
