<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    stats: Object,
    recentLogs: Array,
    mahasiswa: Object, // paginated
    filter: Object,
    default_range: Object,
});

const syncing = ref(false);
const tglDari = ref(props.filter?.tgl_dari || props.default_range?.dari || '');
const tglSampai = ref(props.filter?.tgl_sampai || props.default_range?.sampai || '');
const prodi = ref('');

const syncForm = useForm({
    tgl_dari: '',
    tgl_sampai: '',
    prodi: '',
});

const doSync = () => {
    syncing.value = true;
    syncForm.tgl_dari = tglDari.value;
    syncForm.tgl_sampai = tglSampai.value;
    syncForm.prodi = prodi.value;
    
    syncForm.post(route('admin.sync-simanta.sync'), {
        onFinish: () => { syncing.value = false; },
    });
};

const applyFilter = () => {
    router.get(route('admin.sync-simanta.index'), {
        tgl_dari: tglDari.value,
        tgl_sampai: tglSampai.value,
    }, { preserveState: true });
};

const formatDate = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', { dateStyle: 'medium' });
};

const formatDateTime = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
};

const statusColor = (status) => {
    if (status === 'success') return 'bg-emerald-100 text-emerald-800';
    if (status === 'partial') return 'bg-amber-100 text-amber-800';
    return 'bg-red-100 text-red-800';
};
</script>

<template>
    <Head title="Sinkronisasi SIMANTA — Kelulusan Mahasiswa" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">🎓</span> Sinkronisasi Data Lulusan SIMANTA
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Tarik data mahasiswa lulus (sidang TA/pendadaran) dari SIMANTA berdasarkan rentang waktu.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    :href="route('admin.sync-simanta.import.preview', { tgl_dari: tglDari, tgl_sampai: tglSampai })"
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow transition"
                >
                    <span>📥</span> Import ke Wisudawan
                </Link>
            </div>
        </div>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium rounded-xl flex items-center gap-2">
            ✅ {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 text-sm font-medium rounded-xl flex items-center gap-2">
            ❌ {{ $page.props.flash.error }}
        </div>

        <!-- Filter Sync Form Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 mb-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                ⚙️ Parameter Sinkronisasi Rentang Kelulusan (Oktober - Oktober)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Tanggal Mulai Sidang</label>
                    <input
                        v-model="tglDari"
                        type="date"
                        class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Tanggal Akhir Sidang</label>
                    <input
                        v-model="tglSampai"
                        type="date"
                        class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Program Studi (Optional)</label>
                    <select
                        v-model="prodi"
                        class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    >
                        <option value="">Semua Prodi</option>
                        <option value="A">Teknologi Otomotif (A)</option>
                        <option value="B">Sistem Informasi (B)</option>
                        <option value="C">Komunikasi Massa (C)</option>
                        <option value="D">Perhotelan (D)</option>
                        <option value="E">Farmasi (E)</option>
                        <option value="F">MIK (F)</option>
                        <option value="G">TLM (G)</option>
                        <option value="H">BMR (H)</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="doSync"
                        :disabled="syncing"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-xl shadow transition"
                    >
                        <svg v-if="!syncing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ syncing ? 'Pulling Data...' : 'Pull Data SIMANTA' }}
                    </button>
                    <button
                        @click="applyFilter"
                        class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-xl transition"
                        title="Filter Data di Cache"
                    >
                        Filter Cache
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Lulusan (Periode Ini)</p>
                <p class="text-3xl font-black text-gray-900 dark:text-white mt-2">{{ stats?.total_periode_ini ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">mahasiswa terfilter</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl border border-emerald-100 dark:border-emerald-900 p-4 shadow-sm">
                <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider">Belum Terdaftar Wisuda</p>
                <p class="text-3xl font-black text-emerald-700 dark:text-emerald-300 mt-2">{{ stats?.belum_daftar_wisuda ?? 0 }}</p>
                <p class="text-xs text-emerald-500 mt-1">siap di-import</p>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-950/40 rounded-2xl border border-indigo-100 dark:border-indigo-900 p-4 shadow-sm">
                <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Total Cache SIMANTA</p>
                <p class="text-3xl font-black text-indigo-700 dark:text-indigo-300 mt-2">{{ stats?.total_cache ?? 0 }}</p>
                <p class="text-xs text-indigo-400 mt-1">seluruh data ter-sync</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-950/40 rounded-2xl border border-amber-100 dark:border-amber-900 p-4 shadow-sm">
                <p class="text-xs font-bold text-amber-400 uppercase tracking-wider">Terakhir Sync</p>
                <p class="text-sm font-bold text-amber-700 dark:text-amber-300 mt-2">{{ stats?.last_sync ? formatDateTime(stats.last_sync) : 'Belum pernah' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Log Sync Terakhir -->
            <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-bold text-gray-800 dark:text-white text-sm flex items-center gap-2">
                        📋 Riwayat Pull SIMANTA
                    </h2>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    <div v-if="!recentLogs?.length" class="p-6 text-center text-gray-400 text-sm">
                        Belum ada riwayat sync.
                    </div>
                    <div v-for="log in recentLogs" :key="log.id" class="px-5 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span :class="statusColor(log.status)" class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize">
                                {{ log.status }}
                            </span>
                            <span class="text-xs text-gray-400">{{ formatDateTime(log.created_at) }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ log.notes }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            ↑ {{ log.records_inserted }} baru · ↻ {{ log.records_updated }} update · total {{ log.records_fetched }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Daftar Mahasiswa Lulus Cache -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-bold text-gray-800 dark:text-white text-sm flex items-center gap-2">
                        📜 Data Lulusan Sidang / Pendadaran (Cache)
                    </h2>
                    <span class="text-xs text-gray-400">Total: {{ mahasiswa?.total ?? 0 }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-750 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-4 py-3 text-left">Mahasiswa</th>
                                <th class="px-4 py-3 text-left">Prodi</th>
                                <th class="px-4 py-3 text-left">Tgl Pendadaran</th>
                                <th class="px-4 py-3 text-left">Status Wisuda</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            <tr v-if="!mahasiswa?.data?.length">
                                <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                                    Belum ada data. Silakan tentukan tanggal dan klik <strong>Pull Data SIMANTA</strong>.
                                </td>
                            </tr>
                            <tr v-for="mhs in mahasiswa?.data" :key="mhs.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-800 dark:text-white">{{ mhs.nama || mhs.nim }}</p>
                                    <p class="text-xs text-gray-400 font-mono">{{ mhs.nim }}</p>
                                    <p v-if="mhs.judul_ta" class="text-xs text-gray-500 italic mt-0.5 line-clamp-1">"{{ mhs.judul_ta }}"</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                    {{ mhs.nama_prodi || mhs.kode_prodi || '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 font-medium">
                                    {{ formatDate(mhs.tanggal_pendadaran) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="mhs.wisudawan_id" class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                                        ✓ Terdaftar
                                    </span>
                                    <span v-else class="bg-amber-100 text-amber-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                                        Belum Import
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="mahasiswa?.last_page > 1" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500">
                    <span>Hal {{ mahasiswa.current_page }} dari {{ mahasiswa.last_page }}</span>
                    <div class="flex gap-1">
                        <a v-if="mahasiswa.prev_page_url" :href="mahasiswa.prev_page_url"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium">
                            ← Prev
                        </a>
                        <a v-if="mahasiswa.next_page_url" :href="mahasiswa.next_page_url"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium">
                            Next →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
