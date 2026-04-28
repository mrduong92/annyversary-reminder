<x-app-layout>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('genealogy.index') }}" class="hover:text-gray-700">Gia phả</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700">Sửa</span>
        </div>
        <h1 class="text-2xl font-semibold text-gray-900">{{ $member->pronoun ? $member->pronoun . ' ' : '' }}{{ $member->name }}</h1>
        @if ($member->lifespan())
            <p class="text-sm text-gray-400 mt-0.5">{{ $member->lifespan() }}</p>
        @endif
    </div>
    <div class="max-w-xl bg-white rounded-xl border border-gray-200 p-6">
        @include('genealogy._form', [
            'action'          => route('genealogy.update', $member),
            'method'          => 'PUT',
            'currentCoupleId' => $currentCoupleId,
            'currentSpouseId' => $currentSpouseId,
        ])
    </div>
</x-app-layout>
