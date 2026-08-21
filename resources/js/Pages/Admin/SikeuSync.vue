<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    stats: Object,
    recentLogs: Array,
    payments: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.q || '');
const statusFilter = ref(props.filters?.status || '');
const syncForm = useForm({});

// Edit Modal state
const isEditModalOpen = ref(false);
const editingPayment = ref(null);
const editForm = useForm({
    status_bayar: 'lunas',
    jumlah_undangan_extra: 0,
    total_bayar: 2000000,
    keterangan: '',
});

const runSync = () => {
    syncForm.post(route('admin.sync-sikeu.sync'), {
        preserveScroll: true,
    });
};

const handleSearch = () => {
    router.get(route('admin.sync-sikeu.index'), {
        q: searchQuery.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const setStatusFilter = (st) => {
    statusFilter.value = st;
    handleSearch();
};

const togglePaymentStatus = (payment) => {
    const nextStatus = payment.status_bayar === 'lunas' ? 'BELUM LUNAS' : 'LUNAS';
    if (confirm(`Ubah status pembayaran ${payment.nama} (NIM: ${payment.nim}) menjadi ${nextStatus}?`)) {
        router.post(route('admin.sync-sikeu.toggle', payment.id), {}, {
            preserveScroll: true,
        });
    }
};

const openEditModal = (payment) => {
    editingPayment.value = payment;
    editForm.status_bayar = payment.status_bayar || 'lunas';
    editForm.jumlah_undangan_extra = payment.jumlah_undangan_extra || 0;
    editForm.total_bayar = payment.total_bayar || 2000000;
    editForm.keterangan = payment.keterangan || '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingPayment.value = null;
};

const submitEdit = () => {
    if (!editingPayment.value) return;
    editForm.post(route('admin.sync-sikeu.update', editingPayment.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Sinkronisasi Pembayaran Wisuda (SIKEU)" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span>💳</span>
                        <span>Integrasi Keuangan SIKEU & Pembayaran Wisuda</span>
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Monitoring data pembayaran wisuda, verifikasi lunas/belum lunas, kuota ekstra undangan, dan proteksi pemanggilan panggung.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="runSync"
                        :disabled="syncForm.processing"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-2"
                    >
                        <svg v-if="syncForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span v-else>⚡</span>
                        <span>{{ syncForm.processing ? 'Menyinkronkan...' : 'Sinkronkan Pembayaran SIKEU' }}</span>
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Lunas -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                            Pembayaran Lunas
                        </span>
                        <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 text-lg">
                            ✓
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.total_lunas }}</span>
                        <span class="text-xs text-slate-500 font-medium">/ {{ stats.total_cached }} Mahasiswa</span>
                    </div>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">
                        Diberikan akses penuh barcode & prosesi panggung.
                    </p>
                </div>

                <!-- Card 2: Belum Lunas -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                            Belum Lunas / Tertunda
                        </span>
                        <span class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 text-lg">
                            ⚠️
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-rose-600 dark:text-rose-400">{{ stats.total_belum_lunas }}</span>
                        <span class="text-xs text-slate-500 font-medium">Mahasiswa</span>
                    </div>
                    <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-1 font-semibold">
                        Akses prosesi & gate otomatis diblokir sampai lunas.
                    </p>
                </div>

                <!-- Card 3: Total Dana Masuk -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                            Total Dana Masuk
                        </span>
                        <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 text-lg">
                            💰
                        </span>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatRupiah(stats.total_nominal) }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Tercatat di sistem keuangan SIKEU.
                    </p>
                </div>

                <!-- Card 4: Ekstra Tamu -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                            Ekstra Tamu Berbayar
                        </span>
                        <span class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 text-lg">
                            👥
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.total_extra_guests }}</span>
                        <span class="text-xs text-slate-500 font-medium">Kursi Tambahan</span>
                    </div>
                    <p class="text-[11px] text-purple-600 dark:text-purple-400 mt-1 font-semibold">
                        Katering snack disesuaikan otomatis.
                    </p>
                </div>
            </div>

            <!-- Table & Filters Section -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <button
                            @click="setStatusFilter('')"
                            :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', !statusFilter ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200']"
                        >
                            Semua ({{ stats.total_cached }})
                        </button>
                        <button
                            @click="setStatusFilter('lunas')"
                            :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', statusFilter === 'lunas' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200']"
                        >
                            Lunas ({{ stats.total_lunas }})
                        </button>
                        <button
                            @click="setStatusFilter('belum_lunas')"
                            :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', statusFilter === 'belum_lunas' ? 'bg-rose-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200']"
                        >
                            Belum Lunas ({{ stats.total_belum_lunas }})
                        </button>
                    </div>

                    <div class="relative max-w-xs w-full">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Cari NIM / Nama / No Transaksi..."
                            class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs focus:ring-2 focus:ring-indigo-500 outline-none text-slate-900 dark:text-white"
                        />
                        <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th class="p-3.5 pl-6">Mahasiswa (NIM & Nama)</th>
                                <th class="p-3.5">Status Pembayaran</th>
                                <th class="p-3.5">Total Bayar</th>
                                <th class="p-3.5">Kuota Tamu & Snack</th>
                                <th class="p-3.5">Tanggal / No Transaksi</th>
                                <th class="p-3.5 pr-6 text-right">Aksi & Override</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="6" class="text-center py-10 text-slate-400">
                                    Tidak ada data pembayaran yang cocok. Klik tombol "Sinkronkan Pembayaran SIKEU" untuk menarik data.
                                </td>
                            </tr>
                            <tr
                                v-for="item in payments.data"
                                :key="item.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition"
                            >
                                <td class="p-3.5 pl-6">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ item.nama || item.wisudawan?.nama_lengkap || 'Calon Wisudawan' }}
                                    </div>
                                    <div class="font-mono text-slate-500 dark:text-slate-400 text-[11px]">
                                        NIM: {{ item.nim }}
                                    </div>
                                    <div v-if="item.wisudawan?.program_studi" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                                        {{ item.wisudawan.program_studi.nama_prodi }}
                                    </div>
                                </td>

                                <td class="p-3.5">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wide inline-flex items-center gap-1.5',
                                            item.status_bayar === 'lunas'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700'
                                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-700'
                                        ]"
                                    >
                                        <span>{{ item.status_bayar === 'lunas' ? '✓' : '⚠️' }}</span>
                                        <span>{{ item.status_bayar === 'lunas' ? 'Lunas' : 'Belum Lunas' }}</span>
                                    </span>
                                </td>

                                <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ formatRupiah(item.total_bayar) }}
                                </td>

                                <td class="p-3.5">
                                    <div class="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <span>👥 {{ item.total_kuota_undangan || 2 }} Undangan</span>
                                        <span v-if="item.jumlah_undangan_extra > 0" class="px-1.5 py-0.2 text-[10px] bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 rounded font-bold">
                                            +{{ item.jumlah_undangan_extra }} Extra
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        🍱 {{ item.snack_kuota || 3 }} Porsi Snack
                                    </div>
                                </td>

                                <td class="p-3.5">
                                    <div class="text-slate-800 dark:text-slate-200 font-medium">
                                        {{ formatDate(item.tanggal_bayar) }}
                                    </div>
                                    <div v-if="item.no_transaksi" class="font-mono text-[10px] text-slate-500 truncate max-w-[150px]">
                                        {{ item.no_transaksi }}
                                    </div>
                                </td>

                                <td class="p-3.5 pr-6 text-right space-x-2">
                                    <button
                                        @click="togglePaymentStatus(item)"
                                        :class="[
                                            'px-2.5 py-1 rounded-lg text-xs font-bold transition shadow-sm',
                                            item.status_bayar === 'lunas'
                                                ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 hover:bg-rose-100'
                                                : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100'
                                        ]"
                                    >
                                        {{ item.status_bayar === 'lunas' ? 'Set Belum Lunas' : 'Set Lunas ✓' }}
                                    </button>

                                    <button
                                        @click="openEditModal(item)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 transition"
                                    >
                                        Edit Detail
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="p-4 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-1">
                    <component
                        :is="link.url ? 'a' : 'span'"
                        v-for="(link, i) in payments.links"
                        :key="i"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                            link.active ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200',
                            !link.url ? 'opacity-40 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>

            <!-- Recent Sync Logs -->
            <div v-if="recentLogs && recentLogs.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 p-5 shadow-sm">
                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <span>📜</span>
                    <span>Riwayat Sinkronisasi SIKEU Terakhir</span>
                </h4>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    <div v-for="log in recentLogs" :key="log.id" class="py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span :class="['w-2 h-2 rounded-full', log.status === 'success' ? 'bg-emerald-500' : 'bg-rose-500']"></span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ log.notes || 'Sync SIKEU' }}</span>
                        </div>
                        <span class="text-slate-400 font-mono text-[11px]">{{ formatDate(log.created_at) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Payment Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">
                        Edit Data Pembayaran & Kuota
                    </h3>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Mahasiswa / NIM</label>
                        <input readonly :value="`${editingPayment?.nama} (${editingPayment?.nim})`" class="w-full rounded-xl bg-slate-100 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-600 text-xs cursor-not-allowed" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status Pembayaran</label>
                        <select v-model="editForm.status_bayar" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-xs font-semibold">
                            <option value="lunas">✓ LUNAS (Diizinkan Prosesi & Barcode)</option>
                            <option value="belum_lunas">⚠️ BELUM LUNAS (Blokir Prosesi)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Jumlah Undangan Ekstra SIKEU</label>
                        <input v-model.number="editForm.jumlah_undangan_extra" type="number" min="0" max="10" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-xs" />
                        <p class="text-[10px] text-slate-400 mt-1">Total undangan akan menjadi {{ 2 + Number(editForm.jumlah_undangan_extra || 0) }} orang.</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nominal Pembayaran (Rp)</label>
                        <input v-model.number="editForm.total_bayar" type="number" step="50000" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-xs" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan / Keterangan</label>
                        <textarea v-model="editForm.keterangan" rows="2" placeholder="Catatan verifikasi..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-xs"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-xl">
                            Batal
                        </button>
                        <button type="submit" :disabled="editForm.processing" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
