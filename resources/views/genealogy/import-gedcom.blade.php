<x-app-layout>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('genealogy.index') }}" class="hover:text-gray-700">Gia phả</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700">Import GEDCOM</span>
    </div>
    <h1 class="text-2xl font-semibold text-gray-900">Import gia phả từ file GEDCOM</h1>
    <p class="text-sm text-gray-500 mt-0.5">
        Tương thích với Ancestry, FamilySearch, MyHeritage, MacFamilyTree, Gramps và các app gia phả phổ biến.
    </p>
</div>

<div x-data="gedcomImport()" class="max-w-2xl space-y-5">

    {{-- Upload --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6" x-show="step === 1">
        <h2 class="font-semibold text-gray-900 text-sm mb-4">Chọn file .ged</h2>

        <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
             @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()"
             :class="dragging ? 'border-primary-400 bg-primary-50' : 'border-gray-300 hover:border-primary-300'"
             class="border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-colors">
            <template x-if="!fileName">
                <div>
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-600 mb-1">Kéo thả file .ged vào đây hoặc click để chọn</p>
                    <p class="text-xs text-gray-400">Định dạng .ged hoặc .txt · Tối đa 10MB</p>
                </div>
            </template>
            <template x-if="fileName">
                <div>
                    <svg class="w-8 h-8 text-primary-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-800" x-text="fileName"></p>
                </div>
            </template>
            <input type="file" x-ref="fileInput" class="hidden" accept=".ged,.txt"
                   @change="handleFileSelect($event)">
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button @click="previewFile()" :disabled="!file || loading"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2">
                <template x-if="loading">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <span x-text="loading ? 'Đang đọc...' : 'Xem trước'"></span>
            </button>
            <a href="{{ route('genealogy.index') }}" class="text-sm text-gray-500 hover:underline">Hủy</a>
        </div>

        <template x-if="errorMsg">
            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="errorMsg"></div>
        </template>
    </div>

    {{-- Preview --}}
    <div x-show="step === 2" x-cloak>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 bg-green-50 border-b border-green-100 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-green-800 text-sm">
                        Tìm thấy <span x-text="total" class="text-green-700"></span> thành viên
                        và <span x-text="totalFam" class="text-green-700"></span> gia đình
                    </p>
                    <p class="text-xs text-green-600 mt-0.5">Xem trước 15 thành viên đầu tiên. Import sẽ thêm vào gia phả hiện tại.</p>
                </div>
                <button @click="step = 1; file = null; fileName = ''" class="text-xs text-green-700 hover:underline">← Chọn lại</button>
            </div>

            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left">Tên</th>
                        <th class="px-4 py-3 text-left">Giới tính</th>
                        <th class="px-4 py-3 text-left">Năm sinh</th>
                        <th class="px-4 py-3 text-left">Năm mất</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="p in preview">
                        <tr>
                            <td class="px-4 py-2.5 font-medium text-gray-800" x-text="p.name"></td>
                            <td class="px-4 py-2.5 text-gray-500" x-text="p.sex === 'female' ? 'Nữ' : 'Nam'"></td>
                            <td class="px-4 py-2.5 text-gray-500" x-text="p.birth_year || '—'"></td>
                            <td class="px-4 py-2.5 text-gray-500" x-text="p.death_year || (p.death ? '(Đã mất)' : '—')"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <p x-show="total > 15" class="px-5 py-3 text-xs text-gray-400 border-t border-gray-50"
               x-text="'... và ' + (total - 15) + ' thành viên khác'"></p>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button @click="confirmImport()" :disabled="loading"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-40 flex items-center gap-2">
                <template x-if="loading">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span x-text="loading ? 'Đang import...' : 'Import ' + total + ' thành viên'"></span>
            </button>
        </div>

        <template x-if="errorMsg">
            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="errorMsg"></div>
        </template>
    </div>

</div>

@push('scripts')
<script>
function gedcomImport() {
    return {
        step: 1, file: null, fileName: '', dragging: false,
        loading: false, errorMsg: '',
        total: 0, totalFam: 0, preview: [],

        handleFileSelect(e) { this.setFile(e.target.files[0]); },
        handleDrop(e) { this.dragging = false; this.setFile(e.dataTransfer.files[0]); },
        setFile(f) {
            if (!f) return;
            this.file = f; this.fileName = f.name; this.errorMsg = '';
        },

        async previewFile() {
            if (!this.file) return;
            this.loading = true; this.errorMsg = '';
            const form = new FormData();
            form.append('gedcom', this.file);
            try {
                const res  = await fetch('{{ route('genealogy.import-gedcom.preview') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: form,
                });
                const data = await res.json();
                if (!res.ok) { this.errorMsg = data.error || 'Có lỗi xảy ra.'; return; }
                this.total = data.total_individuals;
                this.totalFam = data.total_families;
                this.preview = data.preview;
                this.step = 2;
            } catch { this.errorMsg = 'Không kết nối được. Vui lòng thử lại.'; }
            finally { this.loading = false; }
        },

        async confirmImport() {
            this.loading = true; this.errorMsg = '';
            const form = new FormData();
            form.append('gedcom', this.file);
            try {
                const res  = await fetch('{{ route('genealogy.import-gedcom.confirm') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: form,
                });
                const data = await res.json();
                if (!res.ok) { this.errorMsg = data.error || 'Có lỗi xảy ra.'; return; }
                window.location.href = data.redirect + '?imported=' + data.created;
            } catch { this.errorMsg = 'Có lỗi xảy ra. Vui lòng thử lại.'; }
            finally { this.loading = false; }
        },
    };
}
</script>
@endpush

</x-app-layout>
