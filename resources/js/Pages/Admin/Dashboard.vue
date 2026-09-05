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

            <!-- MANUAL BOOK & GUIDELINE MANAGEMENT SECTION (NEW) -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl border border-indigo-900/50 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="flex items-center gap-2.5">
                            <span class="px-3 py-1 bg-indigo-500/20 border border-indigo-400/40 text-indigo-300 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span>Buku Panduan Wisudawan (Manual Book PDF)</span>
                            </span>
                            <span
                                v-if="activePeriode?.manual_book_pdf"
                                class="px-2.5 py-0.5 bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 rounded-full text-[11px] font-bold"
                            >
                                ✓ PDF Aktif
                            </span>
                            <span
                                v-else
                                class="px-2.5 py-0.5 bg-amber-500/20 border border-amber-400/40 text-amber-300 rounded-full text-[11px] font-bold"
                            >
                                ! Belum Diunggah
                            </span>
                        </div>

                        <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            Kelola & Upload Berkas Manual Book Wisudawan
                        </h3>

                        <p class="text-xs sm:text-sm text-indigo-200/90 leading-relaxed font-normal">
                            Unggah berkas Buku Panduan (format <strong class="text-white">PDF</strong>, maks 20 MB). Berkas ini akan secara otomatis tampil dan dapat diunduh oleh seluruh calon wisudawan langsung di halaman Dashboard Mandiri mereka untuk panduan pengisian tracer study, biodata, tiket, hingga gladi wisuda.
                        </p>

                        <!-- Existing File Info -->
                        <div v-if="activePeriode?.manual_book_pdf" class="flex items-center gap-3 pt-1 text-xs text-indigo-200">
                            <span class="font-mono bg-white/10 px-2.5 py-1 rounded-lg border border-white/15">
                                📄 {{ activePeriode.manual_book_pdf.split('/').pop() }}
                            </span>
                            <a
                                :href="manualBookUrl || route('manual-book.download')"
                                target="_blank"
                                class="text-emerald-400 hover:text-emerald-300 font-bold underline flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span>Preview / Download PDF</span>
                            </a>
                        </div>
                    </div>

                    <!-- Upload Action Area -->
                    <div class="shrink-0 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 bg-white/5 p-4 rounded-2xl border border-white/10 backdrop-blur-sm">
                        
                        <!-- Hidden File Input -->
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".pdf,application/pdf"
                            class="hidden"
                            @change="handleFileSelect"
                        />

                        <!-- File Select / Drag Drop Button -->
                        <div
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            :class="[
                                'border-2 border-dashed rounded-xl px-4 py-3 text-center cursor-pointer transition flex flex-col items-center justify-center gap-1 min-w-[200px]',
                                isDragging ? 'border-indigo-400 bg-indigo-500/20' : 'border-indigo-400/40 hover:border-indigo-400 bg-indigo-900/30'
                            ]"
                            @click="fileInputRef?.click()"
                        >
                            <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <span class="text-xs font-bold text-white">
                                {{ selectedFileName || (activePeriode?.manual_book_pdf ? 'Ganti File PDF...' : 'Pilih Berkas PDF...') }}
                            </span>
                            <span class="text-[10px] text-indigo-300">Format .PDF maks 20 MB</span>
                        </div>

                        <!-- Upload / Save Button -->
                        <div class="flex flex-col gap-2">
                            <button
                                type="button"
                                @click="submitUpload"
                                :disabled="!uploadForm.manual_book || uploadForm.processing"
                                class="px-5 py-3 bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-extrabold text-xs rounded-xl transition shadow-lg flex items-center justify-center gap-2"
                            >
                                <svg v-if="uploadForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>{{ uploadForm.processing ? 'Mengunggah...' : 'Upload Manual Book' }}</span>
                            </button>

                            <button
                                v-if="activePeriode?.manual_book_pdf"
                                type="button"
                                @click="showDeleteConfirm = true"
                                class="px-4 py-1.5 text-[11px] font-bold text-rose-300 hover:text-rose-100 hover:bg-rose-500/20 rounded-lg transition text-center"
                            >
                                Hapus File PDF
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Delete Confirmation Dialog -->
                <div v-if="showDeleteConfirm" class="mt-4 p-4 rounded-2xl bg-rose-950/80 border border-rose-500/40 text-rose-200 flex flex-col sm:flex-row items-center justify-between gap-3 animate-fadeIn">
                    <div class="flex items-center gap-2 text-xs font-semibold">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Yakin ingin menghapus berkas Manual Book PDF dari sistem? Wisudawan tidak akan dapat mengunduh PDF sampai file baru diunggah.</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            @click="deleteManualBook"
                            :disabled="uploadForm.processing"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition"
                        >
                            Ya, Hapus
                        </button>
                        <button
                            type="button"
                            @click="showDeleteConfirm = false"
                            class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white font-medium text-xs rounded-xl transition"
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
