<script setup>
import PanitiaLayout from '@/Layouts/PanitiaLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { Html5Qrcode } from 'html5-qrcode';

const props = defineProps({
    activePeriode: Object,
    stats: Object,
    recentAttendance: Array,
});

const isCameraActive = ref(false);
const cameraError = ref(null);
const isProcessing = ref(false);
const manualToken = ref('');
const manualInputRef = ref(null);

const popup = ref({
    show: false,
    type: 'success',
    title: '',
    message: '',
    data: null,
    guestData: null,
    countdown: 3,
});
let popupTimer = null;
let popupCountdownInterval = null;

let html5QrCode = null;
let lastScannedCode = '';
let lastScannedTime = 0;
let keyBuffer = '';
let keyTimer = null;
let audioCtx = null;

const getAudioContext = () => {
    if (!audioCtx) {
        const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
        if (AudioCtxClass) {
            audioCtx = new AudioCtxClass();
        }
    }
    if (audioCtx && audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    return audioCtx;
};

const playSuccessSound = () => {
    try {
        if (navigator.vibrate) navigator.vibrate([80, 40, 80]);
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;

        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(880, now);
        gain1.gain.setValueAtTime(0.3, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.12);

        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(1320, now + 0.1);
        gain2.gain.setValueAtTime(0.35, now + 0.1);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.28);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.1);
        osc2.stop(now + 0.28);
    } catch (e) {}
};

const playErrorSound = () => {
    try {
        if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(220, now);
        osc.frequency.setValueAtTime(160, now + 0.15);
        gain.gain.setValueAtTime(0.35, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.35);
    } catch (e) {}
};

const playWarningSound = () => {
    try {
        if (navigator.vibrate) navigator.vibrate([150, 80, 150]);
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;

        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'triangle';
        osc1.frequency.setValueAtTime(587.33, now);
        gain1.gain.setValueAtTime(0.3, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.15);

        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'triangle';
        osc2.frequency.setValueAtTime(440, now + 0.15);
        gain2.gain.setValueAtTime(0.35, now + 0.15);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.15);
        osc2.stop(now + 0.35);
    } catch (e) {}
};

const showPopup = (type, title, message, data = null, guestData = null) => {
    if (popupTimer) clearTimeout(popupTimer);
    if (popupCountdownInterval) clearInterval(popupCountdownInterval);

    const countdownDuration = type === 'success' ? 3 : 4;

    popup.value = {
        show: true,
        type,
        title,
        message,
        data,
        guestData,
        countdown: countdownDuration,
    };

    // Matikan kamera seketika saat informasi hasil scan muncul
    stopCamera();

    popupCountdownInterval = setInterval(() => {
        if (popup.value.countdown > 1) {
            popup.value.countdown--;
        }
    }, 1000);

    popupTimer = setTimeout(() => {
        closePopup();
    }, countdownDuration * 1000);
};

const closePopup = () => {
    popup.value.show = false;
    if (popupTimer) clearTimeout(popupTimer);
    if (popupCountdownInterval) clearInterval(popupCountdownInterval);
    // Aktifkan kembali kamera setelah timer habis atau tombol Selesai ditekan
    startCamera();
    nextTick(() => manualInputRef.value?.focus());
};

const submitScan = async (rawToken) => {
    // HARD LOCK: DO NOT scan if popup is open (timer not expired or waiting for button press) or already processing
    if (popup.value.show || isProcessing.value) return;

    const token = String(rawToken || '').trim();
    if (!token) return;

    const now = Date.now();
    if (lastScannedCode === token && now - lastScannedTime < 2500) return;
    lastScannedCode = token;
    lastScannedTime = now;

    getAudioContext();
    isProcessing.value = true;
    manualToken.value = '';

    try {
        const response = await axios.post(route('panitia.presensi.scan'), {
            qr_code_token: token,
        }, {
            headers: { 'Accept': 'application/json' }
        });

        if (response.data.status === 'success') {
            playSuccessSound();
            showPopup(
                'success',
                'PRESENSI BERHASIL',
                response.data.message,
                response.data.scanned_data || null,
                response.data.guest_data || null
            );
        } else if (response.data.status === 'already_scanned') {
            playWarningSound();
            showPopup(
                'warning',
                'SUDAH PERNAH DI-SCAN',
                response.data.message,
                response.data.scanned_data || null,
                response.data.guest_data || null
            );
        } else {
            playErrorSound();
            showPopup('error', 'AKSES DITOLAK', response.data.message || 'Presensi gagal.');
        }
    } catch (error) {
        playErrorSound();
        const msg = error.response?.data?.message || 'QR Code / NIM Tidak Terdaftar!';
        showPopup('error', 'AKSES DITOLAK / INVALID', msg);
    } finally {
        isProcessing.value = false;
        nextTick(() => manualInputRef.value?.focus());
    }
};

const startCamera = async () => {
    cameraError.value = null;
    getAudioContext();
    try {
        if (isCameraActive.value) return;

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qr-reader');
        }
        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 15,
                qrbox: (w, h) => ({ width: Math.min(w, h) * 0.85, height: Math.min(w, h) * 0.85 })
            },
            (decodedText) => {
                submitScan(decodedText);
            },
            () => {}
        );
        isCameraActive.value = true;
    } catch (err) {
        cameraError.value = 'Tidak dapat mengakses kamera: ' + err;
        isCameraActive.value = false;
    }
};

const stopCamera = async () => {
    if (html5QrCode && isCameraActive.value) {
        try {
            await html5QrCode.stop();
        } catch (err) {
            console.error(err);
        } finally {
            isCameraActive.value = false;
        }
    }
};

const handleGlobalKeyDown = (e) => {
    if (e.target.tagName === 'INPUT' && e.target !== manualInputRef.value) return;

    if (e.key === 'Enter') {
        if (keyBuffer.trim().length > 0) {
            submitScan(keyBuffer.trim());
            keyBuffer = '';
        }
    } else if (e.key.length === 1) {
        keyBuffer += e.key;
        if (keyTimer) clearTimeout(keyTimer);
        keyTimer = setTimeout(() => { keyBuffer = ''; }, 800);
    }
};

onMounted(() => {
    startCamera();
    nextTick(() => manualInputRef.value?.focus());
    window.addEventListener('keydown', handleGlobalKeyDown);
});

onUnmounted(() => {
    stopCamera();
    window.removeEventListener('keydown', handleGlobalKeyDown);
    if (popupTimer) clearTimeout(popupTimer);
    if (popupCountdownInterval) clearInterval(popupCountdownInterval);
});
</script>

<template>
    <Head title="Presensi Gate Barcode Scanner" />

    <PanitiaLayout>
        <div class="space-y-6 relative">

            <!-- POPUP NOTIFICATION MODAL OVERLAY -->
            <transition name="pop">
                <div 
                    v-if="popup.show" 
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
                    @click.self="closePopup"
                >
                    <div 
                        class="w-full max-w-md rounded-3xl p-6 shadow-2xl border-2 text-center space-y-4 transform transition-all"
                        :class="popup.type === 'success' 
                            ? 'bg-slate-900 border-emerald-500/80 shadow-emerald-500/20 text-white' 
                            : (popup.type === 'warning' ? 'bg-slate-900 border-amber-500/80 shadow-amber-500/20 text-white' : 'bg-slate-900 border-rose-600 shadow-rose-600/30 text-white')"
                    >
                        <div class="flex flex-col items-center">
                            <div 
                                class="w-16 h-16 rounded-full flex items-center justify-center text-3xl font-black shadow-lg mb-2"
                                :class="popup.type === 'success' 
                                    ? 'bg-emerald-500 text-slate-950 shadow-emerald-500/50 animate-bounce' 
                                    : (popup.type === 'warning' ? 'bg-amber-500 text-slate-950 shadow-amber-500/50 animate-pulse' : 'bg-rose-500 text-white shadow-rose-500/50 animate-pulse')"
                            >
                                <span v-if="popup.type === 'success'">✓</span>
                                <span v-else-if="popup.type === 'warning'">⚠️</span>
                                <span v-else>✕</span>
                            </div>

                            <span 
                                class="text-xs font-black tracking-widest uppercase px-3 py-1 rounded-full border"
                                :class="popup.type === 'success' 
                                    ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40' 
                                    : (popup.type === 'warning' ? 'bg-amber-500/20 text-amber-400 border-amber-500/40' : 'bg-rose-500/20 text-rose-400 border-rose-500/40')"
                            >
                                {{ popup.title }}
                            </span>
                        </div>

                        <!-- Scanned Wisudawan Details -->
                        <div v-if="popup.data" class="space-y-3 bg-slate-950/80 rounded-2xl p-4 border border-slate-800 text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-16 h-16 rounded-xl bg-slate-800 border border-indigo-400/40 overflow-hidden flex items-center justify-center shrink-0">
                                    <img v-if="popup.data.pas_foto" :src="popup.data.pas_foto" class="w-full h-full object-cover" />
                                    <span v-else class="text-2xl text-slate-500">🎓</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block truncate">
                                        {{ popup.data.prodi }}
                                    </span>
                                    <h4 class="text-base font-black text-white leading-tight truncate">
                                        {{ popup.data.nama_lengkap }}
                                    </h4>
                                    <p class="text-xs font-mono text-slate-400">NIM: {{ popup.data.nim }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-[11px] pt-2 border-t border-slate-800/80">
                                <div class="bg-slate-900/90 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Orang Tua</span>
                                    <span class="font-bold text-slate-200 block truncate">{{ popup.data.nama_ibu || popup.data.nama_ayah || '-' }}</span>
                                </div>
                                <div class="bg-slate-900/90 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Status Kelulusan</span>
                                    <span class="font-bold text-emerald-400 block truncate">{{ popup.data.status_simanta || 'LULUS' }}</span>
                                </div>
                                <div class="bg-slate-900/90 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Kuota Tamu</span>
                                    <span class="font-bold text-amber-400 block">{{ popup.data.tamu_kuota }} Orang</span>
                                </div>
                                <div class="bg-slate-900/90 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Waktu Presensi</span>
                                    <span class="font-bold text-slate-200 block">{{ popup.data.waktu_presensi }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Scanned Guest Details -->
                        <div v-else-if="popup.guestData" class="space-y-2 bg-slate-950/80 rounded-2xl p-4 border border-slate-800 text-left">
                            <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block">Tamu Undangan</span>
                            <h4 class="text-base font-black text-white leading-tight">{{ popup.guestData.nama_tamu }}</h4>
                            <p class="text-xs text-slate-300">Hubungan: <span class="font-semibold text-white">{{ popup.guestData.hubungan || 'Keluarga' }}</span></p>
                            <p class="text-xs text-slate-400 pt-1 border-t border-slate-800">
                                Wisudawan: <span class="font-bold text-slate-200">{{ popup.guestData.wisudawan_nama }}</span> ({{ popup.guestData.wisudawan_nim }})
                            </p>
                        </div>

                        <!-- Error or Info Message -->
                        <div v-if="popup.message" class="text-xs text-slate-300 font-medium px-2 leading-relaxed">
                            {{ popup.message }}
                        </div>

                        <!-- Footer Action & Next Scan Indicator -->
                        <div class="pt-2 flex items-center justify-between gap-3">
                            <div class="text-[11px] text-slate-400 flex items-center gap-1.5 font-medium">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                <span>Siap scan berikutnya ({{ popup.countdown }}s)</span>
                            </div>
                            <button 
                                @click="closePopup"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition bg-slate-800 hover:bg-slate-700 text-white border border-slate-700"
                            >
                                ✓ Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
            
            <!-- Page Header Card & Navigation Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Scanner Presensi Gate Wisudawan</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Arahkan Barcode / QR Tiket Wisudawan ke layar kamera atau gunakan USB Barcode Scanner.
                    </p>
                </div>

                <!-- Sub-Navigation Switcher Tabs -->
                <div class="flex items-center bg-gray-100 dark:bg-gray-700/60 p-1 rounded-xl shrink-0">
                    <Link
                        :href="route('panitia.presensi')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm flex items-center gap-1.5"
                    >
                        <span>Gate Scanner</span>
                    </Link>
                    <Link
                        :href="route('panitia.presensi.wisudawan')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white flex items-center gap-1.5"
                    >
                        <span>Data Wisudawan</span>
                    </Link>
                </div>
            </div>

            <!-- EXCLUSIVE FULL-FIT CAMERA CONTAINER -->
            <div class="bg-slate-900 text-white rounded-3xl p-6 border border-slate-800 shadow-2xl space-y-5 max-w-lg mx-auto">
                
                <!-- Status Top Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full" :class="isCameraActive ? (isProcessing ? 'bg-amber-400 animate-ping' : 'bg-emerald-500 animate-pulse') : 'bg-rose-500'"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300">
                            {{ isProcessing ? 'Memproses Presensi...' : (isCameraActive ? 'Kamera Scanner Aktif (Bip On)' : 'Kamera Non-Aktif') }}
                        </span>
                    </div>

                    <button
                        @click="isCameraActive ? stopCamera() : startCamera()"
                        type="button"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition"
                        :class="isCameraActive ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 hover:bg-rose-500/30' : 'bg-indigo-600 text-white border-indigo-500 hover:bg-indigo-700'"
                    >
                        {{ isCameraActive ? '⏹ Matikan' : 'Aktifkan Kamera' }}
                    </button>
                </div>

                <!-- Full Fit Camera Viewport -->
                <div class="relative w-full aspect-square bg-slate-950 rounded-2xl overflow-hidden border-2 border-indigo-500/40 shadow-inner flex items-center justify-center">
                    <div id="qr-reader" class="w-full h-full"></div>

                    <div v-if="!isCameraActive" class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center text-slate-400">
                        <svg class="w-16 h-16 mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-sm font-semibold">Kamera Belum Aktif</p>
                        <p class="text-xs text-slate-500 mt-1">Klik "Aktifkan Kamera" di atas untuk memulai scanner.</p>
                    </div>

                    <!-- Scan Line Overlay -->
                    <div class="absolute inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-indigo-400 to-transparent animate-scan-line pointer-events-none z-10"></div>
                </div>

                <!-- Manual / USB Scanner Input -->
                <form @submit.prevent="submitScan(manualToken)" class="space-y-1.5 pt-1">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
                        Input Token / Scan Barcode USB
                    </label>
                    <div class="flex gap-2">
                        <input 
                            ref="manualInputRef"
                            v-model="manualToken" 
                            type="text" 
                            placeholder="Scan barcode atau ketik NIM..." 
                            class="flex-1 bg-slate-950 border-slate-800 text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono placeholder:text-slate-600 py-2.5 px-3"
                        />
                        <button 
                            type="submit" 
                            :disabled="isProcessing || !manualToken.trim()"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 font-black text-white text-xs rounded-xl transition shadow-lg shrink-0"
                        >
                            Scan
                        </button>
                    </div>
                </form>

                <!-- Camera Access Error Alert -->
                <div v-if="cameraError" class="p-4 bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold rounded-xl text-center">
                    {{ cameraError }}
                </div>
            </div>

        </div>
    </PanitiaLayout>
</template>

<style scoped>
:deep(#qr-reader) {
    border: none !important;
    background: transparent !important;
    width: 100% !important;
    height: 100% !important;
}

:deep(#qr-reader video) {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 1rem !important;
}

:deep(#qr-shaded-region) {
    display: none !important;
}

:deep(#qr-reader__scan_region) {
    border: none !important;
}

:deep(#qr-reader__scan_region img) {
    display: none !important;
}

:deep(#qr-reader__dashboard_section_csr) {
    display: none !important;
}

.pop-enter-active,
.pop-leave-active {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.pop-enter-from,
.pop-leave-to {
    opacity: 0;
    transform: scale(0.92);
}
</style>
