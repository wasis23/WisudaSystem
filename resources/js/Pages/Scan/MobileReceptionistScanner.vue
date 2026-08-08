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
    form.qr_code_token = token;
    form.post(route('receptionist.scan.process'), {
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

const toggleGuestStatus = (guestId, field) => {
    router.post(route('receptionist.guest.toggle', guestId), {
        [field]: true,
    }, { preserveScroll: true });
};

const playBeep = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(1046, audioCtx.currentTime);
        osc.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.15);
    } catch (e) {}
};

const startCamera = async () => {
    try {
        isScanning.value = true;
        html5QrCode = new Html5Qrcode("reception-reader");
        await html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            (decodedText) => {
                processScan(decodedText);
            },
            (error) => {}
        );
    } catch (e) {
        isScanning.value = false;
        errorMessage.value = "Kamera tidak dapat diakses. Gunakan input manual.";
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
    <Head title="Receptionist Gate Scan Presensi & Snack Mobile" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-900 text-white p-4 max-w-md mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <span class="px-2.5 py-1 bg-purple-500/20 text-purple-300 font-extrabold text-xs rounded-full tracking-wider uppercase">
                        👩‍💼 Receptionist & Snack Desk
                    </span>
                    <h1 class="text-xl font-black mt-1 text-slate-100">Scan & Pengambilan Snack</h1>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-black text-purple-400">{{ stats?.total_reception_scanned || 0 }}</div>
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Wisudawan Hadir</div>
                </div>
            </div>

            <!-- Scanner Camera Box -->
            <div class="relative bg-slate-950 rounded-3xl overflow-hidden border-2 border-purple-900/50 shadow-2xl aspect-square flex flex-col items-center justify-center">
                <div id="reception-reader" class="w-full h-full"></div>

                <div v-if="!isScanning" class="text-center p-6 space-y-3">
                    <span class="text-6xl">📷</span>
                    <p class="text-sm text-slate-400 font-medium">Kamera tidak aktif</p>
                    <button @click="startCamera" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-lg transition">
                        Aktifkan Kamera HP
                    </button>
                </div>
            </div>

            <!-- Manual Token Form -->
            <form @submit.prevent="processScan(scanToken)" class="space-y-3">
                <label class="block text-xs font-semibold text-slate-400">Input QR Code / NIM Manual</label>
                <div class="flex gap-2">
                    <input 
                        v-model="scanToken" 
                        type="text" 
                        placeholder="Scan / Ketik NIM..." 
                        class="flex-1 bg-slate-950 border-slate-800 text-white rounded-xl focus:ring-purple-500 focus:border-purple-500 text-sm font-mono"
                    />
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 font-bold text-white text-xs rounded-xl transition shadow-lg"
                    >
                        Verifikasi
                    </button>
                </div>
            </form>

            <!-- Status Flash Messages -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-950 border border-emerald-800 text-emerald-200 text-sm rounded-2xl shadow-xl flex items-center gap-3">
                <span class="text-2xl">✅</span>
                <div>
                    <div class="font-bold">BERHASIL VERIFIKASI</div>
                    <div class="text-xs text-emerald-300 mt-0.5">{{ $page.props.flash.success }}</div>
                </div>
            </div>

            <div v-if="$page.props.flash?.error || errorMessage" class="p-4 bg-rose-950 border border-rose-800 text-rose-200 text-sm rounded-2xl shadow-xl flex items-center gap-3">
                <span class="text-2xl">🚨</span>
                <div>
                    <div class="font-bold">PEMBERITAHUAN / INVALID</div>
                    <div class="text-xs text-rose-300 mt-0.5">{{ $page.props.flash?.error || errorMessage }}</div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
