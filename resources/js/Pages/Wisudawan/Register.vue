<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const props = defineProps({
    wisudawan: Object,
    activePeriode: Object,
    programStudis: Array,
    stageConfig: Object,
});

// Photo & cropper state
const photoPreview = ref(props.wisudawan?.pas_foto ? `/storage/${props.wisudawan.pas_foto}` : null);
const showCropModal = ref(false);
const cropperRef = ref(null);
const cropperFileInputRef = ref(null);
const rawImageSrc = ref('');

const form = useForm({
    program_studi_id: props.wisudawan?.program_studi_id || (props.programStudis?.[0]?.id || ''),
    nim: props.wisudawan?.nim || '',
    nama_lengkap: props.wisudawan?.nama_lengkap || '',
    gelar: props.wisudawan?.gelar || 'A.Md.Kom.',
    nik: props.wisudawan?.nik || '',
    tempat_lahir: props.wisudawan?.tempat_lahir || '',
    tanggal_lahir: props.wisudawan?.tanggal_lahir || '',
    jenis_kelamin: props.wisudawan?.jenis_kelamin || 'L',
    nomor_hp: props.wisudawan?.nomor_hp || '',
    alamat: props.wisudawan?.alamat || '',
    ipk: props.wisudawan?.ipk || '3.50',
    judul_ta: props.wisudawan?.judul_ta || '',
    dosen_pembimbing_1: props.wisudawan?.dosen_pembimbing_1 || '',
    dosen_pembimbing_2: props.wisudawan?.dosen_pembimbing_2 || '',
    dosen_penguji: props.wisudawan?.dosen_penguji || '',
    tanggal_lulus: props.wisudawan?.tanggal_lulus || '',
    nama_ayah: props.wisudawan?.nama_ayah || '',
    nama_ibu: props.wisudawan?.nama_ibu || '',
    pas_foto: null,
});

const selectedProdiName = computed(() => {
    const prodi = props.programStudis?.find(p => p.id === Number(form.program_studi_id));
    return prodi ? prodi.nama_prodi : 'Program Studi';
});

const selectedJenisKelaminLabel = computed(() => {
    return form.jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-Laki';
});

const formattedFullName = computed(() => {
    const name = form.nama_lengkap || 'NAMA MAHASISWA';
    const degree = form.gelar ? `, ${form.gelar}` : '';
    return `${name}${degree}`;
});

// Open file picker → open crop modal
const openFilePicker = () => {
    cropperFileInputRef.value?.click();
};

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    rawImageSrc.value = URL.createObjectURL(file);
    showCropModal.value = true;
    e.target.value = '';
};

const zoomIn = () => cropperRef.value?.zoom(1.15);
const zoomOut = () => cropperRef.value?.zoom(0.85);
const rotateLeft = () => cropperRef.value?.rotate(-90);
const rotateRight = () => cropperRef.value?.rotate(90);
const resetCrop = () => cropperRef.value?.refresh();

const applyCrop = () => {
    if (!cropperRef.value) return;
    const { canvas } = cropperRef.value.getResult();
    if (!canvas) return;

    // Rescale canvas to 600x800 for high precision
    const finalCanvas = document.createElement('canvas');
    finalCanvas.width = 600;
    finalCanvas.height = 800;
    const ctx = finalCanvas.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(canvas, 0, 0, 600, 800);

    finalCanvas.toBlob((blob) => {
        const croppedFile = new File([blob], 'pas_foto.jpg', { type: 'image/jpeg' });
        form.pas_foto = croppedFile;
        photoPreview.value = URL.createObjectURL(blob);
        closeCropModal();
    }, 'image/jpeg', 0.92);
};

const closeCropModal = () => {
    showCropModal.value = false;
};

// Canvas scale observer for exact 1280x720 preview
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
    if (resizeObserver) resizeObserver.disconnect();
});

const submitForm = () => {
    form.post(route('wisudawan.pendaftaran.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Formulir Biodata Wisudawan & Live Stage Preview" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Pendaftaran Biodata & Live Preview Layar Wisuda</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Lengkapi data pribadi dan pas foto resmi untuk penayangan di proyektor panggung auditorium.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Registration Form Inputs -->
                <div class="lg:col-span-7 space-y-6 bg-white dark:bg-slate-800 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="pb-3 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="font-extrabold text-lg text-slate-900 dark:text-white">
                            Biodata Calon Wisudawan
                        </h3>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        
                        <!-- Program Studi & NIM (Input Biasa Readonly) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                                    Program Studi
                                </label>
                                <input readonly :value="selectedProdiName" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 text-sm cursor-not-allowed" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                                    NIM
                                </label>
                                <input readonly v-model="form.nim" type="text" placeholder="Contoh: 2026010099" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 text-sm font-mono cursor-not-allowed" />
                            </div>
                        </div>

                        <!-- Nama Lengkap (Editable) & Gelar (Readonly) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Lengkap
                                </label>
                                <input v-model="form.nama_lengkap" type="text" placeholder="Contoh: Budi Santoso" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm focus:ring-indigo-500 focus:border-indigo-500" required />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                                    Gelar Akademik
                                </label>
                                <input readonly v-model="form.gelar" type="text" placeholder="A.Md.Kom." class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 text-sm font-semibold cursor-not-allowed" />
                            </div>
                        </div>

                        <!-- IPK, Tgl Lulus & Jenis Kelamin (Input Biasa Readonly) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                                    IPK
                                </label>
                                <input readonly v-model="form.ipk" type="number" step="0.01" min="0" max="4.00" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 text-sm font-mono cursor-not-allowed" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                                    Tgl Lulus
                                </label>
                                <input readonly v-model="form.tanggal_lulus" type="date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 text-sm cursor-not-allowed" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                                    Jenis Kelamin
                                </label>
                                <input readonly :value="selectedJenisKelaminLabel" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 text-sm cursor-not-allowed" />
                            </div>
                        </div>

                        <!-- Judul Tugas Akhir / Skripsi (Editable) -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                                Judul Tugas Akhir / Skripsi
                            </label>
                            <textarea v-model="form.judul_ta" rows="2" placeholder="Tuliskan judul TA sesuai terdaftar di akademik..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" required></textarea>
                        </div>

                        <!-- Dosen Pembimbing 1, 2 & Penguji (Editable) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                                    Dosen Pembimbing 1 (Utama)
                                </label>
                                <input v-model="form.dosen_pembimbing_1" type="text" placeholder="Nama dosen & gelar..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                                    Dosen Pembimbing 2 (Pendamping)
                                </label>
                                <input v-model="form.dosen_pembimbing_2" type="text" placeholder="Nama dosen & gelar..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                                    Dosen Penguji
                                </label>
                                <input v-model="form.dosen_penguji" type="text" placeholder="Nama dosen & gelar..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" />
                            </div>
                        </div>

                        <!-- Nama Ayah & Nama Ibu (Editable) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nama Ayah Kandung</label>
                                <input v-model="form.nama_ayah" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nama Ibu Kandung</label>
                                <input v-model="form.nama_ibu" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                            </div>
                        </div>

                        <!-- Tempat Lahir & Tanggal Lahir (Editable) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                                <input v-model="form.tempat_lahir" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                                <input v-model="form.tanggal_lahir" type="date" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                            </div>
                        </div>

                        <!-- Nomor HP -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nomor WhatsApp / HP</label>
                            <input v-model="form.nomor_hp" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                        </div>

                        <!-- Upload & Crop Pas Foto -->
                        <div class="col-span-1 sm:col-span-2">
                            <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-2">Pas Foto Resmi (Crop 3:4)</label>
                            <div class="flex items-center gap-4">
                                <!-- Preview Thumbnail -->
                                <div class="w-20 h-[106px] rounded-xl overflow-hidden border-2 border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-900 flex items-center justify-center shrink-0">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" alt="Preview Foto" />
                                    <span v-else class="text-2xl">🖼️</span>
                                </div>
                                <!-- Upload Button -->
                                <div class="flex-1">
                                    <button
                                        type="button"
                                        @click="openFilePicker"
                                        class="w-full py-3 px-4 border-2 border-dashed border-indigo-300 dark:border-indigo-700 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition text-center"
                                    >
                                        {{ photoPreview ? '🔄 Ganti & Crop Ulang Foto' : '📷 Pilih & Posisikan Pas Foto' }}
                                    </button>
                                    <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">
                                        Foto akan di-crop otomatis ke ukuran <strong>3:4</strong> (600×800px) sesuai posisi yang Anda pilih.
                                    </p>
                                </div>
                            </div>
                            <!-- Hidden file input -->
                            <input ref="cropperFileInputRef" @change="handlePhotoChange" type="file" accept="image/*" class="hidden" />
                        </div>

                        <!-- Alamat Lengkap (Editable) -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Alamat Lengkap</label>
                            <input v-model="form.alamat" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                        </div>

                        <div class="pt-6 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-indigo-500/25"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Biodata & Pas Foto' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- LIVE STAGE PROJECTION PREVIEW CARD (REAL-TIME SCALED PREVIEW) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="bg-indigo-900 text-white p-4 rounded-2xl border border-indigo-700 shadow-md">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-300 block">🖥 LIVE PREVIEW TAMPILAN HARI WISUDA</span>
                        <p class="text-xs text-indigo-200 mt-1">Begini penayangan nama, foto, dan data Anda di layar proyektor panggung saat prosesi wisuda!</p>
                    </div>

                    <!-- Outer Wrapper Responsive Container -->
                    <div
                        ref="previewWrapperRef"
                        class="w-full aspect-video bg-slate-950 rounded-2xl border-4 border-slate-800 shadow-2xl relative overflow-hidden select-none"
                        :style="{ height: `${1280 * scaleFactor * 9 / 16}px` }"
                    >
                        <!-- Inner Canvas 1280x720 with Scale Transformation -->
                        <div
                            class="absolute top-0 left-0 w-[1280px] h-[720px] origin-top-left pointer-events-none"
                            :style="{ transform: `scale(${scaleFactor})` }"
                        >
                            <!-- Template Background PNG Image -->
                            <img
                                v-if="stageConfig?.bg_image"
                                :src="`/storage/${stageConfig.bg_image}`"
                                class="absolute inset-0 w-full h-full object-cover z-0"
                            />
                            
                            <!-- Gradient Glow Fallback if no PNG uploaded -->
                            <div v-else class="absolute inset-0 bg-gradient-to-br from-indigo-950 via-slate-950 to-purple-950 z-0">
                                <div class="absolute top-10 left-10 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
                                <div class="absolute bottom-10 right-10 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
                            </div>

                            <!-- Elements at Exact Presisi Coordinates -->
                            <div class="relative z-10 w-full h-full text-white font-sans">
                                
                                <!-- Live Photo -->
                                <div
                                    :style="{
                                        left: (stageConfig?.photo_x || 100) + 'px',
                                        top: (stageConfig?.photo_y || 150) + 'px',
                                        width: (stageConfig?.photo_w || 320) + 'px',
                                        height: (stageConfig?.photo_h || 420) + 'px',
                                    }"
                                    class="absolute border-4 border-white/20 bg-slate-900 rounded-2xl overflow-hidden shadow-2xl"
                                >
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-sm text-slate-500 font-bold">Pas Foto</div>
                                </div>

                                <!-- Live Candidate Name & Gelar -->
                                <div
                                    :style="{
                                        left: (stageConfig?.nama_x || 480) + 'px',
                                        top: (stageConfig?.nama_y || 180) + 'px',
                                        fontSize: (stageConfig?.nama_font_size || 48) + 'px',
                                    }"
                                    class="absolute font-black text-white whitespace-nowrap leading-none drop-shadow-lg"
                                >
                                    {{ formattedFullName }}
                                </div>

                                <!-- Live NIM -->
                                <div
                                    :style="{
                                        left: (stageConfig?.nim_x || 480) + 'px',
                                        top: (stageConfig?.nim_y || 250) + 'px',
                                        fontSize: (stageConfig?.nim_font_size || 24) + 'px',
                                    }"
                                    class="absolute font-mono font-bold text-indigo-400 leading-none drop-shadow"
                                >
                                    NIM: {{ form.nim || '2026XXXXXX' }}
                                </div>

                                <!-- Live Prodi -->
                                <div
                                    :style="{
                                        left: (stageConfig?.prodi_x || 480) + 'px',
                                        top: (stageConfig?.prodi_y || 290) + 'px',
                                        fontSize: (stageConfig?.prodi_font_size || 24) + 'px',
                                    }"
                                    class="absolute font-semibold text-slate-200 leading-none drop-shadow"
                                >
                                    {{ selectedProdiName }} - Politeknik Indonusa Surakarta
                                </div>

                                <!-- Live IPK -->
                                <div
                                    :style="{
                                        left: (stageConfig?.ipk_x || 480) + 'px',
                                        top: (stageConfig?.ipk_y || 340) + 'px',
                                        fontSize: (stageConfig?.ipk_font_size || 28) + 'px',
                                    }"
                                    class="absolute font-mono font-bold text-emerald-400 leading-none drop-shadow"
                                >
                                    IPK: {{ form.ipk || '3.50' }} {{ Number(form.ipk) >= 3.51 ? '(Cumlaude ★)' : '' }}
                                </div>

                                <!-- Live TA Title -->
                                <div
                                    :style="{
                                        left: (stageConfig?.ta_x || 480) + 'px',
                                        top: (stageConfig?.ta_y || 400) + 'px',
                                        fontSize: (stageConfig?.ta_font_size || 20) + 'px',
                                        maxWidth: (stageConfig?.ta_max_w || 700) + 'px',
                                    }"
                                    class="absolute font-medium italic text-slate-300 leading-snug line-clamp-2 drop-shadow"
                                >
                                    "{{ form.judul_ta || 'Judul Tugas Akhir / Skripsi Mahasiswa...' }}"
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- ====== CROP MODAL OVERLAY ====== -->
    <Teleport to="body">
        <div
            v-if="showCropModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        >
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Posisikan & Crop Pas Foto</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Geser / perbesar foto hingga posisi pas foto Anda sesuai. Rasio otomatis <strong>3:4</strong>.</p>
                    </div>
                    <button @click="closeCropModal" type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-2xl leading-none">&times;</button>
                </div>

                <!-- Instagram / WA Style Cropper Area (Fixed 450px Height) -->
                <div class="relative w-full h-[450px] bg-slate-950 flex items-center justify-center overflow-hidden">
                    <Cropper
                        ref="cropperRef"
                        class="w-full h-full"
                        :src="rawImageSrc"
                        :stencil-props="{
                            aspectRatio: 3 / 4,
                            movable: false,
                            resizable: false
                        }"
                        image-restriction="none"
                        :auto-zoom="true"
                    />
                </div>

                <!-- Quick Control Toolbar (Rotate, Zoom, Reset) -->
                <div class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800/80 border-t border-b border-gray-200 dark:border-gray-700/60 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 mr-1 uppercase tracking-wider">Rotasi:</span>
                        <button type="button" @click="rotateLeft" title="Putar 90° Kiri" class="p-1.5 rounded-lg bg-white dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 text-xs font-bold text-gray-700 dark:text-gray-200 transition flex items-center gap-1">
                            ↪ 90° Kiri
                        </button>
                        <button type="button" @click="rotateRight" title="Putar 90° Kanan" class="p-1.5 rounded-lg bg-white dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 text-xs font-bold text-gray-700 dark:text-gray-200 transition flex items-center gap-1">
                            ↩ 90° Kanan
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 mr-1 uppercase tracking-wider">Zoom & Reset:</span>
                        <button type="button" @click="zoomOut" title="Zoom Out" class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 text-xs font-black text-gray-700 dark:text-gray-200 transition">
                            ➖ Zoom
                        </button>
                        <button type="button" @click="zoomIn" title="Zoom In" class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 text-xs font-black text-gray-700 dark:text-gray-200 transition">
                            ➕ Zoom
                        </button>
                        <button type="button" @click="resetCrop" title="Reset Posisi" class="px-2.5 py-1.5 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 hover:bg-amber-500/20 border border-amber-300 dark:border-amber-700 text-xs font-bold text-amber-700 dark:text-amber-300 transition">
                            ↺ Reset
                        </button>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="px-6 py-4 flex items-center justify-between gap-3">
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 space-y-0.5">
                        <p class="flex items-center gap-1">👤 <strong>Garis Putih:</strong> Posisikan kepala & bahu di dalam garis panduan</p>
                        <p class="flex items-center gap-1">🖱️ <strong>Geser / Zoom:</strong> Sesuaikan ukuran wajah agar proporsional</p>
                    </div>
                    <div class="flex gap-3 shrink-0">
                        <button
                            type="button"
                            @click="closeCropModal"
                            class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-xs font-bold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="applyCrop"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold transition shadow-md shadow-indigo-500/20"
                        >
                            ✅ Gunakan Foto Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

</template>

<style scoped>
:deep(.vue-advanced-cropper) {
    background: #090d16 !important;
}

:deep(.vue-rectangle-stencil) {
    border: 2.5px solid #6366f1 !important;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7) !important;
}

/* Face Alignment Oval & Shoulder Guide Overlay inside Instagram/WA Stencil */
:deep(.vue-rectangle-stencil::after) {
    content: '';
    position: absolute;
    top: 14%;
    left: 50%;
    transform: translateX(-50%);
    width: 52%;
    height: 48%;
    border: 2px dashed rgba(255, 255, 255, 0.85);
    border-radius: 50% 50% 45% 45%;
    pointer-events: none;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.4);
}

:deep(.vue-rectangle-stencil::before) {
    content: 'AREA WAJAH';
    position: absolute;
    top: 5%;
    left: 50%;
    transform: translateX(-50%);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.8px;
    pointer-events: none;
    white-space: nowrap;
    background: rgba(99, 102, 241, 0.85);
    padding: 2px 10px;
    border-radius: 999px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
</style>
