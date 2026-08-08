<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    periodes: Array,
    selectedPeriodeId: Number,
    programStudis: Array,
    wisudawans: Array,
    filters: Object,
});

const selectedPeriode = ref(props.selectedPeriodeId || '');
const selectedProdi = ref(props.filters.program_studi_id || '');

const filterYearbook = () => {
    router.get(route('admin.buku-kenangan.index'), {
        periode_id: selectedPeriode.value,
        program_studi_id: selectedProdi.value,
    }, { preserveState: true, replace: true });
};

const downloadPdf = () => {
    const url = route('admin.buku-kenangan.export', {
        periode_id: selectedPeriode.value,
        program_studi_id: selectedProdi.value,
    });
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Penyusunan Buku Kenangan Wisuda" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Daftar Wisudawan & Buku Kenangan</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Kompilasi profil wisudawan terverifikasi, IPK, Judul TA, dan fitur cetak dokumen Buku Kenangan PDF.
                    </p>
                </div>

                <button
                    @click="downloadPdf"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export PDF Buku Kenangan</span>
                </button>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
                    <div>
                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase block mb-1">Periode Wisuda:</label>
                        <select v-model="selectedPeriode" @change="filterYearbook" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs font-medium">
                            <option v-for="p in periodes" :key="p.id" :value="p.id">{{ p.nama_periode }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase block mb-1">Program Studi:</label>
                        <select v-model="selectedProdi" @change="filterYearbook" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs font-medium">
                            <option value="">Semua Program Studi</option>
                            <option v-for="ps in programStudis" :key="ps.id" :value="ps.id">{{ ps.nama_prodi }} ({{ ps.jenjang }})</option>
                        </select>
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-3 py-1.5 rounded-xl border border-indigo-200 dark:border-indigo-800 inline-block">
                        Total: {{ wisudawans ? wisudawans.length : 0 }} Wisudawan Terverifikasi
                    </span>
                </div>
            </div>

            <!-- Preview Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="w in wisudawans"
                    :key="w.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm space-y-3 hover:border-indigo-200 dark:hover:border-indigo-800 transition"
                >
                    <div class="flex items-start gap-4">
                        <div class="w-[60px] h-[80px] rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shrink-0 shadow-sm">
                            <img v-if="w.pas_foto" :src="`/storage/${w.pas_foto}`" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-[10px] font-bold">No Foto</div>
                        </div>
                        <div class="space-y-1 min-w-0 flex-1">
                            <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ w.nim }}</span>
                            <h4 class="font-bold text-gray-900 dark:text-white text-sm leading-snug truncate">{{ w.nama_lengkap }}{{ w.gelar ? `, ${w.gelar}` : '' }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                {{ w.program_studi?.nama_prodi }}
                            </p>
                            <div class="flex items-center gap-2 pt-0.5">
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">IPK {{ w.ipk }}</span>
                                <span v-if="Number(w.ipk) >= 3.51" class="text-[10px] bg-amber-500 text-gray-950 font-black px-2 py-0.5 rounded-full uppercase">Cumlaude</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs pt-3 border-t border-gray-100 dark:border-gray-700">
                        <span class="font-bold text-gray-400 dark:text-gray-400 uppercase text-[10px] block mb-0.5">Judul Tugas Akhir:</span>
                        <p class="text-gray-700 dark:text-gray-300 font-medium italic line-clamp-2">"{{ w.judul_ta }}"</p>
                    </div>
                </div>
            </div>

            <div v-if="!wisudawans || wisudawans.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center text-gray-400 text-xs border border-gray-100 dark:border-gray-700">
                Belum ada wisudawan terverifikasi untuk periode ini.
            </div>

        </div>
    </AuthenticatedLayout>
</template>
