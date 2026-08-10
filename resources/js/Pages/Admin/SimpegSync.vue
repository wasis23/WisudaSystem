<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stats: Object,
    recentLogs: Array,
    employees: Object, // paginated
    filters: Object,
});

const syncing = ref(false);
const filterStatus = ref('');

const syncForm = useForm({
    status: '',
});

const doSync = () => {
    syncing.value = true;
    syncForm.status = filterStatus.value;
    syncForm.post(route('admin.sync-simpeg.sync'), {
        onFinish: () => { syncing.value = false; },
    });
};

const formatDate = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
};

const statusColor = (status) => {
    if (status === 'success') return 'bg-emerald-100 text-emerald-800';
    if (status === 'partial') return 'bg-amber-100 text-amber-800';
    return 'bg-red-100 text-red-800';
};

const searchQ = ref(props.filters?.q || '');
const searchStatus = ref(props.filters?.status || '');
const searching = ref(false);

const doSearch = () => {
    searching.value = true;
    router.get(route('admin.sync-simpeg.index'), {
        q: searchQ.value,
        status: searchStatus.value,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => { searching.value = false; },
    });
};

const resetSearch = () => {
    searchQ.value = '';
    searchStatus.value = '';
    doSearch();
};
</script>

<template>
    <Head title="Sinkronisasi SIMPEG — Data Pegawai" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">🏛️</span> Sinkronisasi Data SIMPEG
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Tarik data pegawai dari sistem SIMPEG dan simpan ke cache lokal wisuda.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <select
                    v-model="filterStatus"
                    class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                >
                    <option value="">Semua Pegawai</option>
                    <option value="dosen">Dosen</option>
                    <option value="tendik">Tendik / Pegawai</option>
                </select>
                <button
                    @click="doSync"
                    :disabled="syncing"
                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-xl shadow transition"
                >
                    <svg v-if="!syncing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    {{ syncing ? 'Menyinkronkan...' : 'Sync Sekarang' }}
                </button>
            </div>
        </div>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium rounded-xl flex items-center gap-2">
            ✅ {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 text-sm font-medium rounded-xl flex items-center gap-2">
            ❌ {{ $page.props.flash.error }}
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Cache</p>
                <p class="text-3xl font-black text-gray-900 dark:text-white mt-2">{{ stats?.total ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">pegawai tersimpan</p>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-950/40 rounded-2xl border border-indigo-100 dark:border-indigo-900 p-4 shadow-sm">
                <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Dosen</p>
                <p class="text-3xl font-black text-indigo-700 dark:text-indigo-300 mt-2">{{ stats?.dosen ?? 0 }}</p>
                <p class="text-xs text-indigo-400 mt-1">tenaga pengajar</p>
            </div>
            <div class="bg-violet-50 dark:bg-violet-950/40 rounded-2xl border border-violet-100 dark:border-violet-900 p-4 shadow-sm">
                <p class="text-xs font-bold text-violet-400 uppercase tracking-wider">Tendik</p>
                <p class="text-3xl font-black text-violet-700 dark:text-violet-300 mt-2">{{ stats?.tendik ?? 0 }}</p>
                <p class="text-xs text-violet-400 mt-1">tenaga kependidikan</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-950/40 rounded-2xl border border-amber-100 dark:border-amber-900 p-4 shadow-sm">
                <p class="text-xs font-bold text-amber-400 uppercase tracking-wider">Terakhir Sync</p>
                <p class="text-sm font-bold text-amber-700 dark:text-amber-300 mt-2">{{ stats?.last_sync ? formatDate(stats.last_sync) : 'Belum pernah' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Log Sync Terakhir -->
            <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-bold text-gray-800 dark:text-white text-sm flex items-center gap-2">
                        📋 Riwayat Sinkronisasi
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
                            <span class="text-xs text-gray-400">{{ formatDate(log.created_at) }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ log.notes }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            ↑ {{ log.records_inserted }} baru · ↻ {{ log.records_updated }} update · total {{ log.records_fetched }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Daftar Pegawai Cache -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center gap-3">
                    <h2 class="font-bold text-gray-800 dark:text-white text-sm flex items-center gap-2 shrink-0">
                        👥 Data Pegawai (Cache Lokal)
                    </h2>
                    <div class="flex items-center gap-2 ml-auto w-full sm:w-auto">
                        <input
                            v-model="searchQ"
                            @keydown.enter="doSearch"
                            type="text"
                            placeholder="Cari nama / NIDN..."
                            class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-1.5 flex-1 focus:ring-2 focus:ring-indigo-500 outline-none min-w-0"
                        />
                        <select
                            v-model="searchStatus"
                            @change="doSearch"
                            class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none"
                        >
                            <option value="">Semua</option>
                            <option value="dosen">Dosen</option>
                            <option value="tendik">Tendik</option>
                        </select>
                        <button
                            @click="doSearch"
                            :disabled="searching"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition"
                        >
                            Cari
                        </button>
                        <button
                            v-if="searchQ || searchStatus"
                            @click="resetSearch"
                            title="Reset Pencarian"
                            class="px-2 py-1.5 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 text-gray-700 dark:text-white text-xs font-medium rounded-lg transition"
                        >
                            ✕
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-750 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-4 py-3 text-left">Nama</th>
                                <th class="px-4 py-3 text-left">NIDN / NIP</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Sync</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            <tr v-if="!employees?.data?.length">
                                <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                                    Belum ada data. Klik <strong>Sync Sekarang</strong> untuk menarik data dari SIMPEG.
                                </td>
                            </tr>
                            <tr v-for="emp in employees?.data" :key="emp.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-800 dark:text-white">{{ emp.nama }}</p>
                                    <p class="text-xs text-gray-400">{{ emp.email || '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs">
                                    {{ emp.nidn || emp.nip || '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="emp.jenis === 'dosen'
                                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                                        : 'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300'"
                                        class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize">
                                        {{ emp.jenis }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-400">
                                    {{ emp.synced_at ? formatDate(emp.synced_at) : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="employees?.last_page > 1" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500">
                    <span>Hal {{ employees.current_page }} dari {{ employees.last_page }}</span>
                    <div class="flex gap-1">
                        <a v-if="employees.prev_page_url" :href="employees.prev_page_url"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium">
                            ← Prev
                        </a>
                        <a v-if="employees.next_page_url" :href="employees.next_page_url"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium">
                            Next →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
