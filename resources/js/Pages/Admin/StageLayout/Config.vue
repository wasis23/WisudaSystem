<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    config: Object,
    activePeriode: Object,
});

const form = useForm({
    bg_image: null,
    remove_bg: false,
    photo_x: props.config.photo_x || 100,
    photo_y: props.config.photo_y || 150,
    photo_w: props.config.photo_w || 320,
    photo_h: props.config.photo_h || 420,
    nama_x: props.config.nama_x || 480,
    nama_y: props.config.nama_y || 180,
    nama_font_size: props.config.nama_font_size || 48,
    nim_x: props.config.nim_x || 480,
    nim_y: props.config.nim_y || 250,
    nim_font_size: props.config.nim_font_size || 24,
    prodi_x: props.config.prodi_x || 480,
    prodi_y: props.config.prodi_y || 290,
    prodi_font_size: props.config.prodi_font_size || 24,
    ipk_x: props.config.ipk_x || 480,
    ipk_y: props.config.ipk_y || 340,
    ipk_font_size: props.config.ipk_font_size || 28,
    ta_x: props.config.ta_x || 480,
    ta_y: props.config.ta_y || 400,
    ta_font_size: props.config.ta_font_size || 20,
    ta_max_w: props.config.ta_max_w || 700,
});

const bgPreview = ref(props.config.bg_image ? `/storage/${props.config.bg_image}` : null);

const handleBgChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.bg_image = file;
        form.remove_bg = false;
        bgPreview.value = URL.createObjectURL(file);
    }
};

const removeBgImage = () => {
    form.bg_image = null;
    form.remove_bg = true;
    bgPreview.value = null;
};

// Scaled 1280x720 Canvas auto-resize logic
const previewWrapperRef = ref(null);
const scaleFactor = ref(0.4);

const updateScale = () => {
    if (previewWrapperRef.value) {
        const width = previewWrapperRef.value.clientWidth;
        scaleFactor.value = width / 1280;
    }
};

let resizeObserver = null;

onMounted(() => {
    updateScale();
    window.addEventListener('resize', updateScale);
    if (previewWrapperRef.value && window.ResizeObserver) {
        resizeObserver = new ResizeObserver(() => updateScale());
        resizeObserver.observe(previewWrapperRef.value);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScale);
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});

const submitConfig = () => {
    form.post(route('admin.stage-layout.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Layar Panggung (Stage Display Precision Configurator)" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- Page Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Pengaturan Presisi Layar Wisuda</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Upload template PNG panggung dan atur posisi koordinat (X, Y) serta ukuran font elemen wisudawan secara presisi.
                    </p>
                </div>

                <button
                    @click="submitConfig"
                    :disabled="form.processing"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi Layar' }}</span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left Column: Precision Form Controls -->
                <div class="lg:col-span-5 space-y-6">
                    
                    <!-- Card 1: Upload Template Background PNG -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-3">
                        <span class="text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Template Background PNG Panggung</span>
                        </span>

                        <p class="text-[11px] text-gray-500 leading-relaxed">
                            Upload desain background PNG resolusi HD (1280x720 atau 1920x1080). Template ini akan digunakan di proyektor panggung dan preview dashboard wisudawan.
                        </p>

                        <div class="space-y-2">
                            <input
                                @change="handleBgChange"
                                type="file"
                                accept="image/png, image/jpeg, image/webp"
                                class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950/50 dark:file:text-indigo-300"
                            />
                            
                            <div v-if="bgPreview" class="flex items-center justify-between pt-1">
                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                    ✓ Template Background Aktif
                                </span>
                                <button
                                    @click="removeBgImage"
                                    type="button"
                                    class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline"
                                >
                                    Hapus Background
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Photo Position & Dimension -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-3">
                        <span class="text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Posisi & Dimensi Pas Foto</span>
                        </span>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Foto X (px)</label>
                                <input v-model.number="form.photo_x" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Foto Y (px)</label>
                                <input v-model.number="form.photo_y" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Lebar W (px)</label>
                                <input v-model.number="form.photo_w" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Tinggi H (px)</label>
                                <input v-model.number="form.photo_h" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Nama Lengkap Wisudawan -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-3">
                        <span class="text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                            <span>Nama Lengkap Wisudawan</span>
                        </span>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Nama X (px)</label>
                                <input v-model.number="form.nama_x" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Nama Y (px)</label>
                                <input v-model.number="form.nama_y" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Font (px)</label>
                                <input v-model.number="form.nama_font_size" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: NIM & Program Studi -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-3">
                        <span class="text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                            <span>NIM, Prodi & IPK (Y Coordinate)</span>
                        </span>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">NIM Y (px)</label>
                                <input v-model.number="form.nim_y" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">Prodi Y (px)</label>
                                <input v-model.number="form.prodi_y" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 dark:text-gray-400">IPK Y (px)</label>
                                <input v-model.number="form.ipk_y" type="number" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Perfectly Scaled Live Precision Stage Canvas Viewport -->
                <div class="lg:col-span-7 space-y-4">
                    
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live Stage Canvas Preview (Native 1280x720 Scaled)
                        </span>
                        <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 text-xs font-mono font-bold rounded-full border border-indigo-200 dark:border-indigo-800">
                            Ratio 16:9
                        </span>
                    </div>

                    <!-- Outer Wrapper responsive container -->
                    <div
                        ref="previewWrapperRef"
                        class="w-full aspect-video bg-slate-950 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl relative overflow-hidden select-none"
                        :style="{ height: `${1280 * scaleFactor * 9 / 16}px` }"
                    >
                        <!-- Inner Canvas 1280x720 with Scale Transformation -->
                        <div
                            class="absolute top-0 left-0 w-[1280px] h-[720px] origin-top-left pointer-events-none"
                            :style="{ transform: `scale(${scaleFactor})` }"
                        >
                            <!-- Template Background PNG Image -->
                            <img
                                v-if="bgPreview"
                                :src="bgPreview"
                                class="absolute inset-0 w-full h-full object-cover z-0"
                            />
                            
                            <!-- Gradient Glow Fallback if no PNG uploaded -->
                            <div v-else class="absolute inset-0 bg-gradient-to-br from-indigo-950 via-slate-950 to-purple-950 z-0">
                                <div class="absolute top-10 left-10 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
                                <div class="absolute bottom-10 right-10 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
                            </div>

                            <!-- Content Overlay Elements -->
                            <div class="relative z-10 w-full h-full text-white font-sans">
                                
                                <!-- Photo Bounding Box -->
                                <div
                                    :style="{
                                        left: form.photo_x + 'px',
                                        top: form.photo_y + 'px',
                                        width: form.photo_w + 'px',
                                        height: form.photo_h + 'px',
                                    }"
                                    class="absolute border-2 border-indigo-400/80 bg-slate-900/80 rounded-2xl flex items-center justify-center text-sm font-bold text-indigo-300 overflow-hidden shadow-xl"
                                >
                                    [ Pas Foto Wisudawan ]
                                </div>

                                <!-- Candidate Name -->
                                <div
                                    :style="{
                                        left: form.nama_x + 'px',
                                        top: form.nama_y + 'px',
                                        fontSize: form.nama_font_size + 'px',
                                    }"
                                    class="absolute font-black text-white whitespace-nowrap leading-none drop-shadow-lg"
                                >
                                    NAMA WISUDAWAN, A.Md.
                                </div>

                                <!-- NIM -->
                                <div
                                    :style="{
                                        left: form.nim_x + 'px',
                                        top: form.nim_y + 'px',
                                        fontSize: form.nim_font_size + 'px',
                                    }"
                                    class="absolute font-mono font-bold text-indigo-400 leading-none drop-shadow"
                                >
                                    NIM: 2026010099
                                </div>

                                <!-- Prodi -->
                                <div
                                    :style="{
                                        left: form.prodi_x + 'px',
                                        top: form.prodi_y + 'px',
                                        fontSize: form.prodi_font_size + 'px',
                                    }"
                                    class="absolute font-semibold text-slate-200 leading-none drop-shadow"
                                >
                                    D3 Sistem Informasi - Politeknik Indonusa Surakarta
                                </div>

                                <!-- IPK -->
                                <div
                                    :style="{
                                        left: form.ipk_x + 'px',
                                        top: form.ipk_y + 'px',
                                        fontSize: form.ipk_font_size + 'px',
                                    }"
                                    class="absolute font-mono font-bold text-emerald-400 leading-none drop-shadow"
                                >
                                    IPK: 3.85 (Cumlaude ★)
                                </div>

                                <!-- Judul TA -->
                                <div
                                    :style="{
                                        left: form.ta_x + 'px',
                                        top: form.ta_y + 'px',
                                        fontSize: form.ta_font_size + 'px',
                                        maxWidth: form.ta_max_w + 'px',
                                    }"
                                    class="absolute font-medium italic text-slate-300 leading-snug line-clamp-2 drop-shadow"
                                >
                                    "Rancang Bangun Sistem Informasi Manajemen Wisuda Berbasis Web Pada Politeknik Indonusa Surakarta"
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Dimension Info Box -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                        <span>Faktor Skala Otomatis: <strong class="font-mono text-indigo-600 dark:text-indigo-400">{{ (scaleFactor * 100).toFixed(1) }}%</strong></span>
                        <span>Kanvas Resolusi: <strong class="font-mono text-gray-700 dark:text-gray-300">1280px × 720px</strong></span>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
