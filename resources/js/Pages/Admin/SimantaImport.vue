<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    candidates: Array,
    activePeriode: Object,
    programStudis: Array,
    filter: Object,
    totalUnimported: Number,
    totalInCache: Number,
});

const filterSearch = ref('');
const selectedNims = ref(props.candidates ? props.candidates.map(c => c.nim) : []);
const selectAll = ref(true);

const filteredCandidates = computed(() => {
    if (!props.candidates) return [];
    if (!filterSearch.value.trim()) return props.candidates;
    const q = filterSearch.value.toLowerCase().trim();
    return props.candidates.filter(c => 
        (c.nim && c.nim.toLowerCase().includes(q)) ||
        (c.nama && c.nama.toLowerCase().includes(q)) ||
        (c.judul_ta && c.judul_ta.toLowerCase().includes(q))
    );
});

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedNims.value = filteredCandidates.value.map(c => c.nim);
    } else {
        selectedNims.value = [];
    }
};

const filterTglDari = ref(props.filter?.tgl_dari || '');
const filterTglSampai = ref(props.filter?.tgl_sampai || '');
const filterIgnoreDate = ref(Boolean(props.filter?.ignore_date));

const applyFilter = () => {
    router.get(route('admin.sync-simanta.import'), {
        tgl_dari: filterTglDari.value,
        tgl_sampai: filterTglSampai.value,
        ignore_date: filterIgnoreDate.value ? 1 : 0,
    }, { preserveState: true });
};

const form = useForm({
    periode_wisuda_id: props.activePeriode?.id || '',
    nim: [],
    tgl_dari: props.filter?.tgl_dari || '',
    tgl_sampai: props.filter?.tgl_sampai || '',
    ignore_date: props.filter?.ignore_date ? '1' : '0',
    auto_create_user: '1',
});

const submitImport = () => {
    if (!form.periode_wisuda_id) {
        alert('Pilih Periode Wisuda terlebih dahulu!');
        return;
    }

    if (selectedNims.value.length === 0) {
        alert('Pilih setidaknya 1 mahasiswa untuk di-import.');
        return;
    }

    if (!confirm(`Import ${selectedNims.value.length} mahasiswa terpilih ke tabel Wisudawan?`)) {
        return;
    }

    form.nim = selectedNims.value;
    form.tgl_dari = filterTglDari.value;
    form.tgl_sampai = filterTglSampai.value;
    form.ignore_date = filterIgnoreDate.value ? '1' : '0';
    form.post(route('admin.sync-simanta.import'));
};

const formatDate = (dt) => {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', { dateStyle: 'medium' });
};
</script>

<template>
    <Head title="Import Data Lulusan ke Wisudawan" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <Link :href="route('admin.sync-simanta.index')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                        ← Kembali ke Sinkronisasi SIMANTA
                    </Link>
                </div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">📥</span> Konfirmasi Import Wisudawan
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Memindahkan data lulusan dari SIMANTA ke daftar Wisudawan resmi untuk pembuatan akun & presensi.
                </p>
            </div>
        </div>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium rounded-xl">
            ✅ {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 text-sm font-medium rounded-xl">
            ❌ {{ $page.props.flash.error }}
        </div>
        <div v-if="$page.props.flash?.info" class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-800 text-sm font-medium rounded-xl">
            ℹ️ {{ $page.props.flash.info }}
        </div>

        <!-- Filter & Setting Options Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 mb-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-3">⚙️ Pengaturan & Filter Import</h2>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Periode Wisuda Tujuan</label>
                    <select
                        v-model="form.periode_wisuda_id"
                        class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    >
                        <option value="">-- Pilih Periode --</option>
                        <option :value="activePeriode?.id" v-if="activePeriode">
                            {{ activePeriode.nama_periode }} ({{ activePeriode.tahun_akademik }}) [Aktif]
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Filter Pendadaran Dari - Sampai</label>
                    <div class="flex items-center gap-1">
                        <input type="date" v-model="filterTglDari" class="w-1/2 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-2 py-2 outline-none" />
                        <span class="text-xs text-gray-400">-</span>
                        <input type="date" v-model="filterTglSampai" class="w-1/2 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-2 py-2 outline-none" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Opsi Pembuatan Akun User</label>
                    <select
                        v-model="form.auto_create_user"
                        class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    >
                        <option value="1">Buat Akun User Otomatis (Default Login = NIM)</option>
                        <option value="0">Hanya Buat Data Wisudawan (Tanpa Akun Login)</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="submitImport"
                        :disabled="form.processing || selectedNims.length === 0"
                        class="w-full flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-bold rounded-xl shadow transition"
                    >
                        <span>🚀</span> Process Import ({{ selectedNims.length }} Mahasiswa)
                    </button>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 text-xs">
                <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300 cursor-pointer font-medium">
                    <input type="checkbox" v-model="filterIgnoreDate" @change="applyFilter" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span>Abaikan filter rentang tanggal (Tampilkan semua mahasiswa lulus belum terdaftar)</span>
                </label>

                <div class="flex items-center gap-2">
                    <button @click="applyFilter" class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-100 font-semibold transition">
                        🔍 Terapkan Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Candidates -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h2 class="font-bold text-gray-800 dark:text-white text-sm flex items-center gap-2">
                    📋 Mahasiswa Siap Import ({{ filteredCandidates?.length ?? 0 }} / {{ candidates?.length ?? 0 }})
                </h2>
                <div v-if="candidates?.length" class="flex items-center gap-3">
                    <input
                        v-model="filterSearch"
                        type="text"
                        placeholder="🔍 Filter NIM / Nama..."
                        class="text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none w-48 sm:w-64"
                    />
                    <label class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5 cursor-pointer shrink-0">
                        <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="rounded border-gray-300 text-indigo-600" />
                        Pilih Semua
                    </label>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-750 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-4 py-3 w-10 text-center">#</th>
                            <th class="px-4 py-3 text-left">NIM</th>
                            <th class="px-4 py-3 text-left">Nama Lengkap</th>
                            <th class="px-4 py-3 text-left">Judul TA / Skripsi</th>
                            <th class="px-4 py-3 text-left">Prodi SIMANTA</th>
                            <th class="px-4 py-3 text-left">Tgl Pendadaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        <tr v-if="!candidates?.length">
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="text-4xl">📭</div>
                                    <p class="text-gray-800 dark:text-white font-bold text-base">Tidak ada mahasiswa yang siap di-import pada filter ini.</p>

                                    <!-- Context details -->
                                    <p v-if="totalUnimported > 0" class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 p-3 rounded-xl border border-amber-200">
                                        💡 Terdapat <strong>{{ totalUnimported }}</strong> mahasiswa lulus yang tersimpan di cache lokal, namun tanggal pendadaran mereka berada di luar rentang (<strong>{{ filterTglDari }}</strong> s/d <strong>{{ filterTglSampai }}</strong>).
                                    </p>

                                    <p v-else-if="totalInCache === 0" class="text-xs text-gray-500 dark:text-gray-400">
                                        Cache SIMANTA masih kosong (0 data). Silakan lakukan sinkronisasi data lulusan terlebih dahulu.
                                    </p>

                                    <div class="flex items-center justify-center gap-3 pt-2">
                                        <button v-if="totalUnimported > 0 && !filterIgnoreDate" @click="filterIgnoreDate = true; applyFilter()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow transition">
                                            🔓 Tampilkan Semua {{ totalUnimported }} Mahasiswa Lulus
                                        </button>
                                        <Link :href="route('admin.sync-simanta.index')" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 text-xs font-semibold rounded-xl transition">
                                            ↻ Ke Halaman Sync SIMANTA
                                        </Link>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="!filteredCandidates?.length">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                Mahasiswa tidak ditemukan dengan kata kunci "{{ filterSearch }}".
                            </td>
                        </tr>
                        <tr v-for="c in filteredCandidates" :key="c.nim" class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" :value="c.nim" v-model="selectedNims" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            </td>
                            <td class="px-4 py-3 font-mono font-semibold text-gray-800 dark:text-white text-xs">
                                {{ c.nim }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ c.nama || '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">
                                {{ c.judul_ta || '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                {{ c.nama_prodi || c.kode_prodi || '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 font-medium">
                                {{ formatDate(c.tanggal_pendadaran) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
