<script setup>
import PanitiaLayout from '@/Layouts/PanitiaLayout.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';

const props = defineProps({
    activePeriode: Object,
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const flashWarning = computed(() => page.props.flash?.warning);

const isCameraActive = ref(false);
const cameraError = ref(null);
const isProcessing = ref(false);

const form = useForm({
    qr_code_token: '',
});

let html5QrCode = null;

const startCamera = async () => {
    cameraError.value = null;
    try {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qr-reader');
        }
        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 15,
                // Scan seluruh area kamera tanpa membatasi box
            },
            (decodedText) => {
                if (isProcessing.value) return;
                form.qr_code_token = decodedText;
                submitScan();
            },
            () => {
                // Ignore parse errors per frame
            }
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
            isCameraActive.value = false;
        } catch (err) {
            console.error(err);
        }
    }
};

const submitScan = () => {
    if (!form.qr_code_token.trim() || isProcessing.value) return;

    isProcessing.value = true;

    form.post(route('panitia.presensi.scan'), {
        preserveScroll: true,
        onFinish: () => {
            form.reset();
            // Cooldown 2 detik setelah presensi agar tidak scan berulang
            setTimeout(() => {
                isProcessing.value = false;
            }, 2000);
        },
    });
};

onMounted(() => {
    startCamera();
});

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <Head title="Presensi Gate Barcode Scanner" />

    <PanitiaLayout>
        <div class="space-y-6">
            
            <!-- Page Header Card & Navigation Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Scanner Kamera Gate Wisudawan</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Arahkan Barcode / QR Tiket Wisudawan ke layar kamera untuk memindai presensi secara otomatis.
                    </p>
                </div>

                <!-- Sub-Navigation Switcher Tabs -->
                <div class="flex items-center bg-gray-100 dark:bg-gray-700/60 p-1 rounded-xl shrink-0">
                    <Link
                        :href="route('panitia.presensi')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm flex items-center gap-1.5"
                    >
                        <span>📷 Gate Scanner</span>
                    </Link>
                    <Link
                        :href="route('panitia.presensi.wisudawan')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white flex items-center gap-1.5"
                    >
                        <span>📋 Data Wisudawan</span>
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
                            {{ isProcessing ? 'Memproses Presensi...' : (isCameraActive ? 'Kamera Scanner Aktif' : 'Kamera Non-Aktif') }}
                        </span>
                    </div>

                    <button
                        @click="isCameraActive ? stopCamera() : startCamera()"
                        type="button"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition"
                        :class="isCameraActive ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 hover:bg-rose-500/30' : 'bg-indigo-600 text-white border-indigo-500 hover:bg-indigo-700'"
                    >
                        {{ isCameraActive ? '⏹ Matikan' : '📷 Aktifkan Kamera' }}
                    </button>
                </div>

                <!-- Full Fit Camera Viewport without Letterboxing/Black Bars -->
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
                </div>

                <!-- Camera Access Error Alert -->
                <div v-if="cameraError" class="p-4 bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold rounded-xl text-center">
                    {{ cameraError }}
                </div>

                <!-- Scan Feedback Alerts -->
                <div v-if="flashSuccess" class="p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 font-bold text-xs flex items-center justify-center gap-3 text-center animate-bounce">
                    <span class="text-lg">✅</span>
                    <div>{{ flashSuccess }}</div>
                </div>

                <div v-if="flashError" class="p-4 rounded-xl bg-red-500/20 border border-red-500/40 text-red-200 font-bold text-xs flex items-center justify-center gap-3 text-center">
                    <span class="text-lg">🚫</span>
                    <div>{{ flashError }}</div>
                </div>

                <div v-if="flashWarning" class="p-4 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-200 font-bold text-xs flex items-center justify-center gap-3 text-center">
                    <span class="text-lg">⚠️</span>
                    <div>{{ flashWarning }}</div>
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

/* Sembunyikan bingkai kotak putih & overlay gelap html5-qrcode */
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
</style>
