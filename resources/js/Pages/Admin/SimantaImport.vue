<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    candidates: Array,
    activePeriode: Object,
    programStudis: Array,
    filter: Object,
});

const selectedNims = ref(props.candidates ? props.candidates.map(c => c.nim) : []);
const selectAll = ref(true);

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedNims.value = props.candidates ? props.candidates.map(c => c.nim) : [];
    } else {
        selectedNims.value = [];
    }
};

const form = useForm({
    periode_wisuda_id: props.activePeriode?.id || '',
    nim: [],
    tgl_dari: props.filter?.tgl_dari || '',
    tgl_sampai: props.filter?.tgl_sampai || '',
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
                    <Link :href="route('admin.sync-simanta.index')" class="text-xs text-indigo-600 hover:underline">
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

        <!-- Setting Options Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 mb-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-3">⚙️ Pengaturan Target Import</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
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
        </div>

        <!-- Table Candidates -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-800 dark:text-white text-sm flex items-center gap-2">
                    📋 Mahasiswa Siap Import ({{ candidates?.length ?? 0 }})
                </h2>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5 cursor-pointer">
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
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                Tidak ada mahasiswa yang siap di-import. Semua data lulusan mungkin sudah terdaftar di sistem wisuda.
                            </td>
                        </tr>
                        <tr v-for="c in candidates" :key="c.nim" class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
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
