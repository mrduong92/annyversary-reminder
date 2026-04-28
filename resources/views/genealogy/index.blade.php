<x-app-layout>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Gia phả</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $group->name }} · {{ $members->count() }} thành viên</p>
    </div>
    <a href="{{ route('genealogy.create') }}"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Thêm thành viên
    </a>
</div>

@if ($members->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
        <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <p class="text-sm text-gray-400 mb-4">Chưa có thành viên nào.</p>
        <a href="{{ route('genealogy.create') }}" class="text-sm font-medium text-primary-600 hover:underline">
            + Thêm thành viên đầu tiên
        </a>
    </div>
@else

    <div x-data="{ tab: 'tree' }" class="space-y-4">
        <div class="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit">
            <button @click="tab = 'tree'"
                :class="tab === 'tree' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-1.5 text-sm font-medium rounded-md transition-all">
                🌳 Cây gia phả
            </button>
            <button @click="tab = 'list'"
                :class="tab === 'list' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-1.5 text-sm font-medium rounded-md transition-all">
                📋 Danh sách
            </button>
        </div>

        {{-- Balkan FamilyTree View --}}
        <div x-show="tab === 'tree'">
            <div class="bg-white rounded-xl border border-gray-200" style="height:600px;position:relative;overflow:hidden;">
                <div id="family-tree" class="f3" style="width:100%;height:100%;"></div>
            </div>
            <p class="text-xs text-gray-400 mt-2 text-center">
                Click vào thành viên → menu tùy chọn · Scroll zoom · Kéo di chuyển
                <span class="mx-1">·</span>
                <a href="{{ route('genealogy.create') }}" class="text-primary-500 hover:underline">Thêm từ form</a>
                để gắn quan hệ chi tiết hơn
            </p>
        </div>

        {{-- List View --}}
        <div x-show="tab === 'list'" x-cloak>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3">Tên</th>
                            <th class="px-5 py-3 hidden sm:table-cell">Năm sinh – mất</th>
                            <th class="px-5 py-3 hidden md:table-cell">Cha/Mẹ</th>
                            <th class="px-5 py-3 hidden md:table-cell">Ngày giỗ</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($members as $m)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="{{ $m->gender === 'male' ? 'text-blue-400' : ($m->gender === 'female' ? 'text-pink-400' : 'text-gray-300') }}">{{ $m->genderIcon() ?: '○' }}</span>
                                    <span class="font-medium text-gray-900">{{ $m->name }}</span>
                                    @unless ($m->isAlive()) <span class="text-xs text-gray-400">✝</span> @endunless
                                </div>
                            </td>
                            <td class="px-5 py-3.5 hidden sm:table-cell text-gray-500 text-xs">{{ $m->lifespan() ?: '—' }}</td>
                            <td class="px-5 py-3.5 hidden md:table-cell text-gray-500 text-xs">
                                @php $parents = $m->parents()->pluck('name')->implode(', '); @endphp
                                {{ $parents ? 'Con của ' . $parents : '—' }}
                            </td>
                            <td class="px-5 py-3.5 hidden md:table-cell">
                                @if ($m->derivedEvent)
                                    <a href="{{ route('events.show', $m->derivedEvent) }}" class="text-xs text-primary-600 hover:underline">{{ $m->derivedEvent->displayName() }}</a>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('genealogy.edit', $m) }}" class="text-xs font-medium text-primary-600 hover:underline">Sửa</a>
                                    <form method="POST" action="{{ route('genealogy.destroy', $m) }}"
                                        onsubmit="return confirm('Xóa {{ $m->name }} khỏi gia phả?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-medium text-red-400 hover:underline">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- family-chart CSS + light theme override --}}
    @push('styles')
    <link rel="stylesheet" href="/vendor/family-chart.css">
    <style>
        /* CSS override SVG presentation attributes (lower specificity than CSS) */
        /* path.link có stroke="#fff" set bằng D3 .attr() — CSS !important win */
        #family-tree path.link {
            stroke: #6b7280 !important;
            stroke-width: 2px !important;
            opacity: 1 !important;
        }
        #family-tree svg.main_svg {
            background: #fff !important;
        }
        /* Ẩn buttons mặc định của library */
        #family-tree .card_add,
        #family-tree .card_add_relative,
        #family-tree .card_edit {
            display: none !important;
        }
    </style>
    @endpush

    {{-- family-chart v0.9 — bundled via Vite, API: createChart(selector, data) --}}
    @push('scripts')
    <script>
    (function() {
        const dataUrl = '{{ route('genealogy.data') }}';
        const cont    = document.getElementById('family-tree');

        fetch(dataUrl, { headers: { Accept: 'application/json' } })
            .then(r => {
                if (! r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(initTree)
            .catch(e => {
                console.error('Gia phả load error:', e);
                cont.innerHTML = '<p class="text-center py-20 text-gray-400 text-sm">Không load được dữ liệu gia phả. (' + e.message + ')</p>';
            });

        function initTree(rawNodes) {
            if (! rawNodes.length) {
                cont.innerHTML = '<p class="text-center py-20 text-gray-400 text-sm">Chưa có thành viên nào.</p>';
                return;
            }

            if (! window.f3 || typeof window.f3.createChart !== 'function') {
                console.error('f3.createChart not found. f3:', window.f3);
                cont.innerHTML = '<p class="text-center py-20 text-gray-400 text-sm">Không load được thư viện. Vui lòng tải lại trang.</p>';
                return;
            }

            // Chuyển sang family-chart data format
            const data = rawNodes.map(n => ({
                id:   String(n.id),
                rels: {
                    father:   n.fid  ? String(n.fid)  : undefined,
                    mother:   n.mid  ? String(n.mid)  : undefined,
                    spouses:  (n.pids || []).map(String),
                    children: rawNodes
                        .filter(c => String(c.fid) === String(n.id) || String(c.mid) === String(n.id))
                        .map(c => String(c.id)),
                },
                data: {
                    'first name': n.name,
                    'last name':  '',
                    birthday:     n.birth_year ? String(n.birth_year) : '',
                    death_year:   n.death_year ? String(n.death_year) : '',
                    gender:       n.gender === 'female' ? 'F' : 'M',
                    avatar:       '',
                    // custom fields
                    edit_url:     n.edit_url  || '',
                    event_url:    n.event_url || '',
                    has_event:    !! n.event_url,
                    is_deceased:  !! n.death_year,
                },
            }));

            // family-chart v0.9 API: createChart(selector_or_element, data)
            const f3Chart = window.f3.createChart(cont, data)
                .setTransitionTime(600)
                .setCardXSpacing(220)
                .setCardYSpacing(80);

            const f3Card = f3Chart.setCardHtml()
                .setCardInnerHtmlCreator(function(d) {
                    // d.data = tree node; d.data.data = person fields
                    const m    = d.data.data;
                    const yrs  = [m.birthday, m.death_year].filter(Boolean).join('–');
                    const dead = m.is_deceased;
                    return `
                        <div style="
                            background:${dead ? '#f9fafb' : '#fff'};
                            border:1px solid ${dead ? '#d1d5db' : '#c7d2fe'};
                            border-radius:10px;padding:10px 16px;
                            min-width:160px;text-align:center;cursor:pointer;
                            box-shadow:0 2px 6px rgba(0,0,0,.06);
                        " onclick="window.location.href='${m.edit_url}'" title="Sửa thông tin">
                            <div style="font-size:14px;font-weight:600;color:${dead ? '#6b7280' : '#1f2937'}">${m['first name'] || '?'}</div>
                            ${yrs ? `<div style="font-size:11px;color:#9ca3af;margin-top:3px">${yrs}</div>` : ''}
                            ${m.has_event ? `<div style="font-size:11px;color:#7c3aed;margin-top:4px">📅 Ngày giỗ</div>` : ''}
                        </div>`;
                });

            f3Chart.updateTree({ initial: true });
        }
    })();
    </script>
    @endpush
@endif

</x-app-layout>
