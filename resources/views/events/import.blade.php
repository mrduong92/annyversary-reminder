<x-app-layout>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('events.index') }}" class="hover:text-gray-700">Ngày giỗ</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700">Nhập từ ảnh</span>
    </div>
    <h1 class="text-2xl font-semibold text-gray-900">Nhập ngày giỗ từ ảnh</h1>
    <p class="text-sm text-gray-500 mt-0.5">Chụp ảnh sổ tay, tờ giấy ghi ngày giỗ — AI sẽ đọc và tạo tự động</p>
</div>

<div x-data="importTool()" class="space-y-6">

    {{-- Bước 1: Upload ảnh --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6" x-show="step === 1">
        <h2 class="font-semibold text-gray-900 text-sm mb-4">Bước 1: Chọn ảnh</h2>

        <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
            @drop.prevent="handleDrop($event)"
            @click="$refs.fileInput.click()"
            :class="dragging ? 'border-primary-400 bg-primary-50' : 'border-gray-300 hover:border-primary-300 hover:bg-gray-50'"
            class="border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-colors">

            <template x-if="!previewUrl">
                <div>
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-600 mb-1">Kéo thả ảnh vào đây hoặc click để chọn</p>
                    <p class="text-xs text-gray-400">JPG, PNG, WEBP · Tối đa 10MB</p>
                </div>
            </template>

            <template x-if="previewUrl">
                <div>
                    <img :src="previewUrl" alt="Preview" class="max-h-64 mx-auto rounded-lg object-contain mb-2">
                    <p class="text-xs text-gray-400" x-text="fileName"></p>
                </div>
            </template>

            <input type="file" x-ref="fileInput" class="hidden" accept="image/*"
                @change="handleFileSelect($event)">
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button @click="analyzeImage()" :disabled="!file || analyzing"
                class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                <template x-if="analyzing">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <span x-text="analyzing ? 'AI đang đọc ảnh...' : 'Phân tích ảnh'"></span>
            </button>
            <a href="{{ route('events.index') }}" class="text-sm text-gray-500 hover:underline">Hủy</a>
        </div>

        <template x-if="errorMsg">
            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="errorMsg"></div>
        </template>
    </div>

    {{-- Bước 2: Review & edit --}}
    <div x-show="step === 2" x-cloak>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                <div>
                    <h2 class="font-semibold text-gray-900 text-sm">Bước 2: Kiểm tra & chỉnh sửa</h2>
                    <p class="text-xs text-gray-500 mt-0.5">AI đọc được <span x-text="events.length" class="font-medium text-primary-600"></span> ngày giỗ. Kiểm tra lại trước khi lưu.</p>
                </div>
                <button @click="step = 1; events = []" class="text-xs text-gray-400 hover:text-gray-600 hover:underline">
                    ← Chọn ảnh khác
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left w-8">#</th>
                            <th class="px-4 py-3 text-left">Danh xưng</th>
                            <th class="px-4 py-3 text-left">Tên</th>
                            <th class="px-4 py-3 text-left">Quan hệ</th>
                            <th class="px-4 py-3 text-left w-24">Ngày</th>
                            <th class="px-4 py-3 text-left w-24">Tháng</th>
                            <th class="px-4 py-3 text-left w-28">Loại lịch</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(event, i) in events" :key="i">
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-2.5 text-gray-400 text-xs" x-text="i + 1"></td>
                                <td class="px-4 py-2.5">
                                    <input type="text" x-model="event.pronoun" placeholder="Cụ, Ông..."
                                        class="w-full text-sm border-0 border-b border-gray-200 focus:border-primary-400 outline-none bg-transparent py-0.5">
                                </td>
                                <td class="px-4 py-2.5">
                                    <input type="text" x-model="event.name" placeholder="Tên người mất"
                                        class="w-full text-sm border-0 border-b border-gray-200 focus:border-primary-400 outline-none bg-transparent py-0.5">
                                </td>
                                <td class="px-4 py-2.5">
                                    <input type="text" x-model="event.relationship" placeholder="Quan hệ" list="rel-list"
                                        class="w-full text-sm border-0 border-b border-gray-200 focus:border-primary-400 outline-none bg-transparent py-0.5">
                                </td>
                                <td class="px-4 py-2.5">
                                    <input type="number" x-model.number="event.lunar_day" min="1" max="31"
                                        class="w-full text-sm border-0 border-b border-gray-200 focus:border-primary-400 outline-none bg-transparent py-0.5 text-center">
                                </td>
                                <td class="px-4 py-2.5">
                                    <input type="number" x-model.number="event.lunar_month" min="1" max="12"
                                        class="w-full text-sm border-0 border-b border-gray-200 focus:border-primary-400 outline-none bg-transparent py-0.5 text-center">
                                </td>
                                <td class="px-4 py-2.5">
                                    <select x-model="event.date_type"
                                        class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-primary-400 outline-none bg-white">
                                        <option value="lunar">Âm lịch</option>
                                        <option value="solar">Dương lịch</option>
                                    </select>
                                </td>
                                <td class="px-4 py-2.5">
                                    <button @click="events.splice(i, 1)" class="text-red-300 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Add row --}}
            <div class="px-5 py-3 border-t border-gray-50">
                <button @click="addRow()"
                    class="text-xs text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Thêm dòng
                </button>
            </div>
        </div>

        {{-- Confirm --}}
        <div class="flex items-center gap-3 mt-4">
            <button @click="confirmImport()" :disabled="events.length === 0 || saving"
                class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                <template x-if="saving">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span x-text="saving ? 'Đang lưu...' : 'Lưu ' + events.length + ' ngày giỗ'"></span>
            </button>
            <p class="text-xs text-gray-400">Dữ liệu sẽ được thêm vào nhóm "{{ $activeFamilyGroup->name }}"</p>
        </div>

        <template x-if="saveErrors.length > 0">
            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <template x-for="e in saveErrors"><p x-text="e"></p></template>
            </div>
        </template>
    </div>

</div>

{{-- Datalist cho quan hệ --}}
<datalist id="rel-list">
    <option value="Ông nội"><option value="Bà nội"><option value="Ông ngoại"><option value="Bà ngoại">
    <option value="Bố"><option value="Mẹ"><option value="Chú"><option value="Cô"><option value="Dì"><option value="Cậu">
    <option value="Bác"><option value="Anh"><option value="Chị">
</datalist>

@push('scripts')
<script>
function importTool() {
    return {
        step: 1,
        file: null,
        fileName: '',
        previewUrl: null,
        dragging: false,
        analyzing: false,
        saving: false,
        events: [],
        errorMsg: '',
        saveErrors: [],

        handleFileSelect(e) {
            this.setFile(e.target.files[0]);
        },
        handleDrop(e) {
            this.dragging = false;
            this.setFile(e.dataTransfer.files[0]);
        },
        setFile(f) {
            if (!f || !f.type.startsWith('image/')) return;
            this.file = f;
            this.fileName = f.name;
            this.previewUrl = URL.createObjectURL(f);
            this.errorMsg = '';
        },

        async analyzeImage() {
            if (!this.file) return;
            this.analyzing = true;
            this.errorMsg = '';

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const form = new FormData();
            form.append('image', this.file);

            try {
                const res = await fetch('{{ route('events.import.preview') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    body: form,
                });
                const data = await res.json();

                if (!res.ok) {
                    this.errorMsg = data.error || 'Có lỗi xảy ra.';
                    return;
                }

                this.events = data.events;
                this.step = 2;
            } catch {
                this.errorMsg = 'Không kết nối được. Vui lòng thử lại.';
            } finally {
                this.analyzing = false;
            }
        },

        addRow() {
            this.events.push({ pronoun: '', name: '', relationship: '', lunar_day: 1, lunar_month: 1, date_type: 'lunar' });
        },

        async confirmImport() {
            this.saving = true;
            this.saveErrors = [];

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            try {
                const res = await fetch('{{ route('events.import.confirm') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ events: this.events }),
                });
                const data = await res.json();

                if (data.errors?.length) this.saveErrors = data.errors;

                if (data.created > 0) {
                    window.location.href = data.redirect + '?imported=' + data.created;
                }
            } catch {
                this.saveErrors = ['Có lỗi xảy ra. Vui lòng thử lại.'];
            } finally {
                this.saving = false;
            }
        },
    }
}
</script>
@endpush

</x-app-layout>
