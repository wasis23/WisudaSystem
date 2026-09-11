<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    periodes: Array,
    selectedPeriodeId: Number,
    currentPeriode: Object,
    programStudis: Array,
    wisudawans: [Object, Array],
    stats: Object,
    filters: Object,
});

const selectedPeriode = ref(props.selectedPeriodeId || '');
const selectedProdi = ref(props.filters?.program_studi_id || '');
const selectedStatusFoto = ref(props.filters?.status_foto || '');
const searchInput = ref(props.filters?.search || '');

const footerImageInputRef = ref(null);
const isUploadingImage = ref(false);
const uploadError = ref(null);

const defaultFotoInputRef = ref(null);
const isUploadingDefaultFoto = ref(false);
const uploadDefaultFotoError = ref(null);

// Modal Edit Foto Wisudawan
const editingWisudawan = ref(null);
const wisudawanFotoFile = ref(null);
const wisudawanFotoPreview = ref(null);
const isUploadingWisudawanFoto = ref(false);
const uploadWisudawanFotoError = ref(null);
const wisudawanFotoInputRef = ref(null);

const openEditFotoModal = (w) => {
    editingWisudawan.value = w;
    wisudawanFotoFile.value = null;
    wisudawanFotoPreview.value = null;
    uploadWisudawanFotoError.value = null;
};

const closeEditFotoModal = (force = false) => {
    if (isUploadingWisudawanFoto.value && !force) return;
    editingWisudawan.value = null;
    wisudawanFotoFile.value = null;
    if (wisudawanFotoPreview.value) {
        URL.revokeObjectURL(wisudawanFotoPreview.value);
    }
    wisudawanFotoPreview.value = null;
    uploadWisudawanFotoError.value = null;
    if (wisudawanFotoInputRef.value) {
        wisudawanFotoInputRef.value.value = '';
    }
};

const onWisudawanFotoChange = (e) => {
    uploadWisudawanFotoError.value = null;
    const file = e.target.files[0];
    if (!file) return;

    // Validasi format
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    const fileName = file.name.toLowerCase();
    const isAllowedExt = fileName.endsWith('.jpg') || fileName.endsWith('.jpeg') || fileName.endsWith('.png') || fileName.endsWith('.webp');

    if (!allowedTypes.includes(file.type) && !isAllowedExt) {
        uploadWisudawanFotoError.value = 'Format file tidak didukung! Harap unggah foto dengan format JPG, JPEG, PNG, atau WEBP.';
        e.target.value = '';
        return;
    }

    // Validasi ukuran (maks 3 MB)
    const maxSizeBytes = 3 * 1024 * 1024;
    if (file.size > maxSizeBytes) {
        const actualSizeMb = (file.size / (1024 * 1024)).toFixed(2);
        uploadWisudawanFotoError.value = `Ukuran file melebihi 3MB! Berkas berukuran ${actualSizeMb} MB. Harap gunakan file maksimal 3 MB.`;
        e.target.value = '';
        return;
    }

    wisudawanFotoFile.value = file;
    wisudawanFotoPreview.value = URL.createObjectURL(file);
};

const submitWisudawanFoto = () => {
    if (!editingWisudawan.value || !wisudawanFotoFile.value) return;
    
    isUploadingWisudawanFoto.value = true;
    uploadWisudawanFotoError.value = null;

    const formData = new FormData();
    formData.append('pas_foto', wisudawanFotoFile.value);

    router.post(route('admin.buku-kenangan.wisudawan.foto.update', editingWisudawan.value.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            isUploadingWisudawanFoto.value = false;
            closeEditFotoModal(true);
        },
        onError: (err) => {
            console.error('Update foto wisudawan error:', err);
            uploadWisudawanFotoError.value = err.pas_foto || 'Gagal mengunggah foto. Pastikan format gambar sesuai dan ukuran maksimal 3MB.';
            isUploadingWisudawanFoto.value = false;
        },
        onFinish: () => {
            isUploadingWisudawanFoto.value = false;
        },
    });
};

const deleteWisudawanFoto = (w) => {
    if (!confirm(`Hapus foto wisudawan ${w.nama_lengkap} (${w.nim}) dan kembalikan ke siluet standar?`)) return;
    
    router.delete(route('admin.buku-kenangan.wisudawan.foto.destroy', w.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingWisudawan.value?.id === w.id) {
                closeEditFotoModal();
            }
        },
    });
};

const currentFooterImage = computed(() => {
    const cur = props.periodes?.find(p => p.id === Number(selectedPeriode.value));
    const img = cur?.buku_kenangan_footer_image || props.currentPeriode?.buku_kenangan_footer_image || null;
    return (img && img !== '0') ? img : null;
});

const currentDefaultFoto = computed(() => {
    const cur = props.periodes?.find(p => p.id === Number(selectedPeriode.value));
    const img = cur?.buku_kenangan_default_foto || props.currentPeriode?.buku_kenangan_default_foto || null;
    return (img && img !== '0') ? img : null;
});

const onImageFileChange = (e) => {
    const file = e.target.files[0];
    if (!file || !selectedPeriode.value) return;

    uploadError.value = null;
    isUploadingImage.value = true;

    const form = useForm({
        periode_id: selectedPeriode.value,
        footer_image: file,
    });

    form.post(route('admin.buku-kenangan.footer-image.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            if (footerImageInputRef.value) footerImageInputRef.value.value = '';
        },
        onError: (err) => {
            console.error('Footer image upload error:', err);
            uploadError.value = err.footer_image || err.periode_id || 'Gagal mengunggah gambar. Pastikan format dan ukuran sesuai.';
        },
        onFinish: () => {
            isUploadingImage.value = false;
        },
    });
};

const triggerFileInput = () => {
    footerImageInputRef.value?.click();
};

const deleteFooterImage = () => {
    if (!selectedPeriode.value) return;
    if (confirm('Apakah Anda yakin ingin menghapus gambar footer ini?')) {
        router.delete(route('admin.buku-kenangan.footer-image.destroy'), {
            data: { periode_id: selectedPeriode.value },
            preserveScroll: true,
        });
    }
};

const onDefaultFotoFileChange = (e) => {
    const file = e.target.files[0];
    if (!file || !selectedPeriode.value) return;

    uploadDefaultFotoError.value = null;
    isUploadingDefaultFoto.value = true;

    const form = useForm({
        periode_id: selectedPeriode.value,
        default_foto: file,
    });

    form.post(route('admin.buku-kenangan.default-foto.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            if (defaultFotoInputRef.value) defaultFotoInputRef.value.value = '';
        },
        onError: (err) => {
            console.error('Default foto upload error:', err);
            uploadDefaultFotoError.value = err.default_foto || err.periode_id || 'Gagal mengunggah aset siluet foto. Pastikan format dan ukuran sesuai.';
        },
        onFinish: () => {
            isUploadingDefaultFoto.value = false;
        },
    });
};

const triggerDefaultFotoInput = () => {
    defaultFotoInputRef.value?.click();
};

const deleteDefaultFoto = () => {
    if (!selectedPeriode.value) return;
    if (confirm('Apakah Anda yakin ingin mereset aset siluet default ke gambar standar sistem?')) {
        router.delete(route('admin.buku-kenangan.default-foto.destroy'), {
            data: { periode_id: selectedPeriode.value },
            preserveScroll: true,
        });
    }
};

const wisudawanList = computed(() => {
    return Array.isArray(props.wisudawans) ? props.wisudawans : (props.wisudawans?.data || []);
});

const filterYearbook = () => {
    router.get(route('admin.buku-kenangan.index'), {
        periode_id: selectedPeriode.value,
        program_studi_id: selectedProdi.value,
        status_foto: selectedStatusFoto.value,
        search: searchInput.value,
    }, { 
        preserveState: true, 
        preserveScroll: true,
        replace: true 
    });
};

let searchTimeout = null;
watch(searchInput, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filterYearbook();
    }, 350);
});

const goToPage = (page) => {
    if (!page || page < 1 || page > (props.wisudawans?.last_page || 1)) return;
    router.get(route('admin.buku-kenangan.index'), {
        periode_id: selectedPeriode.value,
        program_studi_id: selectedProdi.value,
        status_foto: selectedStatusFoto.value,
        search: searchInput.value,
        page: page,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const onPageClick = (link) => {
    if (!link || !link.url || link.active) return;
    const pageNum = parseInt(link.label);
    if (!isNaN(pageNum)) {
        goToPage(pageNum);
        return;
    }
    try {
        const url = new URL(link.url, window.location.origin);
        const p = url.searchParams.get('page');
        if (p) {
            goToPage(parseInt(p));
            return;
        }
    } catch (e) {
        // fallback
    }
    router.visit(link.url, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const downloadPdf = () => {
    const params = new URLSearchParams({
        periode_id: selectedPeriode.value,
    });
    if (selectedProdi.value) {
        params.append('program_studi_id', selectedProdi.value);
    }
    const url = route('admin.buku-kenangan.export') + '?' + params.toString();
    window.open(url, '_blank');
};

const downloadTanpaFotoCsv = () => {
    const params = new URLSearchParams({
        periode_id: selectedPeriode.value,
    });
    if (selectedProdi.value) {
        params.append('program_studi_id', selectedProdi.value);
    }
    const url = route('admin.buku-kenangan.export-tanpa-foto') + '?' + params.toString();
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Daftar Wisudawan & Buku Kenangan" />

    <AdminLayout>
        <div class="space-y-6 max-w-[1600px] mx-auto pb-12">
            
            <!-- 1. Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700/80 p-6 sm:p-7">
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-5">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-2xl border border-indigo-100 dark:border-indigo-900/50">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </span>
                            <div>
                                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                                    Daftar Wisudawan & Buku Kenangan
                                </h1>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                    Kelola data calon wisudawan, filter pas foto, konfigurasi banner footer, dan ekspor dokumen resmi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Toolbar -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <!-- Import Button -->
                        <Link
                            :href="route('admin.sync-simanta.import.preview')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] text-white font-bold text-xs rounded-xl transition shadow-sm hover:shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Pilih & Import Data</span>
                        </Link>

                        <!-- Export Belum Foto (CSV) -->
                        <button
                            @click="downloadTanpaFotoCsv"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-700/80 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold text-xs rounded-xl border border-amber-200 dark:border-amber-800/80 transition shadow-sm hover:shadow-md active:scale-[0.98]"
                            title="Export Data Mahasiswa yang Belum Upload Pas Foto ke Excel/CSV"
                        >
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Export Belum Foto (CSV)</span>
                        </button>

                        <!-- Export PDF Buku Kenangan -->
                        <button
                            @click="downloadPdf"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-bold text-xs rounded-xl transition shadow-sm hover:shadow-md"
                            title="Export Dokumen Resmi PDF Buku Kenangan"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>Export PDF Buku Kenangan</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Wisudawan -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Calon Wisudawan</span>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">
                            {{ stats?.total_wisudawan || 0 }} <span class="text-xs font-semibold text-gray-400">Orang</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xl border border-indigo-100 dark:border-indigo-900/40">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Sudah Upload Foto -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Sudah Upload Foto</span>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                            {{ stats?.total_ada_foto || 0 }} <span class="text-xs font-semibold text-emerald-600/70">Orang</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl border border-emerald-100 dark:border-emerald-900/40">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Belum Upload Foto -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Belum Upload Foto</span>
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                            {{ stats?.total_tanpa_foto || 0 }} <span class="text-xs font-semibold text-amber-600/70">Orang</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xl border border-amber-100 dark:border-amber-900/40">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4: Status Footer & Aset -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div class="space-y-1 min-w-0 flex-1 pr-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Banner & Siluet</span>
                        <div class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">
                            {{ currentFooterImage ? 'Banner Aktif' : 'Tanpa Banner' }}
                        </div>
                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold block truncate">
                            {{ currentDefaultFoto ? 'Siluet Kustom' : 'Siluet Toga Sistem' }}
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xl border border-blue-100 dark:border-blue-900/40 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 3. Panel Pengaturan Footer & Aset Dokumen -->
            <div class="bg-white dark:bg-gray-800 p-6 sm:p-7 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-sm space-y-5">
                <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Pengaturan Tampilan & Aset Dokumen Buku Kenangan</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Kelola gambar banner footer dan aset siluet foto pengganti jika wisudawan belum mengunggah pas foto.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Panel Gambar Footer Dokumen -->
                    <div class="space-y-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Banner Footer Dokumen PDF</span>
                                </span>
                                <span v-if="currentFooterImage" class="text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Aktif Terpasang</span>
                                </span>
                                <span v-else class="text-[10px] bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 font-medium px-2.5 py-1 rounded-full">
                                    Tanpa Footer
                                </span>
                            </div>

                            <!-- Error Alert if any -->
                            <div v-if="uploadError" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ uploadError }}</span>
                            </div>

                            <!-- Preview Active Footer Image -->
                            <div class="relative rounded-xl border border-dashed border-gray-300 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-950 p-2 min-h-[90px] flex items-center justify-center">
                                <img
                                    v-if="currentFooterImage"
                                    :src="`/storage/${currentFooterImage}`"
                                    alt="Footer Banner"
                                    class="max-h-24 w-auto object-contain mx-auto rounded-lg shadow-xs"
                                />
                                <div v-else class="text-center py-4 text-gray-400 space-y-1">
                                    <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs">Belum ada banner kustom terpasang</span>
                                </div>
                            </div>

                            <input
                                ref="footerImageInputRef"
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                @change="onImageFileChange"
                                class="hidden"
                            />
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700/60 space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    :disabled="isUploadingImage"
                                    class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow-sm"
                                >
                                    <svg v-if="!isUploadingImage" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>{{ isUploadingImage ? 'Mengunggah...' : (currentFooterImage ? 'Ganti Banner...' : 'Upload Banner...') }}</span>
                                </button>

                                <button
                                    v-if="currentFooterImage && !isUploadingImage"
                                    type="button"
                                    @click="deleteFooterImage"
                                    class="px-3 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 font-bold text-xs rounded-xl transition flex items-center gap-1"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>
                            <p class="text-[10.5px] text-gray-500 dark:text-gray-400">
                                Disarankan banner landscape memanjang (*proporsional auto-height*).
                            </p>
                        </div>
                    </div>

                    <!-- Panel Default Foto / Siluet Wisudawan -->
                    <div class="space-y-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    </svg>
                                    <span>Aset Siluet Foto Wisudawan (Default)</span>
                                </span>
                                <span v-if="currentDefaultFoto" class="text-[10px] bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 font-bold px-2.5 py-1 rounded-full border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Kustom Aktif</span>
                                </span>
                                <span v-else class="text-[10px] bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-medium px-2.5 py-1 rounded-full">
                                    Standar Sistem (Siluet Toga)
                                </span>
                            </div>

                            <!-- Error Alert if any -->
                            <div v-if="uploadDefaultFotoError" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ uploadDefaultFotoError }}</span>
                            </div>

                            <!-- Preview Active Default Silhouette -->
                            <div class="relative rounded-xl border border-indigo-200 dark:border-indigo-800 overflow-hidden bg-white dark:bg-gray-950 p-3 shadow-inner flex items-center gap-4">
                                <div class="w-16 h-20 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shrink-0 shadow-sm">
                                    <img
                                        :src="currentDefaultFoto ? `/storage/${currentDefaultFoto}` : '/images/default_toga_silhouette.png'"
                                        alt="Siluet Wisudawan"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="text-[11px] font-bold text-gray-900 dark:text-white">
                                        {{ currentDefaultFoto ? 'Aset Siluet Kustom Terpasang' : 'Siluet Toga Standar Sistem' }}
                                    </div>
                                    <p class="text-[10.5px] text-gray-500 dark:text-gray-400 leading-snug">
                                        Foto ini otomatis digunakan pada PDF dan preview jika wisudawan belum mengunggah foto.
                                    </p>
                                </div>
                            </div>

                            <input
                                ref="defaultFotoInputRef"
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                @change="onDefaultFotoFileChange"
                                class="hidden"
                            />
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700/60 space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <button
                                    type="button"
                                    @click="triggerDefaultFotoInput"
                                    :disabled="isUploadingDefaultFoto"
                                    class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow-sm"
                                >
                                    <svg v-if="!isUploadingDefaultFoto" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>{{ isUploadingDefaultFoto ? 'Mengunggah...' : (currentDefaultFoto ? 'Ganti Aset Siluet...' : 'Upload Aset Siluet Kustom...') }}</span>
                                </button>

                                <button
                                    v-if="currentDefaultFoto && !isUploadingDefaultFoto"
                                    type="button"
                                    @click="deleteDefaultFoto"
                                    class="px-3 py-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 font-bold text-xs rounded-xl transition flex items-center gap-1"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Reset ke Standar Sistem</span>
                                </button>
                            </div>
                            <p class="text-[10.5px] text-gray-500 dark:text-gray-400">
                                Format: PNG, JPG, WebP. Disarankan rasio pas foto 3:4.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Filter & Search Toolbar -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase block mb-1.5">Periode Wisuda:</label>
                        <select v-model="selectedPeriode" @change="filterYearbook" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs font-semibold text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2">
                            <option v-for="p in periodes" :key="p.id" :value="p.id">{{ p.nama_periode }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase block mb-1.5">Program Studi:</label>
                        <select v-model="selectedProdi" @change="filterYearbook" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs font-semibold text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 truncate">
                            <option value="">Semua Program Studi</option>
                            <option v-for="ps in programStudis" :key="ps.id" :value="ps.id">{{ ps.nama_prodi }} ({{ ps.jenjang }})</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase block mb-1.5">Filter Status Foto:</label>
                        <select v-model="selectedStatusFoto" @change="filterYearbook" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs font-semibold text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2">
                            <option value="">Semua Foto ({{ stats?.total_wisudawan || 0 }})</option>
                            <option value="tanpa_foto">Belum Upload Foto ({{ stats?.total_tanpa_foto || 0 }})</option>
                            <option value="ada_foto">Sudah Upload Foto ({{ stats?.total_ada_foto || 0 }})</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase block mb-1.5">Cari Wisudawan:</label>
                        <div class="relative w-full">
                            <input
                                v-model="searchInput"
                                @keyup.enter="filterYearbook"
                                type="text"
                                placeholder="Cari Nama / NIM / Judul..."
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 pl-9 pr-3 py-2 text-xs text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Sub-bar showing active count results -->
                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs flex-wrap gap-2 text-gray-500">
                    <div>
                        Menampilkan hasil: <strong class="text-gray-900 dark:text-white">{{ wisudawans?.total ?? wisudawanList.length }}</strong> wisudawan
                        <span v-if="selectedStatusFoto === 'tanpa_foto'" class="ml-1.5 text-amber-600 font-bold">(Hanya yang Belum Upload Foto)</span>
                        <span v-else-if="selectedStatusFoto === 'ada_foto'" class="ml-1.5 text-emerald-600 font-bold">(Hanya yang Sudah Upload Foto)</span>
                    </div>
                    <div class="text-[11px] text-gray-400">
                        Gunakan tombol <strong>Export PDF Buku Kenangan</strong> atau <strong>Export Belum Foto</strong> di atas untuk mengunduh laporan.
                    </div>
                </div>
            </div>

            <!-- 5. Wisudawan Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="w in wisudawanList"
                    :key="w.id"
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-800 transition duration-150 flex flex-col justify-between space-y-4"
                >
                    <!-- Top Profile & Photo Row -->
                    <div class="flex items-start gap-4">
                        <!-- Photo container -->
                        <div class="w-[72px] h-[96px] rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shrink-0 shadow-inner relative group">
                            <img
                                v-if="w.pas_foto"
                                :src="`/storage/${w.pas_foto}`"
                                class="w-full h-full object-cover"
                                @error="$event.target.src = currentDefaultFoto ? `/storage/${currentDefaultFoto}` : '/images/default_toga_silhouette.png'"
                            />
                            <img
                                v-else
                                :src="currentDefaultFoto ? `/storage/${currentDefaultFoto}` : '/images/default_toga_silhouette.png'"
                                alt="Siluet Wisudawan"
                                class="w-full h-full object-cover"
                            />
                            <!-- Badge on photo -->
                            <div class="absolute bottom-0 inset-x-0 bg-black/60 backdrop-blur-xs text-[9px] text-center text-white py-0.5 font-bold flex items-center justify-center gap-0.5">
                                <svg v-if="w.pas_foto" class="w-2.5 h-2.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ w.pas_foto ? 'Foto' : 'Siluet' }}</span>
                            </div>
                        </div>

                        <!-- Basic details -->
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="font-mono text-xs font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded-lg border border-indigo-100 dark:border-indigo-900/40">
                                    {{ w.nim }}
                                </span>
                                <span :class="['text-[9px] font-bold px-2 py-0.5 rounded-full', w.pas_foto ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300']">
                                    {{ w.pas_foto ? 'Foto Ada' : 'Belum Foto' }}
                                </span>
                            </div>
                            
                            <h4 class="font-black text-gray-900 dark:text-white text-sm leading-snug truncate pt-0.5" :title="w.nama_lengkap">
                                {{ w.nama_lengkap }}{{ w.gelar ? `, ${w.gelar}` : '' }}
                            </h4>
                            
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold truncate">
                                {{ w.program_studi?.nama_prodi }}
                            </p>

                            <div class="pt-1 text-[11px] text-gray-600 dark:text-gray-300 space-y-0.5">
                                <div class="truncate"><span class="text-gray-400">TTL:</span> {{ w.ttl || '-' }}</div>
                                <div class="truncate"><span class="text-gray-400">Orang Tua:</span> {{ w.orang_tua || (w.nama_ayah ? `${w.nama_ayah} / ${w.nama_ibu}` : '-') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Details Table & Action -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700/70 text-xs space-y-3">
                        <table class="w-full text-xs border-collapse">
                            <tbody>
                                <tr>
                                    <td class="font-bold text-gray-400 dark:text-gray-400 w-16 align-top py-0.5">Alamat</td>
                                    <td class="w-2.5 align-top py-0.5 text-gray-400 text-center">:</td>
                                    <td class="text-gray-700 dark:text-gray-300 font-medium align-top py-0.5 pl-1 line-clamp-2" :title="w.alamat">{{ w.alamat || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="font-bold text-gray-400 dark:text-gray-400 w-16 align-top py-0.5">Pekerjaan</td>
                                    <td class="w-2.5 align-top py-0.5 text-gray-400 text-center">:</td>
                                    <td class="text-gray-700 dark:text-gray-300 font-medium align-top py-0.5 pl-1 line-clamp-2" :title="w.pekerjaan">{{ w.pekerjaan || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="font-bold text-gray-400 dark:text-gray-400 w-16 align-top py-0.5">Judul TA</td>
                                    <td class="w-2.5 align-top py-0.5 text-gray-400 text-center">:</td>
                                    <td class="text-gray-700 dark:text-gray-300 font-medium italic align-top py-0.5 pl-1 line-clamp-2" :title="w.judul_ta">"{{ w.judul_ta || '-' }}"</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Admin Photo Action Button -->
                        <div class="pt-2 border-t border-dashed border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                            <span class="text-[10px] text-gray-400 font-medium">Aksi Foto:</span>
                            <div class="flex items-center gap-1.5">
                                <a
                                    v-if="w.pas_foto"
                                    :href="`/storage/${w.pas_foto}`"
                                    :download="`PasFoto_${w.nim}_${w.nama_lengkap}.jpg`"
                                    target="_blank"
                                    class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold rounded-lg border border-emerald-200 dark:border-emerald-800/60 transition flex items-center gap-1"
                                    title="Download Berkas Pas Foto Wisudawan"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Download</span>
                                </a>
                                <button
                                    type="button"
                                    @click="openEditFotoModal(w)"
                                    class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold rounded-lg border border-indigo-200 dark:border-indigo-900/60 transition flex items-center gap-1.5 shadow-2xs hover:shadow-xs"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>{{ w.pas_foto ? 'Ganti' : 'Upload' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!wisudawanList || wisudawanList.length === 0" class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center space-y-3 border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Tidak Ada Data Wisudawan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Tidak ditemukan data wisudawan untuk periode atau kombinasi filter yang dipilih. Silakan ubah filter atau gunakan tombol "Pilih & Import Data".
                </p>
            </div>

            <!-- 6. Pagination Footer -->
            <div v-if="wisudawans?.last_page > 1" class="bg-white dark:bg-gray-800 rounded-3xl p-4 sm:p-5 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400">
                <div>
                    Menampilkan <strong>{{ wisudawans.from || 0 }}</strong> - <strong>{{ wisudawans.to || 0 }}</strong> dari <strong>{{ wisudawans.total || 0 }}</strong> wisudawan (Halaman <strong>{{ wisudawans.current_page }}</strong> dari {{ wisudawans.last_page }})
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <!-- Tombol Sebelumnya / Previous -->
                    <button
                        type="button"
                        @click="goToPage(wisudawans.current_page - 1)"
                        :disabled="wisudawans.current_page <= 1"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition inline-flex items-center justify-center select-none',
                            wisudawans.current_page <= 1
                                ? 'text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-50 bg-gray-50/50 dark:bg-gray-800/50'
                                : 'bg-gray-50 dark:bg-gray-700/60 text-gray-700 dark:text-gray-200 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-gray-600 cursor-pointer shadow-xs'
                        ]"
                    >
                        &laquo; Prev
                    </button>

                    <!-- Tombol Nomor Halaman -->
                    <template v-for="(link, idx) in wisudawans.links" :key="idx">
                        <button
                            v-if="!link.label.includes('Previous') && !link.label.includes('Sebelumnya') && !link.label.includes('Next') && !link.label.includes('Berikutnya') && !link.label.includes('&laquo;') && !link.label.includes('&raquo;')"
                            type="button"
                            @click="onPageClick(link)"
                            :disabled="!link.url || link.active"
                            :class="[
                                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition inline-flex items-center justify-center select-none min-w-[36px]',
                                link.active
                                    ? 'bg-indigo-600 text-white shadow-sm cursor-default'
                                    : link.url
                                        ? 'bg-gray-50 dark:bg-gray-700/60 text-gray-700 dark:text-gray-200 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-gray-600 cursor-pointer'
                                        : 'text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-50'
                            ]"
                            v-html="link.label"
                        />
                    </template>

                    <!-- Tombol Berikutnya / Next -->
                    <button
                        type="button"
                        @click="goToPage(wisudawans.current_page + 1)"
                        :disabled="wisudawans.current_page >= wisudawans.last_page"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition inline-flex items-center justify-center select-none',
                            wisudawans.current_page >= wisudawans.last_page
                                ? 'text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-50 bg-gray-50/50 dark:bg-gray-800/50'
                                : 'bg-gray-50 dark:bg-gray-700/60 text-gray-700 dark:text-gray-200 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-gray-600 cursor-pointer shadow-xs'
                        ]"
                    >
                        Next &raquo;
                    </button>
                </div>
            </div>

            <!-- 7. Modal Edit / Ganti Foto Wisudawan -->
            <div
                v-if="editingWisudawan"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
                @click.self="closeEditFotoModal"
            >
                <div class="bg-white dark:bg-gray-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-800 space-y-5">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-black text-gray-900 dark:text-white text-base">
                                    {{ editingWisudawan.pas_foto ? 'Ganti Pas Foto Wisudawan' : 'Upload Pas Foto Wisudawan' }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ editingWisudawan.nama_lengkap }} ({{ editingWisudawan.nim }})
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeEditFotoModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Error Notice -->
                    <div v-if="uploadWisudawanFotoError" class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ uploadWisudawanFotoError }}</span>
                    </div>

                    <!-- Photo Comparison / Preview Section -->
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-950/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
                        <!-- Current Photo -->
                        <div class="text-center space-y-2">
                            <span class="text-[11px] font-bold text-gray-400 block uppercase">Foto Saat Ini</span>
                            <div class="w-24 h-32 mx-auto rounded-xl overflow-hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-sm relative">
                                <img
                                    v-if="editingWisudawan.pas_foto"
                                    :src="`/storage/${editingWisudawan.pas_foto}`"
                                    class="w-full h-full object-cover"
                                    @error="$event.target.src = currentDefaultFoto ? `/storage/${currentDefaultFoto}` : '/images/default_toga_silhouette.png'"
                                />
                                <img
                                    v-else
                                    :src="currentDefaultFoto ? `/storage/${currentDefaultFoto}` : '/images/default_toga_silhouette.png'"
                                    alt="Siluet"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <span class="text-[10px] text-gray-500 font-medium block">
                                {{ editingWisudawan.pas_foto ? 'Foto Terpasang' : 'Belum Ada (Siluet)' }}
                            </span>
                        </div>

                        <!-- New Selected Photo Preview -->
                        <div class="text-center space-y-2">
                            <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 block uppercase">Foto Baru</span>
                            <div class="w-24 h-32 mx-auto rounded-xl overflow-hidden bg-white dark:bg-gray-900 border-2 border-dashed border-indigo-300 dark:border-indigo-700 shadow-sm flex items-center justify-center relative">
                                <img
                                    v-if="wisudawanFotoPreview"
                                    :src="wisudawanFotoPreview"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="text-center p-2 text-gray-400">
                                    <svg class="w-6 h-6 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[9px] block leading-tight">Pilih Berkas</span>
                                </div>
                            </div>
                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold block truncate">
                                {{ wisudawanFotoFile ? wisudawanFotoFile.name : 'Belum Dipilih' }}
                            </span>
                        </div>
                    </div>

                    <!-- File Picker -->
                    <div class="space-y-2">
                        <input
                            ref="wisudawanFotoInputRef"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            @change="onWisudawanFotoChange"
                            class="hidden"
                        />
                        <button
                            type="button"
                            @click="wisudawanFotoInputRef?.click()"
                            class="w-full py-2.5 px-4 rounded-xl border border-gray-300 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-700 dark:text-gray-200 transition flex items-center justify-center gap-2 shadow-xs hover:bg-gray-100 dark:hover:bg-gray-700/60"
                        >
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <span>{{ wisudawanFotoFile ? 'Pilih Berkas Lain' : 'Pilih File Foto dari Komputer' }}</span>
                        </button>
                        <p class="text-[10.5px] text-gray-400 text-center">
                            Format yang didukung: JPG, PNG, WebP (Maks. 3MB). Disarankan rasio pas foto resmi (3:4).
                        </p>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="closeEditFotoModal"
                            :disabled="isUploadingWisudawanFoto"
                            class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitWisudawanFoto"
                            :disabled="!wisudawanFotoFile || isUploadingWisudawanFoto"
                            class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition flex items-center gap-2 shadow-sm"
                        >
                            <svg v-if="isUploadingWisudawanFoto" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isUploadingWisudawanFoto ? 'Menyimpan...' : 'Simpan Foto Wisudawan' }}</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
