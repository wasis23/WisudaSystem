<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import Cropper from 'cropperjs';

const props = defineProps({
    wisudawan: Object,
    activePeriode: Object,
    programStudis: Array,
    stageConfig: Object,
});

// Photo & cropper state
const photoPreview = ref(props.wisudawan?.pas_foto ? `/storage/${props.wisudawan.pas_foto}` : null);
const showCropModal = ref(false);
const cropperImgRef = ref(null);
const cropperFileInputRef = ref(null);
let cropperInstance = null;
let rawImageSrc = null;

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
    rawImageSrc = URL.createObjectURL(file);
    showCropModal.value = true;
    nextTick(() => {
        initCropper();
    });
    // Reset input so same file can be re-selected
    e.target.value = '';
};

const initCropper = () => {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
    if (!cropperImgRef.value) return;
    cropperImgRef.value.src = rawImageSrc;
    cropperInstance = new Cropper(cropperImgRef.value, {
        aspectRatio: 3 / 4,
        viewMode: 1,
        dragMode: 'move',          // Mengunci canvas agar hanya gambar yang digeser/di-zoom
        autoCropArea: 0.8,
        movable: true,
        zoomable: true,
        rotatable: false,
        scalable: false,
        guides: true,
        highlight: false,
        cropBoxMovable: false,     // Kotak crop terkunci di tempat
        cropBoxResizable: false,   // Kotak crop TIDAK bisa di-resize sama sekali
        toggleDragModeOnDblclick: false, // Mencegah dblclick merubah mode
        background: false,
        responsive: true,
        checkOrientation: true,
    });
};

const applyCrop = () => {
    if (!cropperInstance) return;
    cropperInstance.getCroppedCanvas({
        width: 600,
        height: 800,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    }).toBlob((blob) => {
        // Set the cropped file into form
        const croppedFile = new File([blob], 'pas_foto.jpg', { type: 'image/jpeg' });
        form.pas_foto = croppedFile;
        // Update live preview
        photoPreview.value = URL.createObjectURL(blob);
        closeCropModal();
    }, 'image/jpeg', 0.92);
};

const closeCropModal = () => {
    showCropModal.value = false;
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
    if (rawImageSrc) {
        URL.revokeObjectURL(rawImageSrc);
        rawImageSrc = null;
    }
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
    if (cropperInstance) cropperInstance.destroy();
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

                <!-- Cropper Area: auto-height mengikuti gambar asli, tidak ada area hitam -->
                <div class="relative overflow-hidden" style="max-height: 70vh; background: #000;">
                    <img
                        ref="cropperImgRef"
                        src=""
                        alt="Crop Preview"
                        class="block w-full"
                        style="max-height: 70vh; display: block;"
                    />
                </div>

                <!-- Modal Footer Actions -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                        <p>🖱️ <strong>Geser</strong> kotak untuk mengatur posisi</p>
                        <p>🔍 <strong>Scroll</strong> untuk zoom in/out</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="closeCropModal"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="applyCrop"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition shadow-sm"
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
:deep(.cropper-line),
:deep(.cropper-point) {
    display: none !important;
}
:deep(.cropper-view-box) {
    outline: 2px solid #6366f1 !important;
    outline-color: #6366f1 !important;
}
</style>
