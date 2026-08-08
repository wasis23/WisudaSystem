<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    recentWisudawan: Array,
    stageConfig: Object,
});

const tracerPercentage = computed(() => {
    if (!props.stats?.totalWisudawan) return 0;
    return Math.round((props.stats.tracerCompleted / props.stats.totalWisudawan) * 100);
});
</script>

<template>
    <Head title="Dashboard PKL - Laravel" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- TOP STATS CARDS GRID (EXACT PKL DASHBOARD CARD STYLE) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                
                <!-- KPI 1: Total Wisudawan -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Wisudawan</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ stats?.totalWisudawan || 0 }}
                        </span>
                        <span class="text-xs font-medium text-gray-500">Mahasiswa</span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        Terdaftar di Sistem
                    </div>
                </div>

                <!-- KPI 2: PESERTA BELUM DATANG (IMPORTANT FOR ADMIN) -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl shadow-lg p-5 transition hover:shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-100 uppercase tracking-wider">BELUM HADIR (BELUM DATANG)</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-400/30 text-white flex items-center justify-center text-lg">
                            ⏳
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl font-black tracking-tight">
                            {{ stats?.belumHadirCount ?? 0 }}
                        </span>
                        <span class="text-xs font-medium text-amber-100">Peserta Belum Hadir</span>
                    </div>
                    <div class="mt-3">
                        <Link 
                            :href="route('admin.monitoring-presensi', { status: 'belum_hadir' })" 
                            class="inline-flex items-center gap-1 text-xs font-bold text-amber-950 bg-white hover:bg-amber-50 px-3 py-1.5 rounded-lg transition shadow-sm"
                        >
                            <span>🔍 Cek Daftar Peserta Belum Datang &rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- KPI 3: HADIR GATE & AUDITORIUM -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-900/50 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Sudah Hadir Gate / Auditorium</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ stats?.hadirCount || 0 }}
                        </span>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">
                            {{ stats?.auditoriumCount || 0 }} di Ballroom
                        </span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Tercatat Gate Scan
                    </div>
                </div>

                <!-- KPI 4: Tracer Study Terisi -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tracer Study Terisi</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ stats?.tracerCompleted || 0 }}
                        </span>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">
                            {{ tracerPercentage }}%
                        </span>
                    </div>
                    <div class="mt-3 w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: `${tracerPercentage}%` }"></div>
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
                                Atur ➔
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
                                Kelola ➔
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
                                Kelola ➔
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

            <!-- SCAN GATES & ACCESS PREVIEW SECTION -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-base">📱</span>
                    <span>Akses Modul Gate Scanner & TV Kiosk Display</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Gate 1: Security Mobile Scanner -->
                    <div class="bg-gradient-to-br from-amber-900/10 via-amber-900/5 to-transparent border border-amber-500/20 dark:border-amber-500/30 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">👮</span>
                            <Link :href="route('security.scan')" class="px-3 py-1 bg-amber-500 text-slate-950 font-bold text-xs rounded-lg hover:bg-amber-400 transition">
                                Buka Preview &rarr;
                            </Link>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Gate Scan Security</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Scanner presensi mobile via kamera HP untuk petugas Security di gerbang masuk.</p>
                        </div>
                    </div>

                    <!-- Gate 2: Receptionist & Snack Mobile Scanner -->
                    <div class="bg-gradient-to-br from-purple-900/10 via-purple-900/5 to-transparent border border-purple-500/20 dark:border-purple-500/30 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">👩‍💼</span>
                            <Link :href="route('receptionist.scan')" class="px-3 py-1 bg-purple-600 text-white font-bold text-xs rounded-lg hover:bg-purple-500 transition">
                                Buka Preview &rarr;
                            </Link>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Gate Scan Receptionist</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pemeriksaan tamu pendamping, verifikasi kehadiran, dan pembagian snack.</p>
                        </div>
                    </div>

                    <!-- Gate 3: TV Display Kiosk -->
                    <div class="bg-gradient-to-br from-emerald-900/10 via-emerald-900/5 to-transparent border border-emerald-500/20 dark:border-emerald-500/30 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">🖥️</span>
                            <a :href="route('kiosk.display')" target="_blank" class="px-3 py-1 bg-emerald-600 text-white font-bold text-xs rounded-lg hover:bg-emerald-500 transition flex items-center gap-1">
                                <span>Layar TV ↗</span>
                            </a>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Self-Service TV Kiosk</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Layar TV Display ballroom terhubung USB scanner laptop untuk scan mandiri.</p>
                        </div>
                    </div>

                    <!-- Gate 4: SIMPEG Duty Assignments -->
                    <div class="bg-gradient-to-br from-sky-900/10 via-sky-900/5 to-transparent border border-sky-500/20 dark:border-sky-500/30 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">👮‍♂️</span>
                            <Link :href="route('admin.duty-assignments.index')" class="px-3 py-1 bg-sky-600 text-white font-bold text-xs rounded-lg hover:bg-sky-500 transition">
                                Kelola Tugas &rarr;
                            </Link>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Penugasan Staff SIMPEG</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pilih pegawai dari SIMPEG untuk ditugaskan scan presensi gate.</p>
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
                                            Detail ➔
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
