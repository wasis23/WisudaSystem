<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
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
    total_security_scanned: props.stats?.total_security_scanned || 0,
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

// Checkout & Camera Snapshot State
const isCapturingCheckout = ref(false);
const checkoutLoading = ref(false);
const checkoutPhotoPreview = ref(null);
const checkoutVideoRef = ref(null);
const checkoutFileInputRef = ref(null);
let checkoutMediaStream = null;

const showPopupNotification = (type, title, message, data = null, guestData = null) => {
    popup.value = {
        show: true,
        type,
        title,
        message,
        data,
        guestData,
    };

    // Matikan kamera seketika saat informasi hasil scan muncul
    stopCamera();
};

const isApprovingReentry = ref(false);

const closePopup = async () => {
    const isReentry = !!((popup.value.data?.is_reentry && popup.value.data?.foto_keluar_gate) || (popup.value.guestData?.is_reentry && popup.value.guestData?.foto_keluar_gate));
    const targetToken = popup.value.data?.token || popup.value.guestData?.token;

    if (isReentry && targetToken) {
        isApprovingReentry.value = true;
        try {
            await axios.post(route('security.reentry.approve'), {
                qr_code_token: targetToken,
            }, {
                headers: { 'Accept': 'application/json' }
            });
        } catch (e) {
            console.warn('Re-entry approval error:', e);
        } finally {
            isApprovingReentry.value = false;
        }
    }

    popup.value.show = false;
    stopCheckoutCamera();
    // Aktifkan kembali kamera setelah tombol Selesai ditekan
    startCamera();
};

const openCheckoutDialog = async () => {
    // Clear auto-close timers while in checkout mode
    if (popupTimer) clearTimeout(popupTimer);
    if (popupCountdownInterval) clearInterval(popupCountdownInterval);

    isCapturingCheckout.value = true;
    checkoutPhotoPreview.value = null;

    // Start live checkout camera or use existing reader video stream
    nextTick(async () => {
        try {
            // Try grabbing from existing html5QrCode video stream or direct getUserMedia
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 640 } }
            });
            checkoutMediaStream = stream;
            if (checkoutVideoRef.value) {
                checkoutVideoRef.value.srcObject = stream;
                await checkoutVideoRef.value.play();
            }
        } catch (e) {
            console.warn("Direct checkout camera error, fallback to file/system camera:", e);
        }
    });
};

const stopCheckoutCamera = () => {
    if (checkoutMediaStream) {
        checkoutMediaStream.getTracks().forEach(track => track.stop());
        checkoutMediaStream = null;
    }
    isCapturingCheckout.value = false;
    checkoutPhotoPreview.value = null;
};

const captureFromVideo = () => {
    if (!checkoutVideoRef.value) return;
    const video = checkoutVideoRef.value;
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    checkoutPhotoPreview.value = canvas.toDataURL('image/jpeg', 0.85);
};

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (evt) => {
        checkoutPhotoPreview.value = evt.target.result;
    };
    reader.readAsDataURL(file);
};

const retakeCheckoutPhoto = () => {
    checkoutPhotoPreview.value = null;
};

const confirmCheckout = async () => {
    const targetToken = popup.value.data?.token || popup.value.guestData?.token;
    if (!targetToken) return;

    if (!checkoutPhotoPreview.value) {
        alert('Silakan ambil/foto wajah terlebih dahulu sebelum konfirmasi Check-Out.');
        return;
    }

    checkoutLoading.value = true;
    try {
        const response = await axios.post(route('security.checkout'), {
            qr_code_token: targetToken,
            foto_wajah: checkoutPhotoPreview.value,
        }, {
            headers: { 'Accept': 'application/json' }
        });

        playSuccessSound();
        stopCheckoutCamera();

        showPopupNotification(
            'success',
            'CHECK-OUT BERHASIL (KELUAR)',
            response.data.message || 'Status berhasil di-checkout. Foto wajah tersimpan.',
            response.data.scanned_data || null,
            response.data.guest_data || null
        );
    } catch (err) {
        playErrorSound();
        alert(err.response?.data?.message || 'Gagal melakukan check-out.');
    } finally {
        checkoutLoading.value = false;
    }
};

const focusManualInput = () => {
    nextTick(() => {
        manualInputRef.value?.focus();
    });
};

const processScan = async (rawToken) => {
    // HARD LOCK: DO NOT scan if popup is open (timer not expired or waiting for button press) or already processing
    if (popup.value.show || isProcessing.value || isCapturingCheckout.value) return;

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
        const response = await axios.post(route('security.scan.process'), {
            qr_code_token: token,
        }, {
            headers: { 'Accept': 'application/json' }
        });

        if (response.data.status === 'success') {
            playSuccessSound();
            
            // Sync realtime stats from server response
            if (response.data?.stats?.total_security_scanned !== undefined) {
                localStats.value.total_security_scanned = response.data.stats.total_security_scanned;
            } else {
                localStats.value.total_security_scanned++;
            }

            const scanned = response.data.scanned_data;
            const guest = response.data.guest_data;

            showPopupNotification(
                'success',
                'PRESENSI BERHASIL',
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

            if (response.data?.stats?.total_security_scanned !== undefined) {
                localStats.value.total_security_scanned = response.data.stats.total_security_scanned;
            }

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
    }
};

let statsInterval = null;

const syncLiveStats = async () => {
    try {
        const res = await axios.get(route('security.stats'), {
            headers: { 'Accept': 'application/json' }
        });
        if (res.data?.stats?.total_security_scanned !== undefined) {
            localStats.value.total_security_scanned = res.data.stats.total_security_scanned;
        }
    } catch (e) {
        // Silently ignore temporary network jitter
    }
};

const startCamera = async () => {
    try {
        getAudioContext();
        if (isScanning.value) return;

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("security-reader");
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
    window.addEventListener('keydown', handleGlobalKeyDown);
    syncLiveStats();
    statsInterval = setInterval(syncLiveStats, 3000);
});

onUnmounted(() => {
    stopCamera();
    window.removeEventListener('keydown', handleGlobalKeyDown);
    if (statsInterval) clearInterval(statsInterval);
});
</script>

<template>
    <Head title="Security Gate Scanner Presensi" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-950 text-white p-4 max-w-md mx-auto space-y-4 pb-16 relative">

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
                            ? 'bg-slate-900 border-emerald-500/80 shadow-emerald-500/20' 
                            : (popup.type === 'warning' ? 'bg-slate-900 border-amber-500/80 shadow-amber-500/20' : 'bg-slate-900 border-rose-600 shadow-rose-600/30')"
                    >
                        <!-- Status Icon & Badge -->
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

                        <!-- RE-ENTRY PHOTO COMPARISON (IF RETURNING) -->
                        <div v-if="(popup.data?.is_reentry && popup.data?.foto_keluar_gate) || (popup.guestData?.is_reentry && popup.guestData?.foto_keluar_gate)" class="p-3 bg-amber-500/10 border-2 border-amber-500/50 rounded-2xl space-y-2 text-left">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black tracking-wider uppercase text-amber-400 flex items-center gap-1">
                                    <span>👁️</span> VERIFIKASI WAJAH KELUAR
                                </span>
                                <span class="text-[9px] font-bold text-slate-400">
                                    Keluar: {{ popup.data?.waktu_keluar_gate || popup.guestData?.waktu_keluar_gate }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-slate-900 rounded-xl p-2 text-center border border-slate-800">
                                    <span class="text-[9px] text-slate-400 block mb-1 font-bold">Foto SIAKAD / Master</span>
                                    <div class="w-full h-24 rounded-lg bg-slate-950 overflow-hidden flex items-center justify-center border border-slate-800">
                                        <img v-if="popup.data?.pas_foto" :src="popup.data?.pas_foto" class="w-full h-full object-cover" />
                                        <span v-else class="text-2xl">🎓</span>
                                    </div>
                                </div>
                                <div class="bg-amber-950/40 rounded-xl p-2 text-center border border-amber-500/40">
                                    <span class="text-[9px] text-amber-300 block mb-1 font-black">Foto Saat Keluar (Security)</span>
                                    <div class="w-full h-24 rounded-lg bg-slate-950 overflow-hidden flex items-center justify-center border border-amber-500/50">
                                        <img :src="popup.data?.foto_keluar_gate || popup.guestData?.foto_keluar_gate" class="w-full h-full object-cover" />
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-amber-200 text-center font-medium">Cocokkan wajah orang yang masuk dengan foto saat keluar di atas.</p>
                        </div>

                        <!-- Scanned Wisudawan Details -->
                        <div v-if="popup.data" class="space-y-3 bg-slate-950/80 rounded-2xl p-4 border border-slate-800 text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-16 h-16 rounded-xl bg-slate-800 border border-amber-400/40 overflow-hidden flex items-center justify-center shrink-0">
                                    <img v-if="popup.data.pas_foto" :src="popup.data.pas_foto" class="w-full h-full object-cover" />
                                    <span v-else class="text-2xl text-slate-500">🎓</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block truncate">
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
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Orang Tua / SIAKAD</span>
                                    <span class="font-bold text-slate-200 block truncate">{{ popup.data.nama_ibu || popup.data.nama_ayah || '-' }}</span>
                                </div>
                                <div class="bg-slate-900/90 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Status SIMANTA</span>
                                    <span class="font-bold text-emerald-400 block truncate">{{ popup.data.status_simanta || 'LULUS' }}</span>
                                </div>
                                <div class="bg-slate-900/90 p-2 rounded-lg">
                                    <span class="text-[9px] text-slate-400 uppercase block font-semibold">Kuota Tamu (SIKEU)</span>
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
                            <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest block">Tamu Undangan / Pendamping</span>
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

                        <!-- CHECKOUT ACTION (WHEN ALREADY SCANNED / INSIDE) -->
                        <div v-if="popup.type === 'warning' && (popup.data || popup.guestData) && !isCapturingCheckout" class="pt-1">
                            <button 
                                @click="openCheckoutDialog"
                                type="button"
                                class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-slate-950 font-black text-xs rounded-2xl shadow-lg transition flex items-center justify-center gap-2"
                            >
                                <span class="text-base">📸</span>
                                <span>Check-Out (Keluar Sementara & Foto Wajah)</span>
                            </button>
                        </div>

                        <!-- CHECKOUT CAMERA CAPTURE SECTION -->
                        <div v-if="isCapturingCheckout" class="space-y-3 bg-slate-950 rounded-2xl p-3 border-2 border-orange-500/60 text-left">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-orange-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📸</span> Ambil Foto Wajah Keluar
                                </span>
                                <button @click="stopCheckoutCamera" class="text-xs text-slate-400 hover:text-white">✕ Batal</button>
                            </div>

                            <!-- Live Camera or Preview -->
                            <div class="relative w-full aspect-square bg-slate-900 rounded-xl overflow-hidden border border-slate-800 flex items-center justify-center">
                                <video 
                                    v-show="!checkoutPhotoPreview" 
                                    ref="checkoutVideoRef" 
                                    autoplay 
                                    playsinline 
                                    muted 
                                    class="w-full h-full object-cover"
                                ></video>

                                <img 
                                    v-if="checkoutPhotoPreview" 
                                    :src="checkoutPhotoPreview" 
                                    class="w-full h-full object-cover" 
                                />

                                <!-- Hidden File Input for fallback upload -->
                                <input 
                                    type="file" 
                                    ref="checkoutFileInputRef" 
                                    accept="image/*" 
                                    capture="user" 
                                    class="hidden" 
                                    @change="onFileSelected"
                                />
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-2">
                                <div v-if="!checkoutPhotoPreview" class="flex gap-2">
                                    <button 
                                        @click="captureFromVideo"
                                        type="button"
                                        class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow transition"
                                    >
                                        📷 Jepret Foto
                                    </button>
                                    <button 
                                        @click="checkoutFileInputRef?.click()"
                                        type="button"
                                        class="px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-700"
                                        title="Pilih/Ambil dari Kamera HP"
                                    >
                                        📁 File/Kamera
                                    </button>
                                </div>

                                <div v-else class="flex gap-2">
                                    <button 
                                        @click="retakeCheckoutPhoto"
                                        type="button"
                                        class="px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-700"
                                    >
                                        🔄 Ulangi Foto
                                    </button>
                                    <button 
                                        @click="confirmCheckout"
                                        :disabled="checkoutLoading"
                                        type="button"
                                        class="flex-1 py-2.5 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-slate-950 font-black text-xs rounded-xl shadow transition flex items-center justify-center gap-1.5"
                                    >
                                        <span v-if="checkoutLoading" class="w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                                        <span>✓ Konfirmasi Check-Out</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Action & Next Scan Indicator -->
                        <div class="pt-2 flex items-center justify-between gap-3">
                            <div class="text-[11px] text-slate-400 flex items-center gap-1.5 font-medium">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span>Tekan Selesai untuk lanjut scan</span>
                            </div>
                            <button 
                                @click="closePopup"
                                :disabled="isApprovingReentry"
                                class="px-5 py-2 text-xs font-bold rounded-xl transition bg-slate-800 hover:bg-slate-700 disabled:opacity-50 text-white border border-slate-700 flex items-center gap-1.5 shadow-md"
                            >
                                <span v-if="isApprovingReentry" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>{{ (popup.data?.is_reentry || popup.guestData?.is_reentry) ? '✓ Setujui Masuk & Reset Foto' : '✓ Selesai' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Top Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <span class="px-2.5 py-0.5 bg-amber-500/20 text-amber-400 font-extrabold text-[11px] rounded-full tracking-wider uppercase">
                        🛡️ Security Gate Mobile
                    </span>
                    <h1 class="text-lg font-black mt-1 text-slate-100">Presensi Masuk Halaman Depan</h1>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-black text-amber-400">{{ localStats.total_security_scanned }}</div>
                    <div class="text-[9px] text-slate-400 uppercase font-semibold">Tercatat Masuk</div>
                </div>
            </div>

            <!-- Scanner Camera Box -->
            <div class="relative bg-slate-900 rounded-3xl overflow-hidden border-2 border-amber-500/30 shadow-2xl aspect-square flex flex-col items-center justify-center">
                <div id="security-reader" class="w-full h-full"></div>

                <div v-if="!isScanning" class="text-center p-6 space-y-3 z-10">
                    <span class="text-5xl block">📷</span>
                    <p class="text-sm text-slate-400 font-medium">Kamera HP Belum Aktif</p>
                    <button 
                        @click="startCamera" 
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-lg transition"
                    >
                        Aktifkan Kamera HP
                    </button>
                </div>

                <!-- Processing Overlay inside Camera -->
                <div v-if="isProcessing" class="absolute inset-0 bg-slate-950/70 backdrop-blur-xs flex flex-col items-center justify-center gap-2 z-20">
                    <div class="w-10 h-10 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-bold text-amber-300 uppercase tracking-wider">Memproses Barcode...</span>
                </div>

                <!-- Scan Line Animation -->
                <div class="absolute inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent animate-scan-line pointer-events-none z-10"></div>
            </div>

            <!-- Manual Barcode / USB Scanner Form -->
            <form @submit.prevent="processScan(scanToken)" class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Scan Barcode / NIM Manual
                    </label>
                    <span class="text-[10px] text-amber-400/80 font-medium">Bip Suara Aktif 🔊</span>
                </div>
                <div class="flex gap-2">
                    <input 
                        ref="manualInputRef"
                        v-model="scanToken" 
                        type="text" 
                        placeholder="Scan barcode / ketik NIM..." 
                        class="flex-1 bg-slate-900 border-slate-800 text-white rounded-xl focus:ring-amber-500 focus:border-amber-500 text-sm font-mono placeholder:text-slate-600 py-2.5 px-3"
                    />
                    <button 
                        type="submit" 
                        :disabled="isProcessing || !scanToken.trim()"
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 font-black text-slate-950 text-xs rounded-xl transition shadow-lg shrink-0"
                    >
                        Check-in
                    </button>
                </div>
            </form>

            <!-- Live Recent Attendees List -->
            <div v-if="recentScans.length > 0" class="space-y-2 pt-2 border-t border-slate-800/80">
                <div class="flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                    <span>Riwayat Presensi Terakhir</span>
                    <span class="text-[10px] text-emerald-400 font-normal">Real-time</span>
                </div>
                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                    <div 
                        v-for="s in recentScans" 
                        :key="s.id" 
                        class="p-2.5 bg-slate-900/90 rounded-xl border border-slate-800 text-xs flex items-center justify-between"
                    >
                        <div class="min-w-0 flex-1 mr-2">
                            <div class="font-bold text-white truncate">{{ s.nama }}</div>
                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                <span class="font-mono">{{ s.nim }}</span>
                                <span>•</span>
                                <span class="truncate">{{ s.prodi }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/20 text-emerald-400 block">
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
#security-reader {
    width: 100% !important;
    height: 100% !important;
    border: none !important;
    position: absolute !important;
    inset: 0 !important;
}
#security-reader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 1.5rem !important;
}
#security-reader__scan_region {
    width: 100% !important;
    height: 100% !important;
}
#security-reader__scan_region img,
#security-reader__dashboard {
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
