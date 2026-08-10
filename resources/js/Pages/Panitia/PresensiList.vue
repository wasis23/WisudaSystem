<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PanitiaLayout from '@/Layouts/PanitiaLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    periodes: Array,
    selectedPeriodeId: Number,
    programStudis: Array,
    wisudawans: [Object, Array],
    counts: Object,
    filters: Object,
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const searchInput = ref(props.filters?.search || '');
const selectedProdi = ref(props.filters?.program_studi_id || '');
const currentStatus = ref(props.filters?.status || '');

const wisudawanList = computed(() => {
    return Array.isArray(props.wisudawans) ? props.wisudawans : (props.wisudawans?.data || []);
});

const filterWisudawan = () => {
    const routeName = props.isAdmin ? 'admin.monitoring-presensi' : 'panitia.presensi.wisudawan';
    router.get(
        route(routeName),
        {
            periode_id: props.selectedPeriodeId,
            program_studi_id: selectedProdi.value,
            status: currentStatus.value,
            search: searchInput.value,
        },
        { preserveState: true, replace: true }
    );
};

const setStatusTab = (status) => {
    currentStatus.value = status;
    filterWisudawan();
};
</script>

<template>
    <Head title="Data Presensi Wisudawan" />

    <component :is="isAdmin ? AdminLayout : PanitiaLayout">
        <div class="space-y-6">
            
            <!-- Page Header Card & Sub-Navigation Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Data Presensi Wisudawan</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Informasi lengkap wisudawan terverifikasi (Belum Hadir, Hadir Gate, dan Masuk Auditorium).
                    </p>
                </div>

                <!-- Sub-Navigation Switcher Tabs (Panitia View Only) -->
                <div v-if="!isAdmin" class="flex items-center bg-gray-100 dark:bg-gray-700/60 p-1 rounded-xl shrink-0">
                    <Link
                        :href="route('panitia.presensi')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white flex items-center gap-1.5"
                    >
                        <span>📷 Gate Scanner</span>
                    </Link>
                    <Link
                        :href="route('panitia.presensi.wisudawan')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm flex items-center gap-1.5"
                    >
                        <span>📋 Data Wisudawan</span>
                    </Link>
                </div>
            </div>

            <!-- Flash Message Alerts -->
            <div v-if="flashSuccess" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-bold text-xs flex items-center gap-3">
                <span class="text-lg">✅</span>
                <div>{{ flashSuccess }}</div>
            </div>

            <!-- STAT CARDS ROW -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- BELUM HADIR CARD -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-amber-200 dark:border-amber-950/80 p-6 transition hover:shadow-md">
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">BELUM HADIR</span>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl font-black text-amber-600 dark:text-amber-400 tracking-tight">{{ counts?.belum_hadir || 0 }}</span>
                        <span class="text-sm font-medium text-amber-600/70">Standby</span>
                    </div>
                </div>

                <!-- HADIR (GATE IN) CARD -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-indigo-200 dark:border-indigo-950/80 p-6 transition hover:shadow-md">
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">HADIR (GATE IN)</span>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">{{ counts?.hadir || 0 }}</span>
                        <span class="text-sm font-medium text-indigo-600/70">Gate Main</span>
                    </div>
                </div>

                <!-- MASUK AUDITORIUM CARD -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-emerald-200 dark:border-emerald-950/80 p-6 transition hover:shadow-md">
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">MASUK AUDITORIUM</span>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ counts?.in_auditorium || 0 }}</span>
                        <span class="text-sm font-medium text-emerald-600/70">Auditorium</span>
                    </div>
                </div>

            </div>

            <!-- Attendance Status Filter Tabs Bar -->
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                <button
                    @click="setStatusTab('')"
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                    :class="!currentStatus ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <span>Semua Wisudawan</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="!currentStatus ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'">{{ counts?.total || 0 }}</span>
                </button>

                <button
                    @click="setStatusTab('belum_hadir')"
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                    :class="currentStatus === 'belum_hadir' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <span>⚠️ Belum Hadir</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="currentStatus === 'belum_hadir' ? 'bg-amber-500 text-white' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400'">{{ counts?.belum_hadir || 0 }}</span>
                </button>

                <button
                    @click="setStatusTab('hadir')"
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                    :class="currentStatus === 'hadir' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <span>🚪 Hadir Gate In</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="currentStatus === 'hadir' ? 'bg-indigo-500 text-white' : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400'">{{ counts?.hadir || 0 }}</span>
                </button>

                <button
                    @click="setStatusTab('in_auditorium')"
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                    :class="currentStatus === 'in_auditorium' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <span>🏛 Masuk Auditorium</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="currentStatus === 'in_auditorium' ? 'bg-emerald-500 text-white' : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'">{{ counts?.in_auditorium || 0 }}</span>
                </button>
            </div>

            <!-- Filters Bar Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="w-full md:w-auto flex flex-col md:flex-row items-center gap-3">
                    <div class="w-full md:w-64">
                        <select
                            v-model="selectedProdi"
                            @change="filterWisudawan"
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-medium text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">-- Semua Program Studi --</option>
                            <option v-for="p in programStudis" :key="p.id" :value="p.id">{{ p.nama_prodi }}</option>
                        </select>
                    </div>
                </div>

                <div class="w-full md:w-72 relative">
                    <input
                        v-model="searchInput"
                        @keyup.enter="filterWisudawan"
                        type="text"
                        placeholder="Cari Nama / NIM..."
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 pl-9 pr-4 py-2 text-xs text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Wisudawan Data Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4">Wisudawan</th>
                                <th class="py-3.5 px-4">Program Studi</th>
                                <th class="py-3.5 px-4">Status Gate In</th>
                                <th class="py-3.5 px-4">Status Auditorium</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            <tr v-for="w in wisudawanList" :key="w.id" class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                
                                <!-- Wisudawan Info -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="w.pas_foto ? `/storage/${w.pas_foto}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(w.nama_lengkap)}&background=6366f1&color=fff`"
                                            class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-gray-700 shrink-0"
                                            alt="Foto"
                                        />
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ w.nama_lengkap }}</p>
                                            <p class="font-mono text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">{{ w.nim }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Program Studi -->
                                <td class="py-3.5 px-4">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ w.program_studi?.nama_prodi }}</span>
                                </td>

                                <!-- Status Gate -->
                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="w.is_hadir"
                                        class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800 text-[10px] font-bold rounded-full inline-flex items-center gap-1"
                                    >
                                        ✓ Gate In
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800 text-[10px] font-bold rounded-full inline-flex items-center gap-1"
                                    >
                                        ⏱ Belum Hadir
                                    </span>
                                </td>

                                <!-- Status Auditorium -->
                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="w.is_in_auditorium"
                                        class="px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800 text-[10px] font-bold rounded-full inline-flex items-center gap-1"
                                    >
                                        🏛 Di Dalam Auditorium
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 text-[10px] font-bold rounded-full inline-flex items-center gap-1"
                                    >
                                        🚪 Belum Masuk
                                    </span>
                                </td>

                            </tr>

                            <tr v-if="!wisudawanList || wisudawanList.length === 0">
                                <td colspan="4" class="py-12 text-center text-gray-400 text-xs">
                                    Tidak ada data wisudawan dengan kriteria filter ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="wisudawans?.last_page > 1" class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <div>
                        Menampilkan <strong>{{ wisudawans.from || 0 }}</strong> - <strong>{{ wisudawans.to || 0 }}</strong> dari <strong>{{ wisudawans.total || 0 }}</strong> wisudawan (50 per halaman)
                    </div>
                    <div class="flex items-center gap-1 flex-wrap">
                        <Link
                            v-for="(link, idx) in wisudawans.links"
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
                                link.active
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : link.url
                                        ? 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600'
                                        : 'text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-50'
                            ]"
                            :preserve-state="true"
                            :preserve-scroll="true"
                        />
                    </div>
                </div>
            </div>

        </div>
    </component>
</template>
