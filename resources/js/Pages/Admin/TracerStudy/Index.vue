<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    responses: Object,
    filters: Object,
    stats: Object,
    prodiList: Array,
});

const search = ref(props.filters?.search || '');
const selectedProdi = ref(props.filters?.prodi || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedDetail = ref(null);
const showDetailModal = ref(false);

const applyFilters = () => {
    router.get(
        route('admin.tracer-study.index'),
        {
            search: search.value,
            prodi: selectedProdi.value,
            status: selectedStatus.value,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedProdi.value = '';
    selectedStatus.value = '';
    applyFilters();
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const openDetail = (item) => {
    selectedDetail.value = item;
    showDetailModal.value = true;
};

const exportCsv = () => {
    const params = new URLSearchParams({
        prodi: selectedProdi.value,
        status: selectedStatus.value,
    });
    window.location.href = route('admin.tracer-study.export') + '?' + params.toString();
};

// Calculate percentages for stats bars
const getStatPercentage = (count) => {
    const total = props.stats?.totalResponden || 1;
    return Math.round((count / total) * 100);
};
</script>

<template>
    <Head title="Monitoring & Laporan Tracer Study - Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- PAGE TITLE & EXPORT BUTTON -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 font-bold text-xs rounded-full">
                            METRICS & ANALYTICS
                        </span>
                        <span class="text-xs text-slate-400">Tracer Study Alumni</span>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                        Monitoring Data Tracer Study
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pantau kuesioner pelacakan karir lulusan dan unduh laporan data lengkap dalam format Excel / CSV.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="exportCsv"
                        class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Download Report Data (CSV / Excel)</span>
                    </button>
                </div>
            </div>

            <!-- TOP ANALYTICS STATS CARDS (4 CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Card 1: Total Responden -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Responden</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-base">
                            
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.totalResponden }}</span>
                        <span class="text-xs font-semibold text-slate-500">Alumni</span>
                    </div>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-2">
                        {{ stats.persentasePartisipasi }}% Partisipasi dari {{ stats.totalWisudawan }} Wisudawan
                    </p>
                </div>

                <!-- Card 2: Alumni Bekerja -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bekerja</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-base">
                            
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ stats.byStatus['Bekerja (full time/part time)'] || 0 }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500">Orang</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        {{ getStatPercentage(stats.byStatus['Bekerja (full time/part time)'] || 0) }}% dari total responden
                    </p>
                </div>

                <!-- Card 3: Alumni Berwirausaha -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Berwirausaha</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-base">
                            
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ stats.byStatus['Berwirausaha'] || 0 }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500">Orang</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        {{ getStatPercentage(stats.byStatus['Berwirausaha'] || 0) }}% dari total responden
                    </p>
                </div>

                <!-- Card 4: Studi Lanjut -->
                <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Studi Lanjut</span>
                        <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-base">
                            
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ stats.byStatus['Melanjutkan Pendidikan'] || 0 }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500">Orang</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        {{ getStatPercentage(stats.byStatus['Melanjutkan Pendidikan'] || 0) }}% dari total responden
                    </p>
                </div>
            </div>

            <!-- ANALYTICS BREAKDOWN (DISTRIBUSI STATUS & RENTANG GAJI) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status Karir Breakdown -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
                    <h3 class="font-extrabold text-sm text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>Distribusi Status Karir Alumni</span>
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(count, statusName) in stats.byStatus" :key="statusName" class="space-y-1">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="text-slate-700 dark:text-slate-300">{{ statusName }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ count }} ({{ getStatPercentage(count) }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" :style="{ width: `${getStatPercentage(count)}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rentang Gaji Breakdown -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
                    <h3 class="font-extrabold text-sm text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>Rentang Gaji per Bulan</span>
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(count, gajiRange) in stats.byGaji" :key="gajiRange" class="space-y-1">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="text-slate-700 dark:text-slate-300">{{ gajiRange }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ count }} ({{ getStatPercentage(count) }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: `${getStatPercentage(count)}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DATA TABLE SECTION -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <!-- Filters & Search Bar -->
                <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama, NIM, atau email..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <span class="absolute left-3.5 top-3 text-slate-400 text-xs"></span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Filter Prodi -->
                        <select
                            v-model="selectedProdi"
                            @change="applyFilters"
                            class="py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-xs text-slate-900 dark:text-white"
                        >
                            <option value="">Semua Program Studi</option>
                            <option v-for="p in prodiList" :key="p" :value="p">{{ p }}</option>
                        </select>

                        <!-- Filter Status Karir -->
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-xs text-slate-900 dark:text-white"
                        >
                            <option value="">Semua Status Karir</option>
                            <option value="Bekerja (full time/part time)">Bekerja</option>
                            <option value="Berwirausaha">Berwirausaha</option>
                            <option value="Melanjutkan Pendidikan">Studi Lanjut</option>
                            <option value="Tidak bekerja tetapi sedang mencari kerja">Mencari Kerja</option>
                        </select>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="search || selectedProdi || selectedStatus"
                            @click="resetFilters"
                            class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 uppercase font-bold text-slate-500 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-5 py-3.5">NIM & Nama</th>
                                <th class="px-5 py-3.5">Program Studi</th>
                                <th class="px-5 py-3.5">Status Saat Ini</th>
                                <th class="px-5 py-3.5">Perusahaan / Usaha</th>
                                <th class="px-5 py-3.5">Gaji per Bulan</th>
                                <th class="px-5 py-3.5">Tgl Pengisian</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            <tr v-for="item in responses.data" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ item.nama_lengkap }}
                                    </div>
                                    <div class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400">
                                        {{ item.nim }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ item.prodi || 'Unspecified' }}
                                </td>

                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 rounded-full font-bold text-[10px] bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200">
                                        {{ item.status_saat_ini || '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ item.tempat_bekerja || item.nama_perusahaan || item.nama_usaha || '-' }}
                                </td>

                                <td class="px-5 py-4 font-mono font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ item.gaji_per_bulan || item.gaji_usaha || '-' }}
                                </td>

                                <td class="px-5 py-4 text-slate-500 text-[11px]">
                                    {{ new Date(item.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <button
                                        @click="openDetail(item)"
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-lg transition"
                                    >
                                        Detail
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!responses.data?.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <div class="text-3xl mb-2"></div>
                                    <p class="font-bold text-xs">Belum ada data Tracer Study yang sesuai filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div v-if="responses.links && responses.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-center gap-1">
                    <template v-for="(link, key) in responses.links" :key="key">
                        <div
                            v-if="link.url === null"
                            class="px-3 py-1.5 text-xs text-slate-400 rounded-lg"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            :class="['px-3 py-1.5 text-xs rounded-lg transition font-bold', link.active ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200']"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL DETAIL RESPONDEN TRACER STUDY -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedDetail" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col max-h-[90vh] overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Detail Jawaban Tracer Study Alumni</h3>
                            <p class="text-xs text-slate-500">{{ selectedDetail.nama_lengkap }} ({{ selectedDetail.nim }}) - {{ selectedDetail.prodi }}</p>
                        </div>
                        <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-700 text-2xl">&times;</button>
                    </div>

                    <div class="p-6 space-y-6 overflow-y-auto text-xs">
                        <!-- Data Diri & Kontak -->
                        <div class="bg-slate-50 dark:bg-slate-800 p-4 rounded-xl space-y-2 border border-slate-200 dark:border-slate-700">
                            <h4 class="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Identitas Alumni</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div><strong class="text-slate-500">Email:</strong> {{ selectedDetail.email }}</div>
                                <div><strong class="text-slate-500">No. WhatsApp:</strong> {{ selectedDetail.no_whatsapp }}</div>
                                <div><strong class="text-slate-500">Jenis Kelas:</strong> {{ selectedDetail.jenis_kelas }}</div>
                                <div><strong class="text-slate-500">Alamat:</strong> {{ selectedDetail.alamat_lengkap }}</div>
                            </div>
                        </div>

                        <!-- Status Pekerjaan -->
                        <div class="space-y-2">
                            <h4 class="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Status Pekerjaan / Karir</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div><strong class="text-slate-500">Status Saat Ini:</strong> {{ selectedDetail.status_saat_ini }}</div>
                                <div><strong class="text-slate-500">Nama Tempat Bekerja:</strong> {{ selectedDetail.tempat_bekerja || selectedDetail.nama_perusahaan || '-' }}</div>
                                <div><strong class="text-slate-500">Gaji per Bulan:</strong> {{ selectedDetail.gaji_per_bulan || '-' }}</div>
                                <div><strong class="text-slate-500">Keselarasan Pekerjaan:</strong> {{ selectedDetail.keselarasan_pekerjaan || '-' }}</div>
                                <div><strong class="text-slate-500">Kesesuaian Pendidikan:</strong> {{ selectedDetail.kesesuaian_pendidikan || '-' }}</div>
                                <div><strong class="text-slate-500">Waktu Tunggu Lulusan:</strong> {{ selectedDetail.waktu_tunggu || '-' }}</div>
                                <div><strong class="text-slate-500">Jenis Instansi:</strong> {{ selectedDetail.jenis_instansi || '-' }}</div>
                                <div><strong class="text-slate-500">Posisi / Jabatan:</strong> {{ selectedDetail.posisi_jabatan || '-' }}</div>
                            </div>
                        </div>

                        <!-- Studi Lanjut & Sumber Dana -->
                        <div class="space-y-2">
                            <h4 class="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Studi Lanjut & Beasiswa</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div><strong class="text-slate-500">Studi Lanjut:</strong> {{ selectedDetail.studi_lanjut || 'Tidak' }}</div>
                                <div><strong class="text-slate-500">Kampus Studi Lanjut:</strong> {{ selectedDetail.kampus_studi_lanjut || '-' }}</div>
                                <div><strong class="text-slate-500">Sumber Dana Kuliah:</strong> {{ selectedDetail.sumber_dana || '-' }}</div>
                                <div><strong class="text-slate-500">Kepuasan Layanan:</strong> {{ selectedDetail.kepuasan_layanan || '-' }}</div>
                            </div>
                        </div>

                        <!-- Masukan Alumni -->
                        <div class="space-y-2">
                            <h4 class="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Saran & Masukan Alumni</h4>
                            <div class="bg-slate-50 dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 italic">
                                "{{ selectedDetail.saran_masukan || 'Tidak ada masukan.' }}"
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <button @click="showDetailModal = false" class="px-5 py-2 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded-xl text-xs">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
