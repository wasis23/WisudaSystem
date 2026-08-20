<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    wisudawan: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const wisudawanData = computed(() => props.wisudawan || user.value?.wisudawan);
const isTracerStudyFilled = computed(() => Boolean(wisudawanData.value?.is_tracer_study_filled));
const isBiodataFilled = computed(() => Boolean(wisudawanData.value?.is_biodata_filled));

const guest1 = computed(() => wisudawanData.value?.tamu_tambahan?.[0]);
const guest2 = computed(() => wisudawanData.value?.tamu_tambahan?.[1]);

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

                <div v-if="wisudawanData?.qr_code_token" class="flex items-center gap-3">
                    <div class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-xl font-mono text-xs font-bold border border-indigo-200 dark:border-indigo-700">
                        QR ID: {{ wisudawanData.qr_code_token }}
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- WhatsApp Group Announcement Banner -->
                <div class="bg-gradient-to-r from-indigo-700 via-blue-800 to-indigo-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-2 max-w-2xl">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 rounded-full text-xs font-bold uppercase tracking-wider">
                                    📢 PENGUMUMAN PENTING WISUDAWAN
                                </span>
                            </div>
                            
                            <p class="text-indigo-100 text-sm sm:text-base leading-relaxed pt-1">
                                Seluruh calon wisudawan <strong class="text-white font-bold">WAJIB bergabung ke Grup WhatsApp Resmi Wisuda</strong>. Semua informasi penting, tata tertib, jadwal gladi bersih, pembagian nomor kursi, dan koordinasi wisuda akan disampaikan secara mendesak di grup WhatsApp tersebut.
                            </p>
                        </div>

                        <!-- WhatsApp Join Button -->
                        <div class="shrink-0 flex flex-col items-start lg:items-end gap-2">
                            <a
                                href="https://chat.whatsapp.com/Hc3ag9O2vBhLLqZT5TNXjx?s=cl&p=a&mlu=4"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-sm rounded-2xl transition shadow-lg flex items-center gap-2.5 hover:scale-105 transform duration-150"
                            >
                                <span class="text-lg">💬</span>
                                <span>Gabung Grup WhatsApp Wisuda →</span>
                            </a>
                            <span class="text-[11px] text-indigo-200/80">Tautan Resmi WhatsApp Politeknik Indonusa</span>
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
                                <span :class="['text-xs font-bold px-3 py-1 rounded-full', isTracerStudyFilled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300']">
                                    {{ isTracerStudyFilled ? '✓ Selesai' : 'Wajib Diisi' }}
                                </span>
                            </div>

                            <h4 class="font-black text-slate-900 dark:text-white text-lg">
                                Data Tracer Study Alumni
                            </h4>
                            
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                                Wajib mengisi kuesioner pelacakan karir alumni untuk evaluasi lulusan & akreditasi institusi sebelum mengakses biodata wisuda.
                            </p>
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
                                
                                <span v-if="!isTracerStudyFilled" class="text-xs font-bold px-3 py-1 rounded-full bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 flex items-center gap-1">
                                    🔒 Terkunci
                                </span>
                                <span v-else-if="wisudawanData?.is_biodata_filled" class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300">
                                    ✓ Terisi
                                </span>
                                <span v-else class="text-xs font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300">
                                    Belum Diisi
                                </span>
                            </div>

                            <h4 class="font-black text-slate-900 dark:text-white text-lg">
                                Biodata & Live Preview Layar Wisuda
                            </h4>
                            
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                                Lengkapi NIM, Judul TA, Dosen Pembimbing & Penguji, Nama Orang Tua, Pas Foto & Lihat Live Preview Layar Wisuda Anda.
                            </p>
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
                                    🔒 Akses Terkunci (Isi Tracer Study)
                                </button>
                                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold text-center leading-tight">
                                    ⚠️ Harap selesaikan pengisian Data Tracer Study terlebih dahulu untuk membuka menu ini.
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
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold text-xs rounded-full">
                                    ✓ BIODATA TERVERIFIKASI
                                </span>
                                <span class="text-xs text-slate-400">3 Barcode Presensi Digital (1 Mahasiswa + 2 Pendamping)</span>
                            </div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white mt-1 flex items-center gap-2">
                                🎫 E-Ticket & Barcode Presensi Wisuda
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Barcode ini berlaku untuk <strong>2x Scan Presensi</strong>: 1️⃣ Oleh Security di Halaman Depan & 2️⃣ Oleh Staf Presensi di Pintu Masuk Venue Auditorium.
                            </p>
                        </div>

                        <button
                            @click="printTickets"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition flex items-center gap-2 shrink-0 shadow-sm"
                        >
                            <span>🖨️</span>
                            <span>Cetak / Simpan E-Ticket (PDF)</span>
                        </button>
                    </div>

                    <!-- 3 BARCODES GRID -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="ticket-print-area">
                        
                        <!-- CARD 1: BARCODE MAHASISWA -->
                        <div class="bg-gradient-to-b from-indigo-50/50 to-white dark:from-slate-900/50 dark:to-slate-800 rounded-2xl border-2 border-indigo-200 dark:border-indigo-800 p-5 flex flex-col items-center text-center space-y-4 shadow-sm relative overflow-hidden">
                            <div class="w-full bg-indigo-600 text-white py-1.5 px-3 rounded-xl font-extrabold text-xs uppercase tracking-wider">
                                🎓 Mahasiswa Wisudawan
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
                                        {{ wisudawanData?.is_hadir ? '✓ Scanned' : '⏳ Belum Scan' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">2. Staf Venue (Auditorium):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', wisudawanData?.is_in_auditorium ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ wisudawanData?.is_in_auditorium ? '✓ Scanned' : '⏳ Belum Scan' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 2: PENDAMPING 1 -->
                        <div class="bg-gradient-to-b from-blue-50/50 to-white dark:from-slate-900/50 dark:to-slate-800 rounded-2xl border-2 border-blue-200 dark:border-blue-800 p-5 flex flex-col items-center text-center space-y-4 shadow-sm relative overflow-hidden">
                            <div class="w-full bg-blue-600 text-white py-1.5 px-3 rounded-xl font-extrabold text-xs uppercase tracking-wider">
                                👥 Pendamping / Orang Tua #1
                            </div>

                            <div class="bg-white p-3 rounded-2xl border border-blue-100 shadow-inner">
                                <QrcodeVue
                                    :value="guest1?.qr_guest_token || ('GST-1-' + (wisudawanData?.nim || 'GUEST1'))"
                                    :size="150"
                                    level="H"
                                    render-as="svg"
                                />
                            </div>

                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">
                                    {{ guest1?.nama_tamu || 'Pendamping 1 (Orang Tua/Wali)' }}
                                </h4>
                                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mt-0.5">
                                    {{ guest1?.hubungan || 'Orang Tua / Wali' }}
                                </p>
                                <span class="text-[10px] text-slate-400 block mt-1 font-mono bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">
                                    ID: {{ guest1?.qr_guest_token || ('GST-1-' + wisudawanData?.nim) }}
                                </span>
                            </div>

                            <!-- SCAN STATUS TIMELINE -->
                            <div class="w-full pt-3 border-t border-slate-100 dark:border-slate-700 space-y-1.5 text-left text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">1. Security (Gate):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', (guest1?.is_hadir_gate || guest1?.is_hadir) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ (guest1?.is_hadir_gate || guest1?.is_hadir) ? '✓ Scanned' : '⏳ Belum Scan' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">2. Staf Venue (Auditorium):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', guest1?.is_hadir_venue ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ guest1?.is_hadir_venue ? '✓ Scanned & Snack' : '⏳ Belum Scan' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 3: PENDAMPING 2 -->
                        <div class="bg-gradient-to-b from-purple-50/50 to-white dark:from-slate-900/50 dark:to-slate-800 rounded-2xl border-2 border-purple-200 dark:border-purple-800 p-5 flex flex-col items-center text-center space-y-4 shadow-sm relative overflow-hidden">
                            <div class="w-full bg-purple-600 text-white py-1.5 px-3 rounded-xl font-extrabold text-xs uppercase tracking-wider">
                                👥 Pendamping / Orang Tua #2
                            </div>

                            <div class="bg-white p-3 rounded-2xl border border-purple-100 shadow-inner">
                                <QrcodeVue
                                    :value="guest2?.qr_guest_token || ('GST-2-' + (wisudawanData?.nim || 'GUEST2'))"
                                    :size="150"
                                    level="H"
                                    render-as="svg"
                                />
                            </div>

                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">
                                    {{ guest2?.nama_tamu || 'Pendamping 2 (Orang Tua/Wali)' }}
                                </h4>
                                <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mt-0.5">
                                    {{ guest2?.hubungan || 'Orang Tua / Wali' }}
                                </p>
                                <span class="text-[10px] text-slate-400 block mt-1 font-mono bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">
                                    ID: {{ guest2?.qr_guest_token || ('GST-2-' + wisudawanData?.nim) }}
                                </span>
                            </div>

                            <!-- SCAN STATUS TIMELINE -->
                            <div class="w-full pt-3 border-t border-slate-100 dark:border-slate-700 space-y-1.5 text-left text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">1. Security (Gate):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', (guest2?.is_hadir_gate || guest2?.is_hadir) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ (guest2?.is_hadir_gate || guest2?.is_hadir) ? '✓ Scanned' : '⏳ Belum Scan' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">2. Staf Venue (Auditorium):</span>
                                    <span :class="['font-bold px-2 py-0.5 rounded-full text-[10px]', guest2?.is_hadir_venue ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                                        {{ guest2?.is_hadir_venue ? '✓ Scanned & Snack' : '⏳ Belum Scan' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ALERT IF BIODATA IS NOT FILLED -->
                <div v-else class="bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-300 dark:border-amber-700/60 rounded-3xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">🎫</span>
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
    </AuthenticatedLayout>
</template>
