<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    candidates: Array,
    periodes: Array,
    activePeriode: Object,
    programStudis: Array,
    filter: Object,
    totalUnimported: Number,
    totalInCache: Number,
});

const activeTab = ref('simanta'); // 'simanta' | 'whitelist'
const filterSearch = ref('');
const filterProdi = ref(props.filter?.prodi || '');
const selectedNims = ref(props.candidates ? props.candidates.map(c => c.nim) : []);
const selectAll = ref(true);

const filterTglDari = ref(props.filter?.tgl_dari || '');
const filterTglSampai = ref(props.filter?.tgl_sampai || '');
const filterIgnoreDate = ref(Boolean(props.filter?.ignore_date));

const pasteNimsText = ref('');

const parsedPastedNims = computed(() => {
    if (!pasteNimsText.value.trim()) return [];
    const tokens = pasteNimsText.value.split(/[\r\n\t,; ]+/).filter(t => t.trim().length > 0);
    return Array.from(new Set(tokens.map(t => t.toUpperCase().trim())));
});

const filteredCandidates = computed(() => {
    if (!props.candidates) return [];
    let list = props.candidates;
    if (filterSearch.value.trim()) {
        const q = filterSearch.value.toLowerCase().trim();
        list = list.filter(c => 
            (c.nim && c.nim.toLowerCase().includes(q)) ||
            (c.nama && c.nama.toLowerCase().includes(q)) ||
            (c.judul_ta && c.judul_ta.toLowerCase().includes(q))
        );
    }
    return list;
});

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedNims.value = filteredCandidates.value.map(c => c.nim);
    } else {
        selectedNims.value = [];
    }
};

const applyFilter = () => {
    router.get(route('admin.sync-simanta.import'), {
        tgl_dari: filterTglDari.value,
        tgl_sampai: filterTglSampai.value,
        ignore_date: filterIgnoreDate.value ? 1 : 0,
        prodi: filterProdi.value,
    }, { preserveState: true });
};

const form = useForm({
    periode_wisuda_id: props.activePeriode?.id || (props.periodes?.[0]?.id || ''),
    nim: [],
    nim_list_text: '',
    tgl_dari: props.filter?.tgl_dari || '',
    tgl_sampai: props.filter?.tgl_sampai || '',
    ignore_date: props.filter?.ignore_date ? '1' : '0',
    auto_create_user: '1',
});

const submitImportSimanta = () => {
    if (!form.periode_wisuda_id) {
        alert('Pilih Periode Wisuda tujuan terlebih dahulu!');
        return;
    }

    if (selectedNims.value.length === 0) {
        alert('Pilih setidaknya 1 mahasiswa dari daftar SIMANTA untuk di-import.');
        return;
    }

    if (!confirm(`Import ${selectedNims.value.length} mahasiswa terpilih ke daftar wisudawan pada periode ini?`)) {
        return;
    }

    form.nim = selectedNims.value;
    form.nim_list_text = '';
    form.tgl_dari = filterTglDari.value;
    form.tgl_sampai = filterTglSampai.value;
    form.ignore_date = filterIgnoreDate.value ? '1' : '0';
    form.post(route('admin.sync-simanta.import'));
};

const submitImportWhitelist = () => {
    if (!form.periode_wisuda_id) {
        alert('Pilih Periode Wisuda tujuan terlebih dahulu!');
        return;
    }

    if (parsedPastedNims.value.length === 0) {
        alert('Tempelkan setidaknya 1 NIM pada kotak teks!');
        return;
    }

    if (!confirm(`Import ${parsedPastedNims.value.length} NIM mahasiswa ke daftar wisudawan pada periode ini?`)) {
        return;
    }

    form.nim = [];
    form.nim_list_text = pasteNimsText.value;
    form.tgl_dari = filterTglDari.value;
    form.tgl_sampai = filterTglSampai.value;
    form.ignore_date = '1';
    form.post(route('admin.sync-simanta.import'), {
        onSuccess: () => {
            pasteNimsText.value = '';
        }
    });
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
                    <span class="text-2xl">📥</span> Import List Wisudawan Resmi
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Tentukan daftar mahasiswa yang berhak mengikuti wisuda pada periode tertentu dan membuka akses login portal mereka.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-xs font-bold rounded-xl flex items-center gap-1.5">
                    🎯 Periode Aktif: {{ activePeriode?.nama_periode || 'Belum Ditentukan' }}
                </span>
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

        <!-- Error summary -->
        <div v-if="$page.props.flash?.import_errors?.length" class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl">
            <p class="font-bold mb-1">⚠️ Catatan Hasil Import:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                <li v-for="(err, idx) in $page.props.flash.import_errors" :key="idx">{{ err }}</li>
            </ul>
        </div>

        <!-- Global Setting Card (Target Periode & Akun) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 mb-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                <span>⚙️</span> Pengaturan Periode Wisuda Tujuan & Akun Login
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Periode Wisuda Tujuan</label>
                    <select
                        v-model="form.periode_wisuda_id"
                        class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none"
                    >
                        <option value="">-- Pilih Periode Wisuda --</option>
                        <option v-for="p in periodes" :key="p.id" :value="p.id">
                            {{ p.nama_periode }} ({{ p.tahun_akademik }}) {{ p.is_active ? '[★ AKTIF]' : '' }}
                        </option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Data wisudawan akan disimpan terkunci pada periode ini.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Opsi Pembuatan Akun Login Wisudawan</label>
                    <select
                        v-model="form.auto_create_user"
                        class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none"
                    >
                        <option value="1">Buat / Hubungkan Akun Otomatis (Login: NIM, Password: NIM / SIAKAD)</option>
                        <option value="0">Hanya Buat Record Data Wisudawan (Tanpa Akun User)</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Mahasiswa hanya bisa login jika sudah masuk di periode aktif ini.</p>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 mb-6 pb-2">
            <button
                @click="activeTab = 'simanta'"
                :class="activeTab === 'simanta' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-500/20' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium'"
                class="px-4 py-2 text-xs rounded-xl transition flex items-center gap-2"
            >
                <span>🔍</span> 1. Pilih dari Sinkronisasi SIMANTA ({{ candidates?.length ?? 0 }} Siap)
            </button>
            <button
                @click="activeTab = 'whitelist'"
                :class="activeTab === 'whitelist' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-500/20' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium'"
                class="px-4 py-2 text-xs rounded-xl transition flex items-center gap-2"
            >
                <span>📋</span> 2. Tempel Daftar NIM (NIM Whitelist / SK BAAK)
            </button>
        </div>

        <!-- TAB 1: Pilih dari SIMANTA Cache -->
        <div v-if="activeTab === 'simanta'" class="space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Filter Program Studi</label>
                        <select v-model="filterProdi" @change="applyFilter" class="w-full text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 outline-none">
                            <option value="">-- Semua Program Studi --</option>
                            <option v-for="p in programStudis" :key="p.id" :value="p.kode_prodi">{{ p.nama_prodi }} ({{ p.kode_prodi }})</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Filter Tanggal Pendadaran Dari - Sampai</label>
                        <div class="flex items-center gap-1">
                            <input type="date" v-model="filterTglDari" class="w-1/2 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-2 py-2 outline-none" />
                            <span class="text-xs text-gray-400">-</span>
                            <input type="date" v-model="filterTglSampai" class="w-1/2 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-2 py-2 outline-none" />
                        </div>
                    </div>
                    <div>
                        <button
                            @click="submitImportSimanta"
                            :disabled="form.processing || selectedNims.length === 0"
                            class="w-full flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-xs font-bold rounded-xl shadow transition"
                        >
                            <span>🚀</span> Import {{ selectedNims.length }} Mahasiswa Terpilih
                        </button>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300 cursor-pointer font-medium">
                        <input type="checkbox" v-model="filterIgnoreDate" @change="applyFilter" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span>Abaikan filter rentang tanggal (Tampilkan semua mahasiswa lulus belum terdaftar)</span>
                    </label>

                    <button @click="applyFilter" class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-100 font-semibold transition">
                        🔍 Terapkan Filter
                    </button>
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
                                        <p v-if="totalUnimported > 0" class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 p-3 rounded-xl border border-amber-200">
                                            💡 Terdapat <strong>{{ totalUnimported }}</strong> mahasiswa lulus yang tersimpan di cache lokal, namun tanggal pendadaran mereka berada di luar rentang.
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
        </div>

        <!-- TAB 2: Tempel Daftar NIM (NIM Whitelist) -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm space-y-5">
            <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                <h2 class="font-extrabold text-base text-gray-900 dark:text-white flex items-center gap-2">
                    <span>📋</span> Tempel Daftar NIM Resmi (Whitelist SK Yudisium BAAK)
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Cocok jika Anda memiliki daftar NIM mahasiswa dari SK Kelulusan / Excel BAAK. Salin kolom NIM dan tempelkan langsung di bawah ini.
                </p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 dark:text-gray-300 mb-1.5">
                    Daftar NIM (Pisahkan dengan baris baru, spasi, atau koma)
                </label>
                <textarea
                    v-model="pasteNimsText"
                    rows="8"
                    placeholder="Contoh:&#10;D23098&#10;D23099&#10;B22001&#10;B22002"
                    class="w-full text-sm font-mono border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none"
                ></textarea>
                
                <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                    <div>
                        Terdeteksi <strong>{{ parsedPastedNims.length }}</strong> NIM unik valid.
                    </div>
                    <button v-if="pasteNimsText" @click="pasteNimsText = ''" type="button" class="text-red-500 hover:underline">
                        Bersihkan Kotak
                    </button>
                </div>
            </div>

            <div v-if="parsedPastedNims.length > 0" class="p-3 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 rounded-xl">
                <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-300 block mb-1">Preview NIM Terdeteksi:</span>
                <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                    <span v-for="nim in parsedPastedNims" :key="nim" class="px-2 py-0.5 bg-white dark:bg-gray-800 text-[10px] font-mono font-bold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-700 rounded">
                        {{ nim }}
                    </span>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                <button
                    @click="submitImportWhitelist"
                    :disabled="form.processing || parsedPastedNims.length === 0"
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-extrabold rounded-xl shadow-lg transition"
                >
                    🚀 Import {{ parsedPastedNims.length }} Mahasiswa ke Periode Ini
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
