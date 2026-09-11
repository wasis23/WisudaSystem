<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stats: Object,
    recentWisudawan: Array,
    stageConfig: Object,
    activePeriode: Object,
    manualBookUrl: String,
});

const tracerPercentage = computed(() => {
    if (!props.stats?.totalWisudawan) return 0;
    return Math.round((props.stats.tracerCompleted / props.stats.totalWisudawan) * 100);
});

// Manual Book Form State
const uploadForm = useForm({
    manual_book: null,
    periode_id: props.activePeriode?.id || null,
});

const fileInputRef = ref(null);
const isDragging = ref(false);
const selectedFileName = ref('');
const showDeleteConfirm = ref(false);

const handleFileSelect = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('Format berkas harus berupa dokumen PDF (.pdf)');
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            alert('Ukuran berkas PDF maksimal 20 MB');
            return;
        }
        uploadForm.manual_book = file;
        selectedFileName.value = file.name;
    }
};

const handleDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) {
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('Format berkas harus berupa dokumen PDF (.pdf)');
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            alert('Ukuran berkas PDF maksimal 20 MB');
            return;
        }
        uploadForm.manual_book = file;
        selectedFileName.value = file.name;
    }
};

const submitUpload = () => {
    if (!uploadForm.manual_book) return;
    uploadForm.post(route('admin.manual-book.upload'), {
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
            selectedFileName.value = '';
            if (fileInputRef.value) fileInputRef.value.value = '';
        },
    });
};

const deleteManualBook = () => {
    uploadForm.delete(route('admin.manual-book.destroy'), {
        data: { periode_id: props.activePeriode?.id },
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
        },
    });
};

const cancelFileSelect = () => {
    uploadForm.manual_book = null;
    selectedFileName.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';
};
</script>

<template>
    <Head title="Dashboard - Sistem Wisuda" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- KPI CARDS (6 Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                
                <!-- KPI 1: Total Wisudawan -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Wisudawan</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ stats?.totalWisudawan || 0 }}
                        </span>
                        <span class="text-xs text-gray-400 font-medium">Terdaftar</span>
                    </div>
                    <div class="mt-3 text-xs text-blue-600 dark:text-blue-400 font-medium truncate">
                        {{ stats?.totalProdi || 0 }} Program Studi
                    </div>
                </div>

                <!-- KPI 2: SIKEU Lunas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">SIKEU Lunas</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                            {{ stats?.lunasCount || 0 }}
                        </span>
                        <span class="text-xs text-emerald-600 font-semibold">Lunas</span>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 font-medium truncate">
                        {{ stats?.totalExtraGuests || 0 }} Ekstra Tamu
                    </div>
                </div>

                <!-- KPI 3: SIKEU Belum Lunas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Belum Lunas</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                            {{ stats?.belumLunasCount || 0 }}
                        </span>
                        <span class="text-xs text-rose-500 font-semibold">Pending</span>
                    </div>
                    <div class="mt-3 text-xs text-rose-500 font-medium truncate">
                        Blokir Prosesi
                    </div>
                </div>

                <!-- KPI 4: PESERTA BELUM HADIR -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Belum Hadir</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-amber-600 dark:text-amber-400 tracking-tight">
                            {{ stats?.belumHadirCount ?? 0 }}
                        </span>
                        <span class="text-xs text-amber-600 font-semibold">Peserta</span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 font-medium truncate">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Belum Check-in
                    </div>
                </div>

                <!-- KPI 5: HADIR GATE & AUDITORIUM -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sudah Hadir</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                            {{ stats?.hadirCount || 0 }}
                        </span>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full truncate">
                            {{ stats?.auditoriumCount || 0 }} di Audi
                        </span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium truncate">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Gate Scan Presensi
                    </div>
                </div>

                <!-- KPI 6: Tracer Study Terisi -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Tracer Study</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">
                            {{ stats?.tracerCompleted || 0 }}
                        </span>
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded-full">
                            {{ tracerPercentage }}%
                        </span>
                    </div>
                    <div class="mt-3 w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" :style="{ width: `${tracerPercentage}%` }"></div>
                    </div>
                </div>
            </div>

            <!-- MANUAL BOOK & GUIDELINE MANAGEMENT SECTION -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 sm:p-6 transition hover:shadow-md">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Left: Info & Status -->
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white">
                                    Manual Book Wisudawan
                                </h3>
                                <span
                                    v-if="activePeriode?.manual_book_pdf"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    PDF Aktif
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Belum Diunggah
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Berkas panduan (PDF, maks 20 MB) yang dapat diunduh oleh wisudawan pada dashboard mereka.
                            </p>

                            <!-- File Link & Delete -->
                            <div v-if="activePeriode?.manual_book_pdf && !selectedFileName" class="flex items-center gap-3 pt-1 text-xs">
                                <a
                                    :href="manualBookUrl || route('manual-book.download')"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 hover:underline"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span>Lihat Berkas PDF</span>
                                </a>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <button
                                    type="button"
                                    @click="showDeleteConfirm = true"
                                    class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 font-medium inline-flex items-center gap-1"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Hapus Berkas</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Actions / Form -->
                    <div class="flex items-center gap-2 shrink-0 self-start md:self-center">
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".pdf,application/pdf"
                            class="hidden"
                            @change="handleFileSelect"
                        />

                        <!-- State 1: File Selected -> Confirm Upload / Cancel -->
                        <template v-if="selectedFileName">
                            <div class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-xl px-3 py-1.5">
                                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-200 max-w-[160px] truncate" :title="selectedFileName">
                                    📄 {{ selectedFileName }}
                                </span>
                                <button
                                    type="button"
                                    @click="submitUpload"
                                    :disabled="uploadForm.processing"
                                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-semibold rounded-lg transition inline-flex items-center gap-1.5 shadow-sm"
                                >
                                    <svg v-if="uploadForm.processing" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>{{ uploadForm.processing ? 'Mengunggah...' : 'Simpan PDF' }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="cancelFileSelect"
                                    :disabled="uploadForm.processing"
                                    title="Batal"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <!-- State 2: Normal Button -->
                        <template v-else>
                            <button
                                type="button"
                                @click="fileInputRef?.click()"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition inline-flex items-center gap-2 shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>{{ activePeriode?.manual_book_pdf ? 'Ganti Berkas PDF' : 'Upload Berkas PDF' }}</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Delete Confirmation Dialog -->
                <div v-if="showDeleteConfirm" class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <span class="text-rose-600 dark:text-rose-400 font-medium">
                        Hapus berkas Manual Book PDF dari sistem untuk periode ini?
                    </span>
                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            @click="deleteManualBook"
                            :disabled="uploadForm.processing"
                            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg transition"
                        >
                            Ya, Hapus
                        </button>
                        <button
                            type="button"
                            @click="showDeleteConfirm = false"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <!-- MAIN MODULES SECTION -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Modul Utama Administrasi</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Card 1: Presisi Layar Wisuda -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <Link :href="route('admin.stage-layout.edit')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Atur 
                            </Link>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Presisi Layar Wisuda</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                Konfigurasi tata letak elemen visual (koordinat, ukuran font & foto) untuk proyektor panggung.
                            </p>
                        </div>
                    </div>

                    <!-- Card 2: Periode Wisuda -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <Link :href="route('admin.periode.index')" class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline">
                                Kelola 
                            </Link>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Periode Wisuda</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                Buat gelombang wisuda baru, tentukan kuota pendaftaran, dan aktifkan status periode.
                            </p>
                        </div>
                    </div>

                    <!-- Card 3: Buku Kenangan -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <Link :href="route('admin.buku-kenangan.index')" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                Kelola 
                            </Link>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Data Wisudawan & Buku Kenangan</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                Lihat daftar lengkap wisudawan terdaftar dan cetak kompilasi PDF Buku Kenangan Wisuda.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT REGISTERED WISUDAWAN TABLE CARD -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                            <span>Pendaftaran Wisudawan Terbaru</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar calon wisudawan yang baru mendaftar di sistem.</p>
                    </div>

                    <Link
                        :href="route('admin.buku-kenangan.index')"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                    >
                        Lihat Semua Wisudawan →
                    </Link>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                                <th class="py-3 px-3">Wisudawan</th>
                                <th class="py-3 px-3">NIM</th>
                                <th class="py-3 px-3">Program Studi</th>
                                <th class="py-3 px-3 text-center">Status SIKEU</th>
                                <th class="py-3 px-3 text-center">Tracer Study</th>
                                <th class="py-3 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
                            <template v-if="recentWisudawan && recentWisudawan.length > 0">
                                <tr v-for="w in recentWisudawan" :key="w.id" class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950 overflow-hidden flex items-center justify-center font-bold text-indigo-600 text-xs shrink-0 border border-indigo-200 dark:border-indigo-700">
                                                <img v-if="w.pas_foto" :src="`/storage/${w.pas_foto}`" class="w-full h-full object-cover" />
                                                <span v-else>{{ w.nama_lengkap?.charAt(0) }}</span>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white">
                                                    {{ w.nama_lengkap }}{{ w.gelar ? `, ${w.gelar}` : '' }}
                                                </div>
                                                <div class="text-[10px] text-gray-400 truncate max-w-xs">{{ w.judul_ta || 'Belum mengisi judul TA' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-gray-600 dark:text-gray-400">
                                        {{ w.nim }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ w.program_studi?.nama_prodi || 'Program Studi' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span
                                            :class="[
                                                'px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide inline-flex items-center gap-1.5',
                                                w.status_pembayaran_sikeu === 'lunas'
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300'
                                            ]"
                                        >
                                            <svg v-if="w.status_pembayaran_sikeu === 'lunas'" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="w-3 h-3 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>{{ w.status_pembayaran_sikeu === 'lunas' ? 'Lunas' : 'Belum Bayar' }}</span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span
                                            :class="[
                                                'px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1',
                                                w.is_tracer_study_filled
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                            ]"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            {{ w.is_tracer_study_filled ? 'Lengkap' : 'Belum Isi' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <Link
                                            :href="route('admin.buku-kenangan.index')"
                                            class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                        >
                                            Detail 
                                        </Link>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="5" class="py-8 text-center text-gray-400 text-xs">
                                    Belum ada data pendaftaran wisudawan terbaru.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
