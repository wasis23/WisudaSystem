<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    wisudawan: Object,
    sikeuQuota: Object,
    stageConfig: Object,
    activePeriode: Object,
    manualBookUrl: String,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const wisudawanData = computed(() => props.wisudawan || user.value?.wisudawan);
const isTracerStudyFilled = computed(() => Boolean(wisudawanData.value?.is_tracer_study_filled));
const isBiodataFilled = computed(() => Boolean(wisudawanData.value?.is_biodata_filled));
const isLunas = computed(() => {
    return wisudawanData.value?.status_pembayaran_sikeu === 'lunas' || Boolean(props.sikeuQuota?.has_paid_wisuda);
});

const allGuests = computed(() => wisudawanData.value?.tamu_tambahan || []);

const showSuccessAlert = ref(true);
const showErrorAlert = ref(true);
const showWarningAlert = ref(true);
const showManualModal = ref(false);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const flashWarning = computed(() => page.props.flash?.warning);

watch(() => page.props.flash, () => {
    showSuccessAlert.value = true;
    showErrorAlert.value = true;
    showWarningAlert.value = true;
}, { deep: true });

const printTickets = () => {
    window.print();
};
</script>

<template>
    <Head title="Dashboard Wisudawan - Politeknik Indonusa Surakarta" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        Portal Mandiri Wisudawan
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Selamat datang di Wisuda Smart System, <span class="font-bold text-slate-900 dark:text-white">{{ user?.name }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <!-- Button to open Manual Book Modal or Download PDF -->
                    <button
                        type="button"
                        @click="showManualModal = true"
                        class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-bold border border-indigo-200 dark:border-indigo-700 transition flex items-center gap-1.5 shadow-sm"
                    >
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Panduan Manual Book</span>
                    </button>

                    <div v-if="wisudawanData?.qr_code_token && isBiodataFilled" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-mono text-xs font-bold border border-slate-200 dark:border-slate-700">
                        QR ID: {{ wisudawanData.qr_code_token }}
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- FLASH NOTIFICATION BANNERS -->
                <!-- 1. SUCCESS NOTIFICATION BANNER -->
                <div
                    v-if="flashSuccess && showSuccessAlert"
                    class="p-5 rounded-3xl bg-gradient-to-r from-emerald-500/15 via-teal-500/10 to-emerald-500/5 border-2 border-emerald-500/40 text-emerald-950 dark:text-emerald-200 shadow-lg relative overflow-hidden backdrop-blur-sm transition animate-fadeIn"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/30">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-400/40">
                                        PENGISIAN BERHASIL
                                    </span>
                                </div>
                                <h3 class="text-sm sm:text-base font-extrabold text-emerald-900 dark:text-emerald-100">
                                    Data Anda Berhasil Disimpan di Sistem!
                                </h3>
                                <p class="text-xs text-emerald-800 dark:text-emerald-300 leading-relaxed font-medium">
                                    {{ flashSuccess }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="showSuccessAlert = false"
                            class="text-emerald-700 hover:text-emerald-950 dark:text-emerald-300 dark:hover:text-white p-1 rounded-xl transition hover:bg-emerald-500/20 shrink-0"
                            title="Tutup Notifikasi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- 2. ERROR / UNSUCCESSFUL NOTIFICATION BANNER -->
                <div
                    v-if="flashError && showErrorAlert"
                    class="p-5 rounded-3xl bg-gradient-to-r from-rose-500/15 via-red-500/10 to-rose-500/5 border-2 border-rose-500/40 text-rose-950 dark:text-rose-200 shadow-lg relative overflow-hidden backdrop-blur-sm transition animate-fadeIn"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/30 animate-pulse">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-400/40">
                                        PENGISIAN TIDAK SUKSES / GAGAL
                                    </span>
                                </div>
                                <h3 class="text-sm sm:text-base font-extrabold text-rose-900 dark:text-rose-100">
                                    Terjadi Kendala Saat Menyimpan Data
                                </h3>
                                <div class="p-2.5 rounded-xl bg-rose-500/10 border border-rose-400/30 text-xs text-rose-900 dark:text-rose-200 leading-relaxed font-medium">
                                    <span class="font-bold">Keterangan Penyebab:</span> {{ flashError }}
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="showErrorAlert = false"
                            class="text-rose-700 hover:text-rose-950 dark:text-rose-300 dark:hover:text-white p-1 rounded-xl transition hover:bg-rose-500/20 shrink-0"
                            title="Tutup Notifikasi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- 3. WARNING NOTIFICATION BANNER -->
                <div
                    v-if="flashWarning && showWarningAlert"
                    class="p-5 rounded-3xl bg-gradient-to-r from-amber-500/15 via-yellow-500/10 to-amber-500/5 border-2 border-amber-500/40 text-amber-950 dark:text-amber-200 shadow-lg relative overflow-hidden backdrop-blur-sm transition"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/30">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-400/40">
                                    PERINGATAN
                                </span>
                                <p class="text-xs text-amber-900 dark:text-amber-200 leading-relaxed font-medium">
                                    {{ flashWarning }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="showWarningAlert = false"
                            class="text-amber-700 hover:text-amber-950 dark:text-amber-300 dark:hover:text-white p-1 rounded-xl transition hover:bg-amber-500/20 shrink-0"
                            title="Tutup Notifikasi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- 3-CARD SUMMARY / ACTION ROW (Manual Book, WhatsApp Group, SIKEU Status in 1 Row) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">

                    <!-- CARD 1: PANDUAN MANUAL BOOK -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-5 sm:p-6 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between space-y-4 transition hover:shadow-md">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <span
                                    v-if="activePeriode?.manual_book_pdf || manualBookUrl"
                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 rounded-full text-[10px] font-extrabold border border-emerald-200 dark:border-emerald-800 flex items-center gap-1"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    PDF Resmi
                                </span>
                                <span
                                    v-else
                                    class="px-2.5 py-1 bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400 rounded-full text-[10px] font-bold"
                                >
                                    Belum Ada File
                                </span>
                            </div>

                            <div>
                                <h4 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Buku Panduan Wisuda
                                </h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Petunjuk alur pengisian tracer study, kelengkapan biodata, pas foto, dan E-Ticket.
                                </p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <a
                                v-if="activePeriode?.manual_book_pdf || manualBookUrl"
                                :href="manualBookUrl || route('manual-book.download')"
                                target="_blank"
                                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition text-center flex items-center justify-center gap-2 shadow-sm"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Unduh Manual Book (PDF)</span>
                            </a>
                            <button
                                v-else
                                disabled
                                class="w-full py-2.5 px-4 bg-slate-100 dark:bg-slate-700/50 text-slate-400 text-xs font-semibold rounded-xl text-center cursor-not-allowed"
                            >
                                Berkas Belum Diunggah
                            </button>
                        </div>
                    </div>

                    <!-- CARD 2: WHATSAPP GROUP ANNOUNCEMENT -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-5 sm:p-6 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between space-y-4 transition hover:shadow-md">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-800">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                </div>
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 rounded-full text-[10px] font-extrabold border border-emerald-200 dark:border-emerald-800">
                                    Wajib Gabung
                                </span>
                            </div>

                            <div>
                                <h4 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Grup WhatsApp Wisuda
                                </h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Informasi mendesak, jadwal gladi bersih, pembagian toga, dan denah kursi wisudawan.
                                </p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <a
                                href="https://chat.whatsapp.com/Hc3ag9O2vBhLLqZT5TNXjx?s=cl&p=a&mlu=4"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition text-center flex items-center justify-center gap-2 shadow-sm"
                            >
                                <span>Gabung Grup WhatsApp →</span>
                            </a>
                        </div>
                    </div>

                    <!-- CARD 3: STATUS KEUANGAN SIKEU -->
                    <div
                        :class="[
                            'rounded-3xl p-5 sm:p-6 border shadow-sm flex flex-col justify-between space-y-4 transition hover:shadow-md',
                            isLunas
                                ? 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700'
                                : 'bg-rose-50/40 dark:bg-rose-950/20 border-rose-200 dark:border-rose-900/60'
                        ]"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div
                                    :class="[
                                        'w-10 h-10 rounded-2xl flex items-center justify-center border',
                                        isLunas
                                            ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800'
                                            : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800'
                                    ]"
                                >
                                    <svg v-if="isLunas" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>

                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-[10px] font-extrabold border uppercase tracking-wider',
                                        isLunas
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300 border-rose-300 dark:border-rose-800'
                                    ]"
                                >
                                    {{ isLunas ? 'SIKEU Lunas' : 'Belum Lunas' }}
                                </span>
                            </div>

                            <div>
                                <h4 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Status Tagihan (SIKEU)
                                </h4>
                                <p v-if="isLunas" class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Kuota: <strong class="text-slate-700 dark:text-slate-200">{{ sikeuQuota?.total_allowed_guests || 2 }} Undangan</strong> & <strong class="text-slate-700 dark:text-slate-200">{{ sikeuQuota?.snack_quota || 3 }} Snack</strong>. E-Ticket aktif.
                                </p>
                                <p v-else class="text-xs text-rose-600 dark:text-rose-400 mt-1 leading-relaxed">
                                    Harap lunasi biaya wisuda di Keuangan SIKEU agar E-Ticket & pemanggilan aktif.
                                </p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <Link
                                :href="route('wisudawan.tamu.form')"
                                class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold text-xs rounded-xl transition text-center flex items-center justify-center gap-1.5 shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                                <span>Kelola Tamu ({{ allGuests.length }}/{{ sikeuQuota?.total_allowed_guests || 2 }}) →</span>
                            </Link>
                        </div>
                    </div>

                </div>

                <!-- Main Action Checklist Grid (2-Column Grid) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Menu 1 (Kiri): Data Tracer Study -->
                    <div class="bg-white dark:bg-slate-800 p-6 sm:p-8 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between relative overflow-hidden">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-lg border border-blue-200 dark:border-blue-700">
                                    1
                                </div>
                                <span :class="['text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5', isTracerStudyFilled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300']">
                                    <svg v-if="isTracerStudyFilled" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>{{ isTracerStudyFilled ? 'Selesai & Tersimpan' : 'Wajib Diisi' }}</span>
                                </span>
                            </div>

                            <h4 class="font-black text-slate-900 dark:text-white text-lg">
                                Data Tracer Study Alumni
                            </h4>
                            
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                                Wajib mengisi kuesioner pelacakan karir alumni untuk evaluasi lulusan & akreditasi institusi sebelum mengakses biodata wisuda.
                            </p>

                            <!-- Status Explanation Note -->
                            <div class="mt-3.5 p-3 rounded-2xl text-[11px] leading-relaxed border" :class="isTracerStudyFilled ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60' : 'bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'">
                                <template v-if="isTracerStudyFilled">
                                    <strong>Status Sukses:</strong> Data Tracer Study Anda telah berhasil disimpan dan terverifikasi di pangkalan data alumni institusi.
                                </template>
                                <template v-else>
                                    <strong>Keterangan:</strong> Kuesioner belum diisi. Pengisian data tracer study adalah syarat wajib untuk membuka pendaftaran biodata wisuda.
                                </template>
                            </div>
                        </div>

                        <div class="pt-6 mt-4 border-t border-slate-100 dark:border-slate-700/60">
                            <Link
                                :href="route('wisudawan.tracer.form')"
                                class="w-full py-3 px-4 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-xl transition text-center block shadow-sm"
                            >
                                {{ isTracerStudyFilled ? 'Edit Data Tracer Study →' : 'Isi Tracer Study Sekarang →' }}
                            </Link>
                        </div>
                    </div>

                    <!-- Menu 2 (Kanan): Biodata & Live Preview Layar Wisuda (Locked if Tracer Study is not filled) -->
                    <div :class="[
                        'p-6 sm:p-8 rounded-3xl border shadow-sm flex flex-col justify-between transition relative overflow-hidden',
                        isTracerStudyFilled
                            ? 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700'
                            : 'bg-slate-50 dark:bg-slate-900/70 border-slate-300 dark:border-slate-800 opacity-90'
                    ]">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div :class="[
                                    'w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg border',
                                    isTracerStudyFilled
                                        ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700'
                                        : 'bg-slate-200 dark:bg-slate-800 text-slate-500 border-slate-300'
                                ]">
                                    2
                                </div>
                                
                                <span v-if="!isTracerStudyFilled" class="text-xs font-bold px-3 py-1 rounded-full bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>Terkunci</span>
                                </span>
                                <span v-else-if="wisudawanData?.is_biodata_filled" class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Terisi & Aktif</span>
                                </span>
                                <span v-else class="text-xs font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>Belum Diisi</span>
                                </span>
                            </div>

                            <h4 class="font-black text-slate-900 dark:text-white text-lg">
                                Biodata & Live Preview Layar Wisuda
                            </h4>
                            
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                                Lengkapi NIM, Judul TA, Dosen Pembimbing & Penguji, Nama Orang Tua, Pas Foto & Lihat Live Preview Layar Wisuda Anda.
                            </p>

                            <!-- Status Explanation Note -->
                            <div class="mt-3.5 p-3 rounded-2xl text-[11px] leading-relaxed border" :class="!isTracerStudyFilled ? 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700' : (wisudawanData?.is_biodata_filled ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60' : 'bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/60')">
                                <template v-if="!isTracerStudyFilled">
                                    <strong>Keterangan Terkunci:</strong> Menu biodata belum dapat diakses karena Anda belum menyelesaikan pengisian Data Tracer Study (Menu 1).
                                </template>
                                <template v-else-if="wisudawanData?.is_biodata_filled">
                                    <strong>Status Sukses:</strong> Biodata, pas foto, dan data pendamping telah tersimpan. Layar live preview panggung & Barcode E-Ticket telah aktif.
                                </template>
                                <template v-else>
                                    <strong>Keterangan:</strong> Biodata belum lengkap. Segera isi data diri, judul tugas akhir, dan unggah pas foto wisuda resmi.
                                </template>
                            </div>
                        </div>

                        <div class="pt-6 mt-4 border-t border-slate-100 dark:border-slate-700/60 space-y-2">
                            <template v-if="isTracerStudyFilled">
                                <Link
                                    :href="route('wisudawan.pendaftaran.form')"
                                    class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition text-center block shadow-sm"
                                >
                                    {{ wisudawanData?.is_biodata_filled ? 'Edit Biodata & Live Preview →' : 'Isi Biodata Sekarang →' }}
                                </Link>
                            </template>
                            <template v-else>
                                <button
                                    disabled
                                    class="w-full py-3 px-4 bg-slate-300 dark:bg-slate-800 text-slate-500 dark:text-slate-500 font-bold text-xs rounded-xl cursor-not-allowed text-center block border border-slate-300 dark:border-slate-700"
                                >
                                    Akses Terkunci (Isi Tracer Study)
                                </button>
                                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold text-center leading-tight">
                                    Harap selesaikan pengisian Data Tracer Study terlebih dahulu untuk membuka menu ini.
                                </p>
                            </template>
                        </div>
                    </div>

                </div>

                <!-- DIGITAL BARCODE & E-TICKET SECTION -->
                <div v-if="isBiodataFilled" class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-700">
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider inline-flex items-center gap-1.5',
                                        isLunas
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                    ]"
                                >
                                    <svg v-if="isLunas" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-3 h-3 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>{{ isLunas ? 'PEMBAYARAN LUNAS & TIKET AKTIF' : 'MENUNGGU PELUNASAN SIKEU' }}</span>
                                </span>
                                <span class="text-xs text-slate-400">Total {{ 1 + allGuests.length }} Barcode Digital (1 Mahasiswa + {{ allGuests.length }} Undangan)</span>
                            </div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white mt-1 flex items-center gap-2">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                                <span>E-Ticket & Barcode Presensi Wisuda</span>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Barcode ini berlaku untuk <strong>2x Scan Presensi</strong>: 1. Oleh Security di Halaman Depan & 2. Oleh Staf Presensi di Pintu Masuk Venue Auditorium.
                            </p>
                        </div>

                        <a
                            v-if="isLunas"
                            :href="route('wisudawan.tiket.export-pdf')"
                            target="_blank"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition flex items-center gap-2 shrink-0 shadow-sm hover:scale-105 transform duration-150"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Unduh E-Ticket Resmi (PDF)</span>
                        </a>
                    </div>

                    <!-- WARNING IF UNPAID -->
                    <div v-if="!isLunas" class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl p-4 text-xs text-rose-700 dark:text-rose-300 flex items-center gap-3">
                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="font-bold">Barcode Presensi Terkunci Sementara</p>
                            <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">Petugas scanner di gerbang dan pintu ballroom akan menolak presensi jika status pembayaran belum lunas. Silakan lakukan pembayaran ke Keuangan SIKEU untuk mengaktifkan tiket.</p>
                        </div>
                    </div>

                    <!-- DYNAMIC BARCODES GRID -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="ticket-print-area">
                        
                        <!-- CARD 1: BARCODE MAHASISWA -->
                        <div class="bg-gradient-to-b from-indigo-50/50 to-white dark:from-slate-900/50 dark:to-slate-800 rounded-2xl border-2 border-indigo-200 dark:border-indigo-800 p-5 flex flex-col items-center text-center space-y-4 shadow-sm relative overflow-hidden">
                            <div class="w-full bg-indigo-600 text-white py-1.5 px-3 rounded-xl font-extrabold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                </svg>
                                <span>Mahasiswa Wisudawan</span>
                            </div>

                            <div class="bg-white p-3 rounded-2xl border border-indigo-100 shadow-inner">
                                <QrcodeVue
                                    :value="wisudawanData?.qr_code_token || ('WSD-' + (wisudawanData?.nim || 'STUDENT'))"
                                    :size="150"
                                    level="H"
                                    render-as="svg"
                                />
                            </div>

                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">
                                    {{ wisudawanData?.nama_lengkap }}
                                </h4>
                                <p class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">
                                    NIM: {{ wisudawanData?.nim }}
                                </p>
                                <span class="text-[10px] text-slate-400 block mt-1 font-mono bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">
                                    ID: {{ wisudawanData?.qr_code_token }}
                                </span>
                            </div>

                            <!-- SCAN STATUS TIMELINE -->
                            <div class="w-full pt-3 border-t border-slate-100 dark:border-slate-700 space-y-1.5 text-left text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">1. Security (Gate):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', wisudawanData?.is_hadir ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ wisudawanData?.is_hadir ? 'Scanned' : 'Belum Scan' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">2. Staf Venue (Auditorium):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', wisudawanData?.is_in_auditorium ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ wisudawanData?.is_in_auditorium ? 'Scanned' : 'Belum Scan' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- DYNAMIC GUEST CARDS (2 STANDARD + N EXTRA) -->
                        <div
                            v-for="(guest, index) in allGuests"
                            :key="guest.id || index"
                            :class="[
                                'rounded-2xl border-2 p-5 flex flex-col items-center text-center space-y-4 shadow-sm relative overflow-hidden',
                                index === 0
                                    ? 'bg-gradient-to-b from-blue-50/50 to-white dark:from-slate-900/50 dark:to-slate-800 border-blue-200 dark:border-blue-800'
                                    : (index === 1
                                        ? 'bg-gradient-to-b from-purple-50/50 to-white dark:from-slate-900/50 dark:to-slate-800 border-purple-200 dark:border-purple-800'
                                        : 'bg-gradient-to-b from-teal-50/50 to-white dark:from-slate-900/50 dark:to-slate-800 border-teal-200 dark:border-teal-800')
                            ]"
                        >
                            <div
                                :class="[
                                    'w-full text-white py-1.5 px-3 rounded-xl font-extrabold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5',
                                    index === 0
                                        ? 'bg-blue-600'
                                        : (index === 1 ? 'bg-purple-600' : 'bg-teal-600')
                                ]"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Pendamping #{{ index + 1 }} {{ index >= 2 ? '(Ekstra SIKEU)' : '' }}</span>
                            </div>

                            <div class="bg-white p-3 rounded-2xl border border-slate-100 shadow-inner">
                                <QrcodeVue
                                    :value="guest.qr_guest_token || ('GST-' + (index + 1) + '-' + wisudawanData?.nim)"
                                    :size="150"
                                    level="H"
                                    render-as="svg"
                                />
                            </div>

                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">
                                    {{ guest.nama_tamu || `Pendamping ${index + 1}` }}
                                </h4>
                                <p
                                    :class="[
                                        'text-xs font-semibold mt-0.5',
                                        index === 0
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : (index === 1 ? 'text-purple-600 dark:text-purple-400' : 'text-teal-600 dark:text-teal-400')
                                    ]"
                                >
                                    {{ guest.hubungan || 'Tamu Undangan' }}
                                </p>
                                <span class="text-[10px] text-slate-400 block mt-1 font-mono bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">
                                    ID: {{ guest.qr_guest_token || ('GST-' + (index + 1) + '-' + wisudawanData?.nim) }}
                                </span>
                            </div>

                            <!-- SCAN STATUS TIMELINE -->
                            <div class="w-full pt-3 border-t border-slate-100 dark:border-slate-700 space-y-1.5 text-left text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">1. Security (Gate):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', (guest.is_hadir_gate || guest.is_hadir) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ (guest.is_hadir_gate || guest.is_hadir) ? 'Scanned' : 'Belum Scan' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">2. Staf Venue (Auditorium):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', guest.is_hadir_venue ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ guest.is_hadir_venue ? 'Scanned & Snack' : 'Belum Scan' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ALERT IF BIODATA IS NOT FILLED -->
                <div v-else class="bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-300 dark:border-amber-700/60 rounded-3xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">ℹ️</span>
                        <div>
                            <h4 class="font-extrabold text-slate-900 dark:text-white text-sm">
                                Barcode Presensi Digital Belum Tersedia
                            </h4>
                            <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                                Harap selesaikan pengisian <strong>Biodata & Live Preview Layar Wisuda</strong> terlebih dahulu untuk memunculkan Barcode Presensi Wisudawan & 2 Pendamping.
                            </p>
                        </div>
                    </div>
                    <Link
                        v-if="isTracerStudyFilled"
                        :href="route('wisudawan.pendaftaran.form')"
                        class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl transition shrink-0"
                    >
                        Isi Biodata Sekarang →
                    </Link>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- INTERACTIVE MANUAL BOOK MODAL -->
        <!-- ========================================================================= -->
        <div
            v-if="showManualModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
            @click.self="showManualModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden animate-fadeIn">
                
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-500/30 border border-indigo-400/40 flex items-center justify-center text-indigo-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white">
                                Panduan Lengkap Wisudawan (Manual Book)
                            </h3>
                            <p class="text-xs text-indigo-200">
                                Tata cara alur website & petunjuk pelaksanaan Wisuda Politeknik Indonusa Surakarta
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            v-if="activePeriode?.manual_book_pdf || manualBookUrl"
                            :href="manualBookUrl || route('manual-book.download')"
                            target="_blank"
                            class="px-3.5 py-1.5 bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow-sm"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Unduh PDF</span>
                        </a>

                        <button
                            type="button"
                            @click="showManualModal = false"
                            class="text-slate-300 hover:text-white p-2 rounded-xl hover:bg-white/10 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 sm:p-8 overflow-y-auto space-y-6 text-slate-800 dark:text-slate-200">
                    
                    <!-- Steps Timeline -->
                    <div class="space-y-6">
                        
                        <!-- Step 1 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-9 h-9 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-black text-sm flex items-center justify-center shrink-0 border border-indigo-200 dark:border-indigo-700">
                                1
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Login & Akses Portal Wisuda
                                </h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Akses <strong>wisuda.poltekindonusa.ac.id</strong> menggunakan <strong>NIM</strong> dan <strong>Password SIAKAD</strong> Anda. Sistem terhubung langsung ke SIAKAD dan memvalidasi keikutsertaan Anda pada periode wisuda aktif.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-9 h-9 rounded-2xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-black text-sm flex items-center justify-center shrink-0 border border-emerald-200 dark:border-emerald-700">
                                2
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Wajib Bergabung Grup WhatsApp Resmi Wisuda
                                </h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Klik tombol <strong>"Gabung Grup WhatsApp Wisuda"</strong> di dashboard. Segala informasi mendesak seperti jadwal gladi bersih, pembagian toga, dan denah tempat duduk auditorium akan dikoordinasikan di grup ini.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-9 h-9 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-black text-sm flex items-center justify-center shrink-0 border border-blue-200 dark:border-blue-700">
                                3
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Isi Kuesioner Tracer Study Alumni (Tahap 1)
                                </h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Isi survei pelacakan karir lulusan (status pekerjaan/wirausaha/studi lanjut, evaluasi kompetensi, dan saran). <em>Pengisian Tracer Study adalah syarat mutlak untuk membuka formulir biodata wisuda.</em>
                                </p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-9 h-9 rounded-2xl bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-black text-sm flex items-center justify-center shrink-0 border border-purple-200 dark:border-purple-700">
                                4
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Lengkapi Biodata, Upload Pas Foto & Cek Live Preview Layar
                                </h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Lengkapi data Judul Tugas Akhir, Dosen Pembimbing, Dosen Penguji, dan Nama Orang Tua. Unggah pas foto resmi wisuda dengan alat bantu crop proporsional. Anda dapat langsung melihat simulasi tampilan visual Anda di proyektor panggung pada fitur <strong>Live Preview</strong>.
                                </p>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-9 h-9 rounded-2xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 font-black text-sm flex items-center justify-center shrink-0 border border-amber-200 dark:border-amber-700">
                                5
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Kelola Nama Pendamping (Tamu Undangan) & Snack
                                </h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Buka menu <strong>"Kelola Tamu & Snack"</strong> untuk memasukkan nama orang tua/wali atau tamu pendamping tambahan (sesuai kuota SIKEU) untuk pencetakan ID card dan pembagian konsumsi snack.
                                </p>
                            </div>
                        </div>

                        <!-- Step 6 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-9 h-9 rounded-2xl bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 font-black text-sm flex items-center justify-center shrink-0 border border-teal-200 dark:border-teal-700">
                                6
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Unduh E-Ticket & Barcode Presensi Digital
                                </h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Setelah pembayaran berstatus <strong>Lunas</strong> di SIKEU dan biodata tersimpan, unduh <strong>E-Ticket PDF</strong>. Setiap tiket memuat QR Code unik untuk wisudawan dan seluruh pendamping.
                                </p>
                            </div>
                        </div>

                        <!-- Step 7 -->
                        <div class="flex gap-4 items-start">
                            <div class="w-9 h-9 rounded-2xl bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 font-black text-sm flex items-center justify-center shrink-0 border border-rose-200 dark:border-rose-700">
                                7
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                                    Hari-H Wisuda: 2x Scan Presensi di Venue
                                </h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Tunjukkan QR Code E-Ticket pada smartphone / printout:
                                    <br />1. <strong>Gate Luar (Security):</strong> Scan kedatangan awal di pos gerbang.
                                    <br />2. <strong>Pintu Auditorium (Receptionist):</strong> Scan check-in venue auditorium & pengambilan jatah snack catering.
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- Helpful Notice -->
                    <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-center gap-3">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Jika Anda mengalami kendala teknis atau perubahan data orang tua, silakan koordinasikan dengan Panitia Wisuda melalui Grup WhatsApp resmi atau loket BAAK.</span>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400">Wisuda Smart System - Politeknik Indonusa Surakarta</span>
                    <button
                        type="button"
                        @click="showManualModal = false"
                        class="px-5 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-xs rounded-xl hover:opacity-90 transition"
                    >
                        Tutup Panduan
                    </button>
                </div>

            </div>
        </div>

    </AuthenticatedLayout>
</template>
