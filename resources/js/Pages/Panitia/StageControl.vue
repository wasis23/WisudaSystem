<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    activePeriode: Object,
    wisudawans: Array,
    initialIndex: Number,
});

const activeIndex = ref(props.initialIndex || 0);

let stageChannel = null;

onMounted(() => {
    if ('BroadcastChannel' in window) {
        stageChannel = new BroadcastChannel('wisuda_stage_channel');
    }
});

onUnmounted(() => {
    if (stageChannel) {
        stageChannel.close();
    }
});

const broadcastActiveCandidate = (idx) => {
    if (!props.wisudawans || !props.wisudawans[idx]) return;
    const candidate = props.wisudawans[idx];

    // 1. BroadcastChannel (Real-time Instant same browser/tabs)
    if (stageChannel) {
        stageChannel.postMessage({
            type: 'CHANGE_CANDIDATE',
            index: idx,
            id: candidate.id,
        });
    }

    // 2. localStorage Event Trigger
    try {
        localStorage.setItem('wisuda_active_stage_index', idx);
        localStorage.setItem('wisuda_active_stage_id', candidate.id);
    } catch (e) {}

    // 3. Backend API Persist & Cross-Browser Polling
    axios.post(route('panitia.stage-control.set-active'), {
        wisudawan_id: candidate.id,
        index: idx,
    }).catch(() => {});
};

const selectCandidate = (idx) => {
    activeIndex.value = idx;
    broadcastActiveCandidate(idx);
};

const nextCandidate = () => {
    if (props.wisudawans && activeIndex.value < props.wisudawans.length - 1) {
        activeIndex.value++;
        broadcastActiveCandidate(activeIndex.value);
    }
};

const prevCandidate = () => {
    if (activeIndex.value > 0) {
        activeIndex.value--;
        broadcastActiveCandidate(activeIndex.value);
    }
};

const openStageDisplay = () => {
    window.open(route('panitia.stage-display'), '_blank');
};
</script>

<template>
    <Head title="Operator Stage Control Console" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- Page Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                        <span>Konsol Operator Layar Panggung</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Pilih nama wisudawan atau tekan tombol Next/Prev untuk langsung mengontrol tampilan layar panggung (Stage Display) secara real-time.
                    </p>
                </div>

                <button
                    @click="openStageDisplay"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Buka Layar Presentasi ➔</span>
                </button>
            </div>

            <!-- Active Candidate Preview Banner -->
            <div v-if="wisudawans && wisudawans.length > 0 && wisudawans[activeIndex]" class="bg-slate-900 text-white rounded-2xl p-6 shadow-md border border-slate-800 flex flex-col md:flex-row items-center gap-6">
                <div class="w-24 h-32 rounded-xl overflow-hidden bg-slate-800 border border-slate-700 shrink-0 shadow-lg">
                    <img v-if="wisudawans[activeIndex].pas_foto" :src="`/storage/${wisudawans[activeIndex].pas_foto}`" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-slate-500 text-xs font-bold">No Foto</div>
                </div>

                <div class="space-y-2 flex-1 text-center md:text-left">
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full font-mono text-[11px] font-bold uppercase tracking-wider inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Sedang Tampil Di Layar Panggung
                    </span>
                    <h3 class="text-2xl font-black tracking-tight">{{ wisudawans[activeIndex].nama_lengkap }}{{ wisudawans[activeIndex].gelar ? `, ${wisudawans[activeIndex].gelar}` : '' }}</h3>
                    <p class="text-slate-400 text-xs font-mono">
                        NIM: {{ wisudawans[activeIndex].nim }} • {{ wisudawans[activeIndex].program_studi?.nama_prodi }} • IPK: {{ wisudawans[activeIndex].ipk }}
                    </p>
                    <p class="text-xs text-slate-300 italic max-w-xl">"{{ wisudawans[activeIndex].judul_ta }}"</p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="prevCandidate"
                        :disabled="activeIndex === 0"
                        class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 disabled:opacity-40 rounded-xl font-bold text-xs transition border border-slate-700"
                    >
                        ◀ Prev
                    </button>
                    <button
                        @click="nextCandidate"
                        :disabled="activeIndex === wisudawans.length - 1"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl font-bold text-xs transition shadow-sm"
                    >
                        Next Candidate ▶
                    </button>
                </div>
            </div>

            <!-- Candidates Queue List Grid (Interactive Clickable) -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 002-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Daftar Antrean Wisudawan ({{ wisudawans?.length || 0 }})</span>
                        </h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Klik pada salah satu nama wisudawan di bawah ini untuk menampilkan datanya di proyektor panggung secara instan.</p>
                    </div>
                </div>

                <div v-if="wisudawans && wisudawans.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div
                        v-for="(w, idx) in wisudawans"
                        :key="w.id"
                        @click="selectCandidate(idx)"
                        :class="[
                            'p-4 rounded-xl border cursor-pointer transition flex items-center gap-3 select-none transform hover:scale-[1.01]',
                            activeIndex === idx
                                ? 'bg-indigo-50 border-indigo-500 dark:bg-indigo-950/50 dark:border-indigo-500 shadow-md ring-2 ring-indigo-500/20'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700'
                        ]"
                    >
                        <span :class="[
                            'w-8 h-8 rounded-lg font-bold text-xs flex items-center justify-center shrink-0 font-mono shadow-sm transition',
                            activeIndex === idx ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                        ]">
                            {{ idx + 1 }}
                        </span>

                        <div class="overflow-hidden min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <h4 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ w.nama_lengkap }}</h4>
                                <span v-if="activeIndex === idx" class="text-[10px] bg-emerald-500 text-white font-extrabold px-2 py-0.5 rounded-full shrink-0">TAMPIL</span>
                            </div>
                            <p class="text-[11px] text-gray-500 font-mono truncate mt-0.5">{{ w.nim }} • {{ w.program_studi?.nama_prodi || 'Prodi' }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="py-8 text-center text-gray-400 text-xs">
                    Belum ada wisudawan terdaftar dalam antrean periode wisuda ini.
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
