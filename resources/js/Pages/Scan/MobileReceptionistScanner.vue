<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { Html5Qrcode } from 'html5-qrcode';

const props = defineProps({
    activePeriode: Object,
    stats: Object,
});

const scanToken = ref('');
const isScanning = ref(false);
const isProcessing = ref(false);
const manualInputRef = ref(null);
const localStats = ref({
    total_reception_scanned: props.stats?.total_reception_scanned || 0,
    total_snack_issued: props.stats?.total_snack_issued || 0,
});
const recentScans = ref([]);

// Popup Modal State
const popup = ref({
    show: false,
    type: 'success', // 'success' | 'error'
    title: '',
    message: '',
    data: null,
    guestData: null,
    countdown: 3,
});
let popupTimer = null;
let popupCountdownInterval = null;

// Scanner control
let html5QrCode = null;
let lastScannedCode = '';
let lastScannedTime = 0;
let keyBuffer = '';
let keyTimer = null;
let audioCtx = null;

// Audio Context initialization on user gesture
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

// Success Beep (Crisp High Double-Tone)
const playSuccessSound = () => {
    try {
        if (navigator.vibrate) {
            navigator.vibrate([80, 40, 80]);
        }
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;

        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(1046, now); // High C
        gain1.gain.setValueAtTime(0.3, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.12);

        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(1568, now + 0.1); // High G pleasant chime
        gain2.gain.setValueAtTime(0.35, now + 0.1);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.28);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.1);
        osc2.stop(now + 0.28);
    } catch (e) {
        console.warn('Audio play error:', e);
    }
};

// Error Beep (Low Warning Buzz)
const playErrorSound = () => {
    try {
        if (navigator.vibrate) {
            navigator.vibrate([200, 100, 200]);
        }
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
    } catch (e) {
        console.warn('Audio play error:', e);
    }
};

// Warning Beep (Double Alert Tone)
const playWarningSound = () => {
    try {
        if (navigator.vibrate) {
            navigator.vibrate([150, 80, 150]);
        }
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;

        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'triangle';
        osc1.frequency.setValueAtTime(587.33, now); // D5
        gain1.gain.setValueAtTime(0.3, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.15);

        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'triangle';
        osc2.frequency.setValueAtTime(440, now + 0.15); // A4
        gain2.gain.setValueAtTime(0.35, now + 0.15);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.15);
        osc2.stop(now + 0.35);
    } catch (e) {
        console.warn('Audio play error:', e);
    }
};

const showPopupNotification = (type, title, message, data = null, guestData = null) => {
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
    focusManualInput();
};

const focusManualInput = () => {
    nextTick(() => {
        manualInputRef.value?.focus();
    });
};

const processScan = async (rawToken) => {
    // HARD LOCK: DO NOT scan if popup is open (timer not expired or waiting for button press) or already processing
    if (popup.value.show || isProcessing.value) return;

    const token = String(rawToken || '').trim();
    if (!token) return;

    // Duplicate protection: prevent scanning same barcode within 2.5s
    const now = Date.now();
    if (lastScannedCode === token && now - lastScannedTime < 2500) {
        return;
    }
    lastScannedCode = token;
    lastScannedTime = now;

    getAudioContext();
    isProcessing.value = true;
    scanToken.value = '';

    try {
        const response = await axios.post(route('receptionist.scan.process'), {
            qr_code_token: token,
        }, {
            headers: { 'Accept': 'application/json' }
        });

        if (response.data.status === 'success') {
            playSuccessSound();
            localStats.value.total_reception_scanned++;

            const scanned = response.data.scanned_data;
            const guest = response.data.guest_data;

            showPopupNotification(
                'success',
                'PRESENSI & SNACK DIVERIFIKASI',
                response.data.message,
                scanned || null,
                guest || null
            );

            // Add to live recent scan list
            recentScans.value.unshift({
                id: Date.now(),
                nama: scanned ? scanned.nama_lengkap : (guest ? guest.nama_tamu : token),
                nim: scanned ? scanned.nim : (guest ? guest.wisudawan_nim : '-'),
                prodi: scanned ? scanned.prodi : (guest ? guest.wisudawan_prodi : '-'),
                snack: scanned ? `${scanned.snack_porsi} Porsi` : (guest?.snack_diambil ? '1 Snack Diserahkan' : '1 Snack'),
                type: guest ? 'Tamu/Pendamping' : 'Wisudawan',
                time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
            });
            if (recentScans.value.length > 8) {
                recentScans.value.pop();
            }
        } else if (response.data.status === 'already_scanned') {
            playWarningSound();
            const scanned = response.data.scanned_data;
            const guest = response.data.guest_data;

            showPopupNotification(
                'warning',
                'SUDAH PERNAH DI-SCAN',
                response.data.message,
                scanned || null,
                guest || null
            );
        } else {
            playErrorSound();
            showPopupNotification('error', 'AKSES DITOLAK', response.data.message || 'Data tidak valid.');
        }
    } catch (error) {
        playErrorSound();
        const msg = error.response?.data?.message || 'QR Code / NIM Tidak Terdaftar!';
        showPopupNotification('error', 'AKSES DITOLAK / TIDAK DITEMUKAN', msg);
    } finally {
        isProcessing.value = false;
        focusManualInput();
    }
};

const toggleGuestStatus = async (guestId, field) => {
    try {
        await axios.post(route('receptionist.guest.toggle', guestId), {
            [field]: true,
        });
        if (popup.value.data?.tamu_tambahan_list) {
            const item = popup.value.data.tamu_tambahan_list.find(g => g.id === guestId);
            if (item) item[field] = !item[field];
        }
        playSuccessSound();
    } catch (e) {
        console.error(e);
    }
};

const startCamera = async () => {
    try {
        getAudioContext();
        if (isScanning.value) return;

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reception-reader");
        }

        await html5QrCode.start(
            { facingMode: "environment" },
            { 
                fps: 15, 
                qrbox: (w, h) => ({ width: Math.min(w, h) * 0.85, height: Math.min(w, h) * 0.85 }) 
            },
            (decodedText) => {
                processScan(decodedText);
            },
            () => {}
        );
        isScanning.value = true;
    } catch (e) {
        isScanning.value = false;
        console.warn("Camera start error:", e);
    }
};

const stopCamera = async () => {
    if (html5QrCode && isScanning.value) {
        try {
            await html5QrCode.stop();
        } catch (e) {
            console.warn("Camera stop error:", e);
        } finally {
            isScanning.value = false;
        }
    }
};

// Global USB Barcode Scanner Listener
const handleGlobalKeyDown = (e) => {
    if (e.target.tagName === 'INPUT' && e.target !== manualInputRef.value) {
        return;
    }

    if (e.key === 'Enter') {
        if (keyBuffer.trim().length > 0) {
            processScan(keyBuffer.trim());
            keyBuffer = '';
        }
    } else if (e.key.length === 1) {
        keyBuffer += e.key;
        if (keyTimer) clearTimeout(keyTimer);
        keyTimer = setTimeout(() => {
            keyBuffer = '';
        }, 800);
    }
};

onMounted(() => {
    startCamera();
    focusManualInput();
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
    <Head title="Receptionist Gate Presensi & Snack Scanner" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-900 text-white p-4 max-w-md mx-auto space-y-4 pb-16 relative">

            <!-- POPUP NOTIFICATION MODAL OVERLAY -->
            <transition name="pop">
                <div 
                    v-if="popup.show" 
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
                    @click.self="closePopup"
                >
                    <div 
                        class="w-full max-w-sm rounded-3xl p-6 shadow-2xl border-2 text-center space-y-4 transform transition-all"
                        :class="popup.type === 'success' 
                            ? 'bg-slate-950 border-purple-500/80 shadow-purple-500/30' 
                            : (popup.type === 'warning' ? 'bg-slate-950 border-amber-500/80 shadow-amber-500/20' : 'bg-slate-950 border-rose-600 shadow-rose-600/30')"
                    >
                        <!-- Status Icon & Badge -->
                        <div class="flex flex-col items-center">
                            <div 
                                class="w-16 h-16 rounded-full flex items-center justify-center text-3xl font-black shadow-lg mb-2"
                                :class="popup.type === 'success' 
                                    ? 'bg-purple-600 text-white shadow-purple-600/50 animate-bounce' 
                                    : (popup.type === 'warning' ? 'bg-amber-500 text-slate-950 shadow-amber-500/50 animate-pulse' : 'bg-rose-500 text-white shadow-rose-500/50 animate-pulse')"
                            >
                                <span v-if="popup.type === 'success'">✓</span>
                                <span v-else-if="popup.type === 'warning'">⚠️</span>
                                <span v-else>✕</span>
                            </div>

                            <span 
                                class="text-xs font-black tracking-widest uppercase px-3 py-1 rounded-full border"
                                :class="popup.type === 'success' 
                                    ? 'bg-purple-500/20 text-purple-300 border-purple-500/40' 
                                    : (popup.type === 'warning' ? 'bg-amber-500/20 text-amber-400 border-amber-500/40' : 'bg-rose-500/20 text-rose-400 border-rose-500/40')"
                            >
                                {{ popup.title }}
                            </span>
                        </div>

                        <!-- Scanned Wisudawan Details -->
                        <div v-if="popup.data" class="space-y-3 bg-slate-900/90 rounded-2xl p-4 border border-slate-800 text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-16 h-16 rounded-xl bg-slate-800 border border-purple-400/40 overflow-hidden flex items-center justify-center shrink-0">
                                    <img v-if="popup.data.pas_foto" :src="popup.data.pas_foto" class="w-full h-full object-cover" />
                                    <span v-else class="text-2xl text-purple-400">🎓</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wider block truncate">
                                        {{ popup.data.prodi }}
                                    </span>
                                    <h4 class="text-base font-black text-white leading-tight truncate">
                                        {{ popup.data.nama_lengkap }}
                                    </h4>
                                    <p class="text-xs font-mono text-slate-400">NIM: {{ popup.data.nim }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-[11px] pt-2 border-t border-slate-800/80">
                                <div class="bg-purple-950/60 border border-purple-800/60 p-2.5 rounded-xl col-span-2 flex items-center justify-between">
                                    <div>
                                        <span class="text-[9px] text-purple-300 uppercase block font-bold">Hak Paket Snack Catering</span>
                                        <span class="font-black text-purple-100 text-base block">{{ popup.data.snack_porsi }} Porsi</span>
                                    </div>
                                    <span class="text-2xl">🍱</span>
                                </div>

                                <div class="bg-slate-950/80 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Tamu Kuota (SIKEU)</span>
                                    <span class="font-bold text-amber-400 block">{{ popup.data.tamu_kuota }} Orang</span>
                                </div>
                                <div class="bg-slate-950/80 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Waktu Presensi</span>
                                    <span class="font-bold text-slate-200 block">{{ popup.data.waktu_presensi }}</span>
                                </div>
                            </div>

                            <!-- Interactive Extra Guest Snack Status -->
                            <div v-if="popup.data.tamu_tambahan_list?.length > 0" class="pt-2 border-t border-slate-800 space-y-1.5">
                                <span class="text-[10px] font-bold text-purple-300 uppercase tracking-wider block">Tamu Pendamping & Penyerahan Snack:</span>
                                <div class="space-y-1.5 max-h-32 overflow-y-auto">
                                    <div 
                                        v-for="g in popup.data.tamu_tambahan_list" 
                                        :key="g.id"
                                        class="p-2 bg-slate-950/90 rounded-lg flex items-center justify-between text-xs"
                                    >
                                        <div class="min-w-0 flex-1 mr-2">
                                            <div class="font-bold text-slate-200 truncate">{{ g.nama_tamu }}</div>
                                            <span class="text-[9px] text-slate-400">({{ g.hubungan || 'Tamu' }})</span>
                                        </div>
                                        <button 
                                            @click="toggleGuestStatus(g.id, 'snack_diambil')"
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-lg transition shrink-0"
                                            :class="g.snack_diambil ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-purple-600 text-white hover:bg-purple-700'"
                                        >
                                            {{ g.snack_diambil ? '✓ Snack Diserahkan' : 'Serahkan Snack' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scanned Guest Details -->
                        <div v-else-if="popup.guestData" class="space-y-2 bg-slate-900/90 rounded-2xl p-4 border border-slate-800 text-left">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-purple-400 uppercase tracking-widest block">Tamu Undangan</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400">Snack Siap Diserahkan</span>
                            </div>
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
                                <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
                                <span>Siap scan berikutnya ({{ popup.countdown }}s)</span>
                            </div>
                            <button 
                                @click="closePopup"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition bg-slate-850 hover:bg-slate-800 text-white border border-slate-700"
                            >
                                ✓ Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Top Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <span class="px-2.5 py-0.5 bg-purple-500/20 text-purple-300 font-extrabold text-[11px] rounded-full tracking-wider uppercase">
                        ‍ Reception & Snack Desk
                    </span>
                    <h1 class="text-lg font-black mt-1 text-slate-100">Scan Presensi & Snack Venue</h1>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-black text-purple-400">{{ localStats.total_reception_scanned }}</div>
                    <div class="text-[9px] text-slate-400 uppercase font-semibold">Tercatat Masuk</div>
                </div>
            </div>

            <!-- Scanner Camera Box -->
            <div class="relative bg-slate-950 rounded-3xl overflow-hidden border-2 border-purple-900/50 shadow-2xl aspect-square flex flex-col items-center justify-center">
                <div id="reception-reader" class="w-full h-full"></div>

                <div v-if="!isScanning" class="text-center p-6 space-y-3 z-10">
                    <span class="text-5xl block">📷</span>
                    <p class="text-sm text-slate-400 font-medium">Kamera HP Belum Aktif</p>
                    <button 
                        @click="startCamera" 
                        class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-lg transition"
                    >
                        Aktifkan Kamera HP
                    </button>
                </div>

                <!-- Processing Overlay inside Camera -->
                <div v-if="isProcessing" class="absolute inset-0 bg-slate-950/70 backdrop-blur-xs flex flex-col items-center justify-center gap-2 z-20">
                    <div class="w-10 h-10 border-4 border-purple-400 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-bold text-purple-300 uppercase tracking-wider">Memverifikasi Barcode...</span>
                </div>

                <!-- Live Scan Line overlay -->
                <div class="absolute inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-purple-400 to-transparent animate-scan-line pointer-events-none z-10"></div>
            </div>

            <!-- Manual Barcode / USB Scanner Form -->
            <form @submit.prevent="processScan(scanToken)" class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Scan Barcode / NIM Manual
                    </label>
                    <span class="text-[10px] text-purple-400/80 font-medium">Bip Suara Aktif 🔊</span>
                </div>
                <div class="flex gap-2">
                    <input 
                        ref="manualInputRef"
                        v-model="scanToken" 
                        type="text" 
                        placeholder="Scan barcode / ketik NIM..." 
                        class="flex-1 bg-slate-950 border-slate-800 text-white rounded-xl focus:ring-purple-500 focus:border-purple-500 text-sm font-mono placeholder:text-slate-600 py-2.5 px-3"
                    />
                    <button 
                        type="submit" 
                        :disabled="isProcessing || !scanToken.trim()"
                        class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 font-black text-white text-xs rounded-xl transition shadow-lg shrink-0"
                    >
                        Verifikasi
                    </button>
                </div>
            </form>

            <!-- Live Recent Attendees List -->
            <div v-if="recentScans.length > 0" class="space-y-2 pt-2 border-t border-slate-800/80">
                <div class="flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                    <span>Riwayat Presensi & Snack Terakhir</span>
                    <span class="text-[10px] text-purple-400 font-normal">Real-time</span>
                </div>
                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                    <div 
                        v-for="s in recentScans" 
                        :key="s.id" 
                        class="p-2.5 bg-slate-950/80 rounded-xl border border-slate-800 text-xs flex items-center justify-between"
                    >
                        <div class="min-w-0 flex-1 mr-2">
                            <div class="font-bold text-white truncate">{{ s.nama }}</div>
                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                <span class="font-mono">{{ s.nim }}</span>
                                <span>•</span>
                                <span class="text-purple-300 font-semibold">{{ s.snack }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-500/20 text-purple-300 block">
                                {{ s.time }}
                            </span>
                            <span class="text-[9px] text-slate-500 font-medium block mt-0.5">{{ s.type }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style>
#reception-reader {
    width: 100% !important;
    height: 100% !important;
    border: none !important;
    position: absolute !important;
    inset: 0 !important;
}
#reception-reader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 1.5rem !important;
}
#reception-reader__scan_region {
    width: 100% !important;
    height: 100% !important;
}
#reception-reader__scan_region img,
#reception-reader__dashboard {
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
