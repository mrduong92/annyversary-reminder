@php /** @var array $node */ @endphp
<ul>
    <li>
        {{-- Member card --}}
        <div class="inline-flex items-center gap-3 mb-1">
            {{-- Member bubble --}}
            <a href="{{ route('genealogy.edit', $node['member']) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors hover:shadow-sm
                    {{ $node['member']->isAlive()
                        ? 'bg-white border-gray-200 text-gray-800 hover:border-primary-300'
                        : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-gray-300' }}">
                <span class="{{ $node['member']->gender === 'male' ? 'text-blue-400' : ($node['member']->gender === 'female' ? 'text-pink-400' : 'text-gray-300') }}">
                    {{ $node['member']->genderIcon() ?: '○' }}
                </span>
                <span>{{ $node['member']->name }}</span>
                @unless ($node['member']->isAlive())
                    <span class="text-xs text-gray-400">✝</span>
                @endunless
                @if ($node['member']->lifespan())
                    <span class="text-xs text-gray-400 font-normal">({{ $node['member']->lifespan() }})</span>
                @endif
            </a>

            {{-- Spouses --}}
            @foreach ($node['spouses'] as $spouse)
                <span class="text-gray-300 text-sm">—</span>
                <a href="{{ route('genealogy.edit', $spouse) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors
                        {{ $spouse->isAlive()
                            ? 'bg-white border-gray-200 text-gray-800 hover:border-primary-300'
                            : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                    <span class="{{ $spouse->gender === 'male' ? 'text-blue-400' : ($spouse->gender === 'female' ? 'text-pink-400' : 'text-gray-300') }}">
                        {{ $spouse->genderIcon() ?: '○' }}
                    </span>
                    <span>{{ $spouse->name }}</span>
                    @unless ($spouse->isAlive()) <span class="text-xs text-gray-400">✝</span> @endunless
                    @if ($spouse->lifespan())
                        <span class="text-xs text-gray-400 font-normal">({{ $spouse->lifespan() }})</span>
                    @endif
                </a>
            @endforeach

            {{-- Link ngày giỗ --}}
            @if ($node['member']->derivedEvent)
                <a href="{{ route('events.show', $node['member']->derivedEvent) }}"
                    class="text-xs text-primary-500 hover:underline">📅</a>
            @endif
        </div>

        {{-- Children recursively --}}
        @if ($node['children']->isNotEmpty())
            @foreach ($node['children'] as $child)
                @include('genealogy._tree_node', ['node' => $child])
            @endforeach
        @endif
    </li>
</ul>
