<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Html5Qrcode } from 'html5-qrcode';

const props = defineProps({
    activePeriode: Object,
});

const scanInput = ref('');
const isScanning = ref(false);
const isCameraActive = ref(false);
const activeWisudawan = ref(null);
const errorMessage = ref('');
const countdown = ref(0);
let timer = null;
let inputBuffer = '';
let html5QrCode = null;

const kioskInputRef = ref(null);

const focusInput = () => {
    setTimeout(() => {
        kioskInputRef.value?.focus();
    }, 100);
};

const startCamera = async () => {
    try {
        isCameraActive.value = true;
        html5QrCode = new Html5Qrcode("kiosk-camera-reader");
        const config = { 
            fps: 15, 
            qrbox: (w, h) => ({ width: Math.min(w, h) * 0.75, height: Math.min(w, h) * 0.75 }) 
        };

        try {
            await html5QrCode.start(
                { facingMode: "user" },
                config,
                (decodedText) => {
                    if (!isScanning.value && !activeWisudawan.value) {
                        handleScanSubmit(decodedText);
                    }
                },
                () => {}
            );
        } catch (err) {
            await html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    if (!isScanning.value && !activeWisudawan.value) {
                        handleScanSubmit(decodedText);
                    }
                },
                () => {}
            );
        }
    } catch (e) {
        isCameraActive.value = false;
        console.warn("Kamera laptop tidak dapat dibuka secara otomatis:", e);
    }
};

const stopCamera = async () => {
    if (html5QrCode && isCameraActive.value) {
        try {
            await html5QrCode.stop();
            isCameraActive.value = false;
        } catch (e) {}
    }
};

const handleScanSubmit = async (token) => {
    if (!token || isScanning.value) return;

    isScanning.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('api.kiosk.scan'), { qr_code_token: token });

        if (response.data.status === 'success') {
            activeWisudawan.value = response.data.wisudawan;
            playSuccessSound();
            startResetTimer(5);
        }
    } catch (error) {
        playErrorSound();
        errorMessage.value = error.response?.data?.message || 'QR Code / NIM Tidak Terdaftar!';
        startResetTimer(3);
    } finally {
        isScanning.value = false;
        scanInput.value = '';
        inputBuffer = '';
        focusInput();
    }
};

const startResetTimer = (seconds) => {
    if (timer) clearInterval(timer);
    countdown.value = seconds;

    timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            activeWisudawan.value = null;
            errorMessage.value = '';
            focusInput();
        }
    }, 1000);
};

const playSuccessSound = () => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator();
        o.type = 'triangle';
        o.frequency.setValueAtTime(523.25, ctx.currentTime);
        o.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1);
        o.frequency.setValueAtTime(783.99, ctx.currentTime + 0.2);
        o.connect(ctx.destination);
        o.start();
        o.stop(ctx.currentTime + 0.4);
    } catch (e) {}
};

const playErrorSound = () => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator();
        o.type = 'sawtooth';
        o.frequency.setValueAtTime(220, ctx.currentTime);
        o.connect(ctx.destination);
        o.start();
        o.stop(ctx.currentTime + 0.3);
    } catch (e) {}
};

// Global USB Barcode / Scanner Keyboard Listener
const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
        if (inputBuffer.trim()) {
            handleScanSubmit(inputBuffer.trim());
        }
        inputBuffer = '';
    } else if (e.key.length === 1) {
        inputBuffer += e.key;
    }
};

onMounted(() => {
    focusInput();
    startCamera();
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('click', focusInput);
});

onUnmounted(() => {
    stopCamera();
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('click', focusInput);
    if (timer) clearInterval(timer);
});
</script>

<template>
    <Head title="Self-Service TV Kiosk Scan Gate Ballroom" />

    <div class="min-h-screen bg-slate-950 text-white font-sans overflow-hidden flex flex-col justify-between p-8 relative" @click="focusInput">

        <!-- Top Header Bar -->
        <div class="flex items-center justify-between border-b border-slate-800/80 pb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-tr from-amber-500 to-amber-300 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-amber-500/20">
                    
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-100 tracking-wide">POLITEKNIK INDONUSA SURAKARTA</h1>
                    <p class="text-sm font-semibold text-amber-400 mt-0.5">{{ activePeriode?.nama_periode || 'WISUDA AGUNG POLITEKNIK INDONUSA' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="px-5 py-2.5 bg-slate-900 border border-slate-800 rounded-2xl flex items-center gap-3 shadow-inner">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full animate-ping"></span>
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-widest">Self-Scan Kiosk (Kamera Laptop Aktif)</span>
                </div>
            </div>
        </div>

        <!-- Main Display Center -->
        <div class="my-auto py-8">

            <!-- IDLE / WAITING STATE WITH LIVE CAMERA SCANNER -->
            <div v-if="!activeWisudawan && !errorMessage" class="text-center max-w-4xl mx-auto space-y-6 animate-fade-in">
                
                <!-- Live Laptop Camera Box -->
                <div class="relative w-80 h-72 md:w-[440px] md:h-[320px] mx-auto rounded-3xl overflow-hidden border-4 border-amber-400/80 shadow-2xl shadow-amber-500/20 bg-slate-900 flex items-center justify-center">
                    <div id="kiosk-camera-reader" class="w-full h-full"></div>
                    
                    <!-- Scanner Corner Brackets -->
                    <div class="absolute inset-0 pointer-events-none p-4 flex flex-col justify-between z-10">
                        <div class="flex justify-between">
                            <div class="w-8 h-8 border-t-4 border-l-4 border-amber-400 rounded-tl-lg"></div>
                            <div class="w-8 h-8 border-t-4 border-r-4 border-amber-400 rounded-tr-lg"></div>
                        </div>
                        <div class="flex justify-between">
                            <div class="w-8 h-8 border-b-4 border-l-4 border-amber-400 rounded-bl-lg"></div>
                            <div class="w-8 h-8 border-b-4 border-r-4 border-amber-400 rounded-br-lg"></div>
                        </div>
                    </div>

                    <!-- Scanning Indicator Badge -->
                    <div class="absolute top-3 z-10 bg-slate-950/80 backdrop-blur border border-amber-500/30 px-3 py-1 rounded-full text-[10px] font-bold text-amber-300 flex items-center gap-1.5 shadow">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Kamera Laptop Siap Scan QR
                    </div>

                    <!-- Scanning Line Animation -->
                    <div class="absolute inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent animate-scan-line pointer-events-none z-10"></div>
                </div>

                <div class="space-y-2">
                    <h2 class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-400 to-amber-100">
                        ARAHKAN QR CODE KARTU WISUDA KE KAMERA
                    </h2>
                    <p class="text-lg text-slate-400 font-medium">Tunjukkan QR Code Kartu Undangan tepat di depan Kamera Laptop untuk Presensi Otomatis</p>
                </div>

                <!-- Backup input simulation form -->
                <div class="pt-2 max-w-md mx-auto">
                    <input 
                        ref="kioskInputRef"
                        v-model="scanInput" 
                        @keyup.enter="handleScanSubmit(scanInput)"
                        type="text" 
                        autofocus
                        placeholder="Atau ketik NIM / Scan USB disini..." 
                        class="w-full bg-slate-900 border-slate-800 text-slate-100 placeholder-slate-600 text-center text-xs rounded-xl py-2.5 focus:border-amber-500 focus:ring-amber-500 shadow-inner"
                    />
                </div>
            </div>

            <!-- SUCCESS WELCOME DISPLAY (TV BIG SCREEN) -->
            <div v-else-if="activeWisudawan" class="max-w-6xl mx-auto bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 border-2 border-emerald-500/40 rounded-3xl p-10 shadow-2xl relative overflow-hidden animate-bounce-in">
                
                <!-- Background Accent Glow -->
                <div class="absolute -right-20 -top-20 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl"></div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    
                    <!-- Student Photo & Status Badges -->
                    <div class="lg:col-span-5 text-center space-y-4">
                        <div class="relative w-64 h-80 mx-auto rounded-2xl overflow-hidden border-4 border-emerald-500 shadow-2xl bg-slate-800">
                            <img :src="activeWisudawan.pas_foto" alt="Foto Wisudawan" class="w-full h-full object-cover" />
                        </div>

                        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full text-xs font-bold uppercase tracking-wider">
                            <span></span> Presensi Masuk Ballroom Berhasil
                        </div>
                    </div>

                    <!-- Student Information -->
                    <div class="lg:col-span-7 space-y-6">
                        <div>
                            <span class="text-sm font-extrabold text-amber-400 uppercase tracking-widest">{{ activeWisudawan.prodi }}</span>
                            <h2 class="text-4xl font-black text-white mt-1 leading-tight">{{ activeWisudawan.nama_lengkap }}</h2>
                            <p class="text-xl font-mono text-slate-400 mt-1">NIM: {{ activeWisudawan.nim }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-y border-slate-800 py-6">
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold">Orang Tua / Wali (SIAKAD)</p>
                                <p class="text-base font-bold text-slate-200 mt-0.5">{{ activeWisudawan.nama_ibu || activeWisudawan.nama_ayah }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold">Status Kelulusan (SIMANTA)</p>
                                <p class="text-base font-bold text-emerald-400 mt-0.5">LULUS / SYARAT LENGKAP</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold">Kuota Pendamping (SIKEU)</p>
                                <p class="text-base font-bold text-amber-400 mt-0.5">{{ activeWisudawan.tamu_kuota }} Orang</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold">Waktu Presensi Gate</p>
                                <p class="text-base font-bold text-slate-200 mt-0.5 font-mono">{{ activeWisudawan.waktu_presensi }}</p>
                            </div>
                        </div>

                        <div class="p-4 bg-emerald-950/60 border border-emerald-800/80 rounded-2xl flex items-center justify-between">
                            <span class="text-lg font-extrabold text-emerald-300">SELAMAT DATANG DI BALLROOM</span>
                            <span class="text-2xl"></span>
                        </div>
                    </div>

                </div>

                <!-- Timer Reset Bar -->
                <div class="mt-8 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs text-slate-400">
                    <span>Layar akan reset otomatis dalam {{ countdown }} detik...</span>
                    <span class="font-mono text-emerald-400 font-bold">READY FOR NEXT SCAN</span>
                </div>
            </div>

            <!-- REJECTED DISPLAY -->
            <div v-else-if="errorMessage" class="max-w-3xl mx-auto bg-rose-950/80 border-2 border-rose-600 rounded-3xl p-10 shadow-2xl text-center space-y-6 animate-shake">
                <div class="text-7xl"></div>
                <h2 class="text-3xl font-black text-rose-200">AKSES DITOLAK / DATA INVALID</h2>
                <p class="text-lg text-rose-300 font-medium">{{ errorMessage }}</p>
                <div class="text-xs text-rose-400">Layar akan reset dalam {{ countdown }} detik...</div>
            </div>

        </div>

        <!-- Bottom Footer -->
        <div class="border-t border-slate-800/80 pt-4 flex items-center justify-between text-xs text-slate-400 font-semibold">
            <div>Politeknik Indonusa Surakarta &copy; {{ new Date().getFullYear() }}</div>
            <div>Integrated Systems: SIKEU • SIMPEG • SIAKAD • SIMANTA</div>
        </div>

    </div>
</template>

<style>
#kiosk-camera-reader {
    width: 100% !important;
    height: 100% !important;
    border: none !important;
    position: absolute !important;
    inset: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    overflow: hidden !important;
}

#kiosk-camera-reader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 1.5rem !important;
}

#kiosk-camera-reader__scan_region {
    width: 100% !important;
    height: 100% !important;
    position: absolute !important;
    inset: 0 !important;
}

#kiosk-camera-reader__scan_region video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

#kiosk-camera-reader__scan_region img,
#kiosk-camera-reader__dashboard {
    display: none !important;
}
</style>
