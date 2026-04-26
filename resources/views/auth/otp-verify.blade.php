<x-guest-layout>
    <h2 class="text-xl font-semibold text-gray-900 mb-1">Nhập mã OTP</h2>
    <p class="text-sm text-gray-500 mb-6">
        Mã 6 số đã gửi đến <span class="font-medium text-gray-700">{{ $phone }}</span>
    </p>

    @if ($isLocal)
    <div class="flex items-center gap-2 p-3 mb-5 text-sm text-amber-800 bg-amber-50 rounded-lg border border-amber-200">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        <span><strong>Local:</strong> dùng mã <span class="font-mono font-bold tracking-widest">000000</span></span>
    </div>
    @endif

    <form method="POST" action="{{ route('otp.verify.submit') }}"
        x-data="otpForm()" @submit.prevent="submit">
        @csrf

        <div class="mb-5">
            <label for="otp" class="block mb-1.5 text-sm font-medium text-gray-700">Mã xác thực</label>
            <input
                type="text"
                id="otp"
                name="otp"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autofocus
                autocomplete="one-time-code"
                placeholder="000000"
                x-model="otp"
                @input="onInput"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-2xl font-mono tracking-[0.5em] rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-3 text-center @error('otp') border-red-500 @enderror"
            >
            @error('otp')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            x-bind:disabled="loading"
            class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
            <span x-show="!loading">Xác nhận</span>
            <span x-show="loading" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Đang xác nhận...
            </span>
        </button>
    </form>

    <div class="mt-5 text-center">
        <a href="{{ $action === 'register' ? route('register') : route('login') }}"
            class="text-sm text-gray-400 hover:text-gray-600 hover:underline">
            ← Nhập lại số điện thoại
        </a>
    </div>

    @push('scripts')
    <script>
    function otpForm() {
        return {
            otp: '{{ $isLocal ? '000000' : old('otp', '') }}',
            loading: false,
            onInput() {
                this.otp = this.otp.replace(/\D/g, '').slice(0, 6);
                if (this.otp.length === 6) this.submit();
            },
            submit() {
                if (this.otp.length !== 6) return;
                this.loading = true;
                this.$el.submit();
            }
        }
    }
    </script>
    @endpush
</x-guest-layout>
