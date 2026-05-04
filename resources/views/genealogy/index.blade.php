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

        {{-- Tree View --}}
        <div x-show="tab === 'tree'">
            <div class="flex items-center justify-end mb-2 gap-2">
                <a href="{{ route('print-orders.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    In Gia Phả
                </a>
                <button id="export-png-btn"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Xuất PNG
                </button>
            </div>
            <div class="bg-white rounded-xl border border-gray-200" style="height:600px;position:relative;overflow:hidden;">
                <div id="family-tree" class="f3" style="width:100%;height:100%;"></div>
            </div>
            <p class="text-xs text-gray-400 mt-2 text-center">
                Click card để focus · Scroll zoom · Kéo di chuyển
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

    {{-- Popup thêm quan hệ — ngoài tree container để không bị overflow:hidden clip --}}
    <div id="gp-add-rel-overlay"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:14px;padding:24px;width:340px;box-shadow:0 24px 64px rgba(0,0,0,.25);"
             onclick="event.stopPropagation()">
            <h3 style="font-size:15px;font-weight:600;color:#111827;margin:0 0 4px">Thêm quan hệ</h3>
            <p id="gp-add-rel-ctx" style="font-size:12px;color:#6b7280;margin:0 0 16px"></p>
            <form id="gp-add-rel-form" autocomplete="off">
                <div style="margin-bottom:12px">
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Quan hệ</label>
                    <select name="type" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff">
                        <option value="father">Cha</option>
                        <option value="mother">Mẹ</option>
                        <option value="spouse">Vợ / Chồng</option>
                        <option value="son">Con trai</option>
                        <option value="daughter">Con gái</option>
                    </select>
                </div>
                <div style="margin-bottom:12px">
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Họ và tên *</label>
                    <input name="name" required placeholder="Nguyễn Văn A"
                           style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                </div>
                <div style="margin-bottom:12px">
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Danh xưng</label>
                    <input name="pronoun" placeholder="VD: Cụ ông, Ông, Bà..."
                           style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                </div>
                <div style="display:flex;gap:8px;margin-bottom:20px">
                    <div style="flex:1">
                        <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Năm sinh</label>
                        <input name="birth_year" type="number" min="1800" max="2100" placeholder="VD: 1945"
                               style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                    </div>
                    <div style="flex:1">
                        <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Năm mất</label>
                        <input name="death_year" type="number" min="1800" max="2100" placeholder="VD: 2010"
                               style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                    </div>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" id="gp-add-rel-cancel"
                            style="padding:8px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;cursor:pointer;background:#fff;color:#374151">
                        Hủy
                    </button>
                    <button type="submit" id="gp-add-rel-submit"
                            style="padding:8px 20px;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer">
                        Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup sửa thành viên --}}
    <div id="gp-edit-member-overlay"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:14px;padding:24px;width:340px;box-shadow:0 24px 64px rgba(0,0,0,.25);"
             onclick="event.stopPropagation()">
            <h3 style="font-size:15px;font-weight:600;color:#111827;margin:0 0 16px">Sửa thông tin nhanh</h3>
            <form id="gp-edit-member-form" autocomplete="off">
                <input type="hidden" name="id">
                <div style="margin-bottom:12px">
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Danh xưng</label>
                    <input name="pronoun" placeholder="VD: Cụ ông, Ông, Bà..."
                           style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                </div>
                <div style="margin-bottom:12px">
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Họ và tên *</label>
                    <input name="name" required placeholder="Nguyễn Văn A"
                           style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                </div>
                <div style="margin-bottom:12px">
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Giới tính</label>
                    <select name="gender" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff">
                        <option value="unknown">Không rõ</option>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;margin-bottom:20px">
                    <div style="flex:1">
                        <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Năm sinh</label>
                        <input name="birth_year" type="number" min="1800" max="2100" placeholder="VD: 1945"
                               style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                    </div>
                    <div style="flex:1">
                        <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px">Năm mất</label>
                        <input name="death_year" type="number" min="1800" max="2100" placeholder="VD: 2010"
                               style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box">
                    </div>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" id="gp-edit-member-cancel"
                            style="padding:8px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;cursor:pointer;background:#fff;color:#374151">
                        Hủy
                    </button>
                    <button type="submit" id="gp-edit-member-submit"
                            style="padding:8px 20px;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer">
                        Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="/vendor/family-chart.css">
    <style>
        #family-tree path.link { stroke: #6b7280 !important; stroke-width: 2px !important; opacity: 1 !important; }
        #family-tree svg.main_svg { background: #fff !important; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    <script>
    (function() {
        const dataUrl    = '{{ route('genealogy.data') }}';
        const cont       = document.getElementById('family-tree');
        const csrfToken  = '{{ csrf_token() }}';
        const baseUrl    = '{{ url('genealogy') }}';

        // ── Popup thêm quan hệ (setup một lần) ─────────────────────────
        let pendingId = null;
        const overlay    = document.getElementById('gp-add-rel-overlay');
        const popupForm  = document.getElementById('gp-add-rel-form');
        const ctxLabel   = document.getElementById('gp-add-rel-ctx');
        const submitBtn  = document.getElementById('gp-add-rel-submit');

        window.gpOpenAddRel = function(id, name) {
            pendingId = id;
            ctxLabel.textContent = 'Cho: ' + name;
            popupForm.reset();
            overlay.style.display = 'flex';
            popupForm.querySelector('[name="name"]').focus();
        };

        window.gpDeleteMember = async function(deleteUrl, name) {
            if (! confirm(`Xóa "${name}" khỏi gia phả?\nThao tác này không thể hoàn tác.`)) return;
            try {
                const res = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                if (! res.ok) throw new Error('HTTP ' + res.status);
                const fresh = await fetch(dataUrl, { headers: { Accept: 'application/json' } }).then(r => r.json());
                initTree(fresh);
            } catch (err) {
                console.error('Delete failed:', err);
                alert('Có lỗi xảy ra khi xóa.');
            }
        };

        document.getElementById('gp-add-rel-cancel').addEventListener('click', closePopup);
        overlay.addEventListener('click', e => { if (e.target === overlay) closePopup(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closePopup(); });

        function closePopup() {
            overlay.style.display = 'none';
            pendingId = null;
        }

        popupForm.addEventListener('submit', async e => {
            e.preventDefault();
            if (! pendingId) return;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang lưu...';
            try {
                const fd  = new FormData(popupForm);
                const res = await fetch(`${baseUrl}/${pendingId}/add-relative`, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body:    JSON.stringify({
                        type:       fd.get('type'),
                        name:       fd.get('name').trim(),
                        pronoun:    fd.get('pronoun').trim() || null,
                        birth_year: fd.get('birth_year') ? parseInt(fd.get('birth_year')) : null,
                        death_year: fd.get('death_year') ? parseInt(fd.get('death_year')) : null,
                    }),
                });
                if (! res.ok) throw new Error('HTTP ' + res.status);
                closePopup();
                // Reload tree với dữ liệu mới
                const fresh = await fetch(dataUrl, { headers: { Accept: 'application/json' } }).then(r => r.json());
                initTree(fresh);
            } catch (err) {
                console.error('Add relative failed:', err);
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Lưu';
            }
        });

        // ── Popup sửa thành viên ──────────────────────────────────────
        const editOverlay = document.getElementById('gp-edit-member-overlay');
        const editForm    = document.getElementById('gp-edit-member-form');
        const editSubmitBtn = document.getElementById('gp-edit-member-submit');
        window.gpOpenEditMember = function(id) {
            const node = window.treeNodes.find(n => n.id == id);
            if (!node) return;

            editForm.elements['id'].value = node.id;
            editForm.elements['name'].value = node.name || '';
            editForm.elements['pronoun'].value = node.pronoun || '';

            // Map gender
            let g = 'unknown';
            if (node.gender === 'male' || node.gender === 'M') g = 'male';
            if (node.gender === 'female' || node.gender === 'F') g = 'female';
            editForm.elements['gender'].value = g;

            editForm.elements['birth_year'].value = node.birth_year || '';
            editForm.elements['death_year'].value = node.death_year || '';

            editOverlay.style.display = 'flex';
            editForm.elements['name'].focus();
        };
        document.getElementById('gp-edit-member-cancel').addEventListener('click', closeEditPopup);
        editOverlay.addEventListener('click', e => { if (e.target === editOverlay) closeEditPopup(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditPopup(); });
        function closeEditPopup() {
            editOverlay.style.display = 'none';
        }
        editForm.addEventListener('submit', async e => {
            e.preventDefault();
            editSubmitBtn.disabled = true;
            editSubmitBtn.textContent = 'Đang lưu...';
            try {
                const fd = new FormData(editForm);
                const res = await fetch('{{ route('genealogy.save') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        action:     'update_member',
                        id:         fd.get('id'),
                        name:       fd.get('name').trim(),
                        pronoun:    fd.get('pronoun').trim() || null,
                        gender:     fd.get('gender'),
                        birth_year: fd.get('birth_year') ? parseInt(fd.get('birth_year')) : null,
                        death_year: fd.get('death_year') ? parseInt(fd.get('death_year')) : null,
                    }),
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                closeEditPopup();
                const fresh = await fetch(dataUrl, { headers: { Accept: 'application/json' } }).then(r => r.json());
                initTree(fresh);
            } catch (err) {
                console.error('Update member failed:', err);
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            } finally {
                editSubmitBtn.disabled = false;
                editSubmitBtn.textContent = 'Lưu';
            }
        });

        // ── Tree rendering ───────────────────────────────────────────────
        let f3Chart = null; // Lưu reference để destroy khi reload
        let exportBtnListenerAdded = false; // Đánh dấu đã add event listener chưa

        fetch(dataUrl, { headers: { Accept: 'application/json' } })
            .then(r => { if (! r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(initTree)
            .catch(e => {
                cont.innerHTML = `<p class="text-center py-20 text-gray-400 text-sm">Không load được dữ liệu gia phả. (${e.message})</p>`;
            });

        function initTree(rawNodes) {
            if (! rawNodes.length) {
                cont.innerHTML = '<p class="text-center py-20 text-gray-400 text-sm">Chưa có thành viên nào.</p>';
                return;
            }
            if (! window.f3?.createChart) {
                cont.innerHTML = '<p class="text-center py-20 text-gray-400 text-sm">Không load được thư viện. Vui lòng tải lại trang.</p>';
                return;
            }

            // Destroy chart cũ + xóa sạch container để tránh event listener cũ bị chồng
            if (f3Chart) {
                try { f3Chart.destroy(); } catch (e) {}
                f3Chart = null;
            }
            cont.innerHTML = '';
            const validNodes = rawNodes.filter(n => n && n.id);
            window.treeNodes = validNodes;
            const validIds   = new Set(validNodes.map(n => String(n.id)));

            const data = validNodes.map(n => {
                const nId      = String(n.id);
                const spouseIds = (n.pids || []).map(String).filter(id => validIds.has(id));

                // Tính children theo 2 chiều:
                // - child trực tiếp tham chiếu n là cha/mẹ
                // - child của spouse của n (để 2 vợ chồng có cùng list children)
                const children = validNodes
                    .filter(c => {
                        const cFid = c.fid ? String(c.fid) : null;
                        const cMid = c.mid ? String(c.mid) : null;
                        if (cFid === nId || cMid === nId) return true;
                        return spouseIds.some(sid => cFid === sid || cMid === sid);
                    })
                    .map(c => String(c.id));

                return {
                id:   nId,
                rels: {
                    father:   n.fid && validIds.has(String(n.fid)) ? String(n.fid) : undefined,
                    mother:   n.mid && validIds.has(String(n.mid)) ? String(n.mid) : undefined,
                    spouses:  spouseIds,
                    children,
                },
                data: {
                    'first name': n.name,
                    pronoun:     n.pronoun    || '',
                    birthday:    n.birth_year ? String(n.birth_year) : '',
                    death_day:  n.death_day ? String(n.death_day) : '',
                    death_month:  n.death_month ? String(n.death_month) : '',
                    death_year:  n.death_year ? String(n.death_year) : '',
                    death_date_type:  n.death_date_type ? String(n.death_date_type) : '',
                    gender:      n.gender === 'female' ? 'F' : 'M',
                    member_id:   n.id,
                    edit_url:    n.edit_url    || '',
                    delete_url:  n.delete_url  || '',
                    has_event:   !! n.event_url,
                    is_alive:    !! n.is_alive,
                },
                };
            });
            console.log(data);

            f3Chart = window.f3.createChart(cont, data)
                .setTransitionTime(600)
                .setCardXSpacing(260)
                .setCardYSpacing(150);

            f3Chart.setCardHtml()
                .setCardInnerHtmlCreator(function(d) {
                    const m       = d.data.data;
                    console.log(m);
                    const dead    = !m.is_alive;
                    const deadDateType = m.death_date_type === 'lunar' ? 'Âm lịch' : 'Dương Lịch';
                    const yrs     = dead ? `${m.death_day}/${m.death_month} ${deadDateType}` : m.birthday;
                    const isFemale = m.gender === 'F';
                    const genderDot = isFemale
                        ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f472b6;margin-left:5px;vertical-align:middle" title="Nữ"></span>'
                        : '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#60a5fa;margin-left:5px;vertical-align:middle" title="Nam"></span>';
                    const borderColor = dead ? '#d1d5db' : (isFemale ? '#fbcfe8' : '#c7d2fe');
                    const display = m.pronoun ? `${m.pronoun}<br>${m['first name']}` : (m['first name'] || '?');
                    const safeName = display.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                    return `<div style="
                        background:${dead ? '#f9fafb' : '#fff'};
                        border:1px solid ${borderColor};
                        border-radius:10px;padding:10px 14px;min-width:180px;text-align:center;
                        box-shadow:0 2px 6px rgba(0,0,0,.06);">
                        <div style="font-size:13px;font-weight:600;color:${dead ? '#6b7280' : '#1f2937'};line-height:1.4;margin-bottom:2px">${display}${genderDot}</div>
                        ${yrs ? `<div style="font-size:11px;color:#9ca3af">${yrs}</div>` : ''}
                        ${m.has_event ? `<div style="font-size:11px;color:#7c3aed;margin-top:2px">📅 Ngày giỗ</div>` : ''}
                        <div data-actions style="display:flex;gap:6px;justify-content:center;margin-top:8px;padding-top:7px;border-top:1px solid #f3f4f6">
                            <a href="${m.edit_url}" onclick="event.stopPropagation();"
                               style="font-size:11px;color:#6366f1;text-decoration:none;padding:2px 8px;border:1px solid #e0e7ff;border-radius:5px">
                                Chi tiết
                            </a>
                            <button onclick="event.stopPropagation(); window.gpOpenEditMember(${m.member_id})"
                                    style="font-size:11px;color:#ca8a04;background:none;border:1px solid #fef08a;border-radius:5px;padding:2px 8px;cursor:pointer">
                                Sửa
                            </button>
                            <button onclick="event.stopPropagation(); window.gpOpenAddRel(${m.member_id}, '${safeName}')"
                                    style="font-size:11px;color:#059669;background:none;border:1px solid #d1fae5;border-radius:5px;padding:2px 8px;cursor:pointer">
                                + Thêm
                            </button>
                            <button onclick="event.stopPropagation(); window.gpDeleteMember('${m.delete_url}', '${safeName}')"
                                    style="font-size:11px;color:#dc2626;background:none;border:1px solid #fee2e2;border-radius:5px;padding:2px 8px;cursor:pointer">
                                Xóa
                            </button>
                        </div>
                    </div>`;
                });

            f3Chart.updateTree({ initial: true });

            // PNG export - chỉ add event listener một lần
            if (! exportBtnListenerAdded) {
                document.getElementById('export-png-btn')?.addEventListener('click', async () => {
                    if (! window.html2canvas) { alert('Thư viện xuất ảnh chưa sẵn sàng.'); return; }
                    const btn = document.getElementById('export-png-btn');
                    btn.disabled = true;
                    btn.textContent = 'Đang xuất...';
                    // Ẩn các nút thao tác trước khi chụp
                    const actionBars = cont.querySelectorAll('[data-actions]');
                    actionBars.forEach(el => el.style.display = 'none');
                    try {
                        const canvas = await html2canvas(cont, { backgroundColor: '#ffffff', scale: 2, useCORS: true, logging: false });
                        const a = document.createElement('a');
                        a.download = 'gia-pha.png';
                        a.href = canvas.toDataURL('image/png');
                        a.click();
                    } catch { alert('Không thể xuất PNG.'); }
                    finally {
                        // Hiện lại các nút sau khi xuất xong
                        actionBars.forEach(el => el.style.display = '');
                        btn.disabled = false;
                        btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> Xuất PNG`;
                    }
                });
                exportBtnListenerAdded = true;
            }
        }
    })();
    </script>
    @endpush
@endif

</x-app-layout>
