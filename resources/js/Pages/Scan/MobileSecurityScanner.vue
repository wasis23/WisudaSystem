<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';

const props = defineProps({
    activePeriode: Object,
    stats: Object,
});

const scanToken = ref('');
const isScanning = ref(false);
const errorMessage = ref('');
let html5QrCode = null;

const form = useForm({
    qr_code_token: '',
});

const processScan = (token) => {
    if (!token) return;
    form.qr_code_token = token;
    form.post(route('security.scan.process'), {
        preserveScroll: true,
        onSuccess: () => {
            scanToken.value = '';
            playBeep();
        },
        onError: (errors) => {
            errorMessage.value = errors.qr_code_token || 'Gagal memproses QR code.';
        }
    });
};

const playBeep = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime);
        osc.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.2);
    } catch (e) {}
};

const startCamera = async () => {
    try {
        isScanning.value = true;
        html5QrCode = new Html5Qrcode("security-reader");
        await html5QrCode.start(
            { facingMode: "environment" },
            { 
                fps: 15, 
                qrbox: (w, h) => ({ width: Math.min(w, h) * 0.8, height: Math.min(w, h) * 0.8 })
            },
            (decodedText) => {
                processScan(decodedText);
            },
            (error) => {}
        );
    } catch (e) {
        isScanning.value = false;
        errorMessage.value = "Kamera HP tidak dapat dibuka. Gunakan opsi input NIM manual di bawah.";
    }
};

const stopCamera = async () => {
    if (html5QrCode && isScanning.value) {
        try {
            await html5QrCode.stop();
            isScanning.value = false;
        } catch (e) {}
    }
};

onMounted(() => {
    startCamera();
});

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <Head title="Security Gate Scan Presensi Mobile" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-950 text-white p-4 max-w-md mx-auto space-y-5 pb-12">

            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <span class="px-2.5 py-1 bg-amber-500/20 text-amber-400 font-extrabold text-xs rounded-full tracking-wider uppercase">
                        Security Gate Mobile
                    </span>
                    <h1 class="text-xl font-black mt-1 text-slate-100">Scan Kehadiran Gate</h1>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-black text-amber-400">{{ stats?.total_security_scanned || 0 }}</div>
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Tercatat Hadir</div>
                </div>
            </div>

            <!-- Scanner Camera Box -->
            <div class="relative bg-slate-900 rounded-3xl overflow-hidden border-2 border-amber-500/30 shadow-2xl aspect-square flex flex-col items-center justify-center">
                <div id="security-reader" class="w-full h-full"></div>

                <div v-if="!isScanning" class="text-center p-6 space-y-3">
                    <span class="text-6xl"></span>
                    <p class="text-sm text-slate-400 font-medium">Kamera tidak aktif</p>
                    <button @click="startCamera" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-lg transition">
                        Aktifkan Kamera HP
                    </button>
                </div>

                <!-- Live Scan Line overlay -->
                <div class="absolute inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent animate-scan-line pointer-events-none"></div>
            </div>

            <!-- Manual Token Form -->
            <form @submit.prevent="processScan(scanToken)" class="space-y-2">
                <label class="block text-xs font-semibold text-slate-400">Input QR Code / NIM Manual</label>
                <div class="flex gap-2">
                    <input 
                        v-model="scanToken" 
                        type="text" 
                        placeholder="Ketik NIM / Token..." 
                        class="flex-1 bg-slate-900 border-slate-800 text-white rounded-xl focus:ring-amber-500 focus:border-amber-500 text-sm font-mono"
                    />
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 font-bold text-slate-950 text-xs rounded-xl transition shadow-lg"
                    >
                        Check-in
                    </button>
                </div>
            </form>

            <!-- Status Flash Messages -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-950 border border-emerald-800 text-emerald-200 text-sm rounded-2xl shadow-xl flex items-center gap-3">
                <span class="text-2xl"></span>
                <div>
                    <div class="font-bold">BERHASIL VERIFIKASI</div>
                    <div class="text-xs text-emerald-300 mt-0.5">{{ $page.props.flash.success }}</div>
                </div>
            </div>

            <!-- SCANNED DATA DETAILS (WISUDAWAN, KELUARGA, TAMBAHAN WISUDA) -->
            <div v-if="$page.props.flash?.scannedWisudawan" class="bg-gradient-to-br from-slate-900 to-slate-950 border border-amber-500/40 rounded-2xl p-5 shadow-2xl space-y-4">
                
                <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                    <div class="w-14 h-14 rounded-xl bg-slate-800 border border-amber-400/40 overflow-hidden flex items-center justify-center shrink-0">
                        <img v-if="$page.props.flash.scannedWisudawan.pas_foto" :src="$page.props.flash.scannedWisudawan.pas_foto" class="w-full h-full object-cover" />
                        <span v-else class="text-2xl"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">{{ $page.props.flash.scannedWisudawan.prodi }}</span>
                        <h3 class="font-black text-lg text-white leading-tight">{{ $page.props.flash.scannedWisudawan.nama_lengkap }}</h3>
                        <p class="text-xs font-mono text-slate-400">NIM: {{ $page.props.flash.scannedWisudawan.nim }}</p>
                    </div>
                </div>

                <!-- Family & Integration Info -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-2.5 bg-slate-950/80 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Orang Tua (SIAKAD)</span>
                        <span class="font-bold text-slate-200 mt-0.5 block truncate">
                            {{ $page.props.flash.scannedWisudawan.nama_ibu || $page.props.flash.scannedWisudawan.nama_ayah || '-' }}
                        </span>
                    </div>
                    <div class="p-2.5 bg-slate-950/80 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Status (SIMANTA)</span>
                        <span class="font-bold text-emerald-400 mt-0.5 block">LULUS</span>
                    </div>
                    <div class="p-2.5 bg-slate-950/80 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Tamu Total (SIKEU)</span>
                        <span class="font-bold text-amber-400 mt-0.5 block">{{ $page.props.flash.scannedWisudawan.tamu_kuota }} Orang</span>
                    </div>
                    <div class="p-2.5 bg-slate-950/80 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Porsi Snack (Catering)</span>
                        <span class="font-bold text-purple-400 mt-0.5 block">{{ $page.props.flash.scannedWisudawan.snack_porsi }} Porsi</span>
                    </div>
                </div>

                <!-- Extra Guest List -->
                <div v-if="$page.props.flash.scannedWisudawan.tamu_tambahan_list?.length > 0" class="space-y-2 pt-2 border-t border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400">Daftar Anggota Keluarga / Tamu Tambahan:</span>
                    <div class="space-y-1.5 max-h-32 overflow-y-auto">
                        <div v-for="g in $page.props.flash.scannedWisudawan.tamu_tambahan_list" :key="g.id" class="px-3 py-1.5 bg-slate-800/80 rounded-lg text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-200">{{ g.nama_tamu }}</span>
                                <span class="text-[10px] text-slate-400 block">({{ g.hubungan || 'Tamu Tambahan' }})</span>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="g.is_hadir ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-400'">
                                {{ g.is_hadir ? 'Hadir' : 'Belum Scan' }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <div v-if="$page.props.flash?.error || errorMessage" class="p-4 bg-rose-950 border border-rose-800 text-rose-200 text-sm rounded-2xl shadow-xl flex items-center gap-3">
                <span class="text-2xl"></span>
                <div>
                    <div class="font-bold">DITOLAK / INVALID</div>
                    <div class="text-xs text-rose-300 mt-0.5">{{ $page.props.flash?.error || errorMessage }}</div>
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
#security-reader__scan_region img {
    display: none !important;
}
</style>
