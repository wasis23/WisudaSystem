<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    activePeriode: Object,
    wisudawans: Array,
    stageConfig: Object,
    initialIndex: Number,
});

const currentIndex = ref(props.initialIndex || 0);
const isFullscreen = ref(false);
const showControls = ref(false);

const currentWisudawan = computed(() => {
    return props.wisudawans[currentIndex.value] || null;
});

const defaultSilhouette = computed(() => {
    if (props.activePeriode?.buku_kenangan_default_foto && props.activePeriode.buku_kenangan_default_foto !== '0') {
        return `/storage/${props.activePeriode.buku_kenangan_default_foto}`;
    }
    return '/images/default_toga_silhouette.png';
});

const setCandidateIndex = (newIndex) => {
    if (newIndex >= 0 && newIndex < props.wisudawans.length) {
        currentIndex.value = newIndex;
    }
};

const setCandidateById = (wisudawanId) => {
    const foundIdx = props.wisudawans.findIndex(w => w.id == wisudawanId);
    if (foundIdx !== -1) {
        currentIndex.value = foundIdx;
    }
};

const nextCandidate = () => {
    if (currentIndex.value < props.wisudawans.length - 1) {
        setCandidateIndex(currentIndex.value + 1);
    }
};

const prevCandidate = () => {
    if (currentIndex.value > 0) {
        setCandidateIndex(currentIndex.value - 1);
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'ArrowRight' || e.key === ' ') {
        e.preventDefault();
        nextCandidate();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        prevCandidate();
    } else if (e.key === 'f' || e.key === 'F') {
        toggleFullscreen();
    }
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
            isFullscreen.value = false;
        }
    }
};

// Auto-scale 1080x1980 canvas calculation for full projector screen (Portrait)
const stageWrapperRef = ref(null);
const scaleFactor = ref(1);

const updateScale = () => {
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    
    const scaleX = screenWidth / 1080;
    const scaleY = screenHeight / 1980;
    
    scaleFactor.value = Math.min(scaleX, scaleY);
};

let hideControlsTimer = null;
const handleMouseMove = () => {
    showControls.value = true;
    clearTimeout(hideControlsTimer);
    hideControlsTimer = setTimeout(() => {
        showControls.value = false;
    }, 3000);
};

// Real-time synchronization listeners across tabs/windows/browsers
let stageChannel = null;
let pollTimer = null;

const handleStorageEvent = (e) => {
    if (e.key === 'wisuda_active_stage_index' && e.newValue !== null) {
        setCandidateIndex(parseInt(e.newValue, 10));
    } else if (e.key === 'wisuda_active_stage_id' && e.newValue !== null) {
        setCandidateById(e.newValue);
    }
};

const syncFromBackend = () => {
    axios.get(route('panitia.stage-display.get-active'))
        .then(res => {
            if (res.data?.active_wisudawan_id) {
                setCandidateById(res.data.active_wisudawan_id);
            } else if (res.data?.index !== undefined) {
                setCandidateIndex(res.data.index);
            }
        })
        .catch(() => {});
};

onMounted(() => {
    updateScale();
    window.addEventListener('resize', updateScale);
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('storage', handleStorageEvent);

    // 1. BroadcastChannel API for instant < 1ms same-browser sync
    if ('BroadcastChannel' in window) {
        stageChannel = new BroadcastChannel('wisuda_stage_channel');
        stageChannel.onmessage = (e) => {
            if (e.data?.type === 'CHANGE_CANDIDATE') {
                if (e.data.index !== undefined) {
                    setCandidateIndex(e.data.index);
                } else if (e.data.id) {
                    setCandidateById(e.data.id);
                }
            }
        };
    }

    // 2. Poll Backend state every 1.5s for cross-browser / separate window sync
    pollTimer = setInterval(syncFromBackend, 1500);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScale);
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('storage', handleStorageEvent);
    clearTimeout(hideControlsTimer);
    
    if (stageChannel) {
        stageChannel.close();
    }
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <Head :title="`Stage Projection - ${currentWisudawan?.nama_lengkap || 'Wisuda'}`" />

    <div
        ref="stageWrapperRef"
        @dblclick="toggleFullscreen"
        class="w-screen h-screen bg-slate-950 text-white overflow-hidden relative select-none font-sans flex items-center justify-center cursor-none"
        :class="{ 'cursor-default': showControls }"
    >
        <!-- Fullscreen Canvas Box 1080x1980 (Portrait) Scaled to exact screen dimensions -->
        <div
            class="relative w-[1080px] h-[1980px] shrink-0 overflow-hidden shadow-2xl origin-center"
            :style="{ transform: `scale(${scaleFactor})` }"
        >
            <!-- Background Image uploaded by Admin or default background.png -->
            <img
                v-if="stageConfig?.bg_image"
                :src="`/storage/${stageConfig.bg_image}`"
                class="absolute inset-0 w-full h-full object-cover z-0"
            />
            <img
                v-else
                src="/background.png"
                class="absolute inset-0 w-full h-full object-cover z-0"
            />

            <!-- Decorative Gradient Fallback if background image fails to load -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-950 via-slate-950 to-purple-950 z-[-1]">
                <div class="absolute top-10 left-10 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
            </div>

            <!-- Main Wisudawan Projection Content -->
            <template v-if="currentWisudawan">
                <div class="relative z-10 w-full h-full">
                    
                    <!-- Nama Lengkap Wisudawan & Gelar (Bottom-Anchored: Baris paling bawah/default tepat di atas foto, jika > 1 baris tumbuh ke atas) -->
                    <div
                        :style="{
                            left: (stageConfig?.nama_x || 40) + 'px',
                            top: (stageConfig?.nama_y || 440) + 'px',
                            width: (1080 - 2 * (stageConfig?.nama_x || 40)) + 'px',
                            transform: 'translateY(-100%)',
                        }"
                        class="absolute flex flex-col items-center pointer-events-none"
                    >
                        <div
                            :style="{
                                fontSize: (stageConfig?.nama_font_size || 44) + 'px',
                            }"
                            class="w-full text-center font-black tracking-tight leading-tight uppercase drop-shadow-[0_4px_14px_rgba(245,158,11,0.6)]"
                            style="color: #FCD34D; text-shadow: 0 0 20px rgba(251, 191, 36, 0.5), 0 2px 4px rgba(0, 0, 0, 0.8);"
                        >
                            {{ currentWisudawan.nama_lengkap }}{{ currentWisudawan.gelar ? `, ${currentWisudawan.gelar}` : '' }}
                        </div>
                    </div>

                    <!-- Pas Foto Wisudawan (Foto Memenuhi Frame Tanpa Garis Hitam) -->
                    <div
                        :style="{
                            left: (stageConfig?.photo_x || 300) + 'px',
                            top: (stageConfig?.photo_y || 470) + 'px',
                            width: (stageConfig?.photo_w || 480) + 'px',
                            height: (stageConfig?.photo_h || 600) + 'px',
                        }"
                        class="absolute border-4 border-amber-400/60 bg-slate-900 rounded-3xl overflow-hidden shadow-2xl flex items-center justify-center"
                    >
                        <img
                            v-if="currentWisudawan.pas_foto"
                            :src="`/storage/${currentWisudawan.pas_foto}`"
                            class="w-full h-full object-cover object-top"
                            @error="$event.target.src = defaultSilhouette"
                        />
                        <img
                            v-else
                            :src="defaultSilhouette"
                            alt="Siluet Wisudawan"
                            class="w-full h-full object-cover object-top"
                        />
                    </div>

                    <!-- Tabel Informasi Wisudawan (Font Lebih Besar & Judul TA Tidak Terpotong) -->
                    <div
                        :style="{
                            left: (stageConfig?.nim_x || 80) + 'px',
                            top: (stageConfig?.nim_y || 1100) + 'px',
                            width: (stageConfig?.ta_max_w || 920) + 'px',
                        }"
                        class="absolute bg-slate-950/75 border border-amber-500/40 rounded-3xl p-7 backdrop-blur-md shadow-2xl"
                    >
                        <table class="w-full text-left border-collapse">
                            <tbody>
                                <!-- Baris NIM -->
                                <tr class="border-b border-white/10">
                                    <td class="py-4 px-4 text-amber-300 font-extrabold uppercase tracking-wider text-2xl w-64 shrink-0">
                                        NIM
                                    </td>
                                    <td class="py-4 px-2 text-amber-400 font-black text-2xl w-8">:</td>
                                    <td class="py-4 px-4 font-mono font-black text-3xl text-white tracking-widest">
                                        {{ currentWisudawan.nim }}
                                    </td>
                                </tr>

                                <!-- Baris Program Studi -->
                                <tr class="border-b border-white/10">
                                    <td class="py-4 px-4 text-amber-300 font-extrabold uppercase tracking-wider text-2xl">
                                        Program Studi
                                    </td>
                                    <td class="py-4 px-2 text-amber-400 font-black text-2xl">:</td>
                                    <td class="py-4 px-4 font-extrabold text-3xl text-slate-100">
                                        {{ currentWisudawan.program_studi?.nama_prodi }}
                                    </td>
                                </tr>

                                <!-- Baris IPK & Predikat -->
                                <tr class="border-b border-white/10">
                                    <td class="py-4 px-4 text-amber-300 font-extrabold uppercase tracking-wider text-2xl">
                                        IPK
                                    </td>
                                    <td class="py-4 px-2 text-amber-400 font-black text-2xl">:</td>
                                    <td class="py-4 px-4 font-mono font-black text-3xl text-emerald-400 flex items-center gap-5">
                                        <span>{{ (currentWisudawan.ipk && Number(currentWisudawan.ipk) > 0) ? currentWisudawan.ipk : '-' }}</span>
                                        <span
                                            v-if="currentWisudawan.predikat_kelulusan === 'Dengan Pujian (Cumlaude)' || (currentWisudawan.predikat_kelulusan && currentWisudawan.predikat_kelulusan.toLowerCase().includes('cumlaude'))"
                                            class="px-5 py-1.5 bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black text-base uppercase tracking-wider rounded-full shadow-lg"
                                        >
                                            Cumlaude 
                                        </span>
                                    </td>
                                </tr>

                                <!-- Baris Judul TA / Skripsi (Full Multi-Line Tanpa Terpotong) -->
                                <tr v-if="currentWisudawan.judul_ta">
                                    <td class="py-4 px-4 text-amber-300 font-extrabold uppercase tracking-wider text-2xl align-top">
                                        Judul Tugas Akhir
                                    </td>
                                    <td class="py-4 px-2 text-amber-400 font-black text-2xl align-top">:</td>
                                    <td class="py-4 px-4 font-semibold italic text-2xl text-slate-100 leading-snug">
                                        "{{ currentWisudawan.judul_ta }}"
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </template>

            <!-- Blank State if no candidate -->
            <div v-else class="relative z-10 w-full h-full flex items-center justify-center text-slate-400 font-bold text-2xl">
                Tidak ada wisudawan terdaftar untuk proyeksi panggung.
            </div>
        </div>

        <!-- Floating Auto-Hide Operator Quick Toolbar (Only appears on mouse hover/move) -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div
                v-if="showControls"
                class="fixed bottom-6 right-6 z-50 bg-slate-900/90 border border-white/20 backdrop-blur-md px-4 py-2.5 rounded-2xl shadow-2xl flex items-center gap-4 text-xs"
            >
                <span class="font-mono text-indigo-300 font-bold">
                    {{ currentIndex + 1 }} / {{ wisudawans.length }} Wisudawan
                </span>

                <div class="flex items-center gap-2">
                    <button
                        @click="prevCandidate"
                        :disabled="currentIndex === 0"
                        class="px-3 py-1.5 bg-white/10 hover:bg-white/20 disabled:opacity-30 rounded-xl font-bold transition"
                    >
                         Prev
                    </button>

                    <button
                        @click="nextCandidate"
                        :disabled="currentIndex === wisudawans.length - 1"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-30 text-white rounded-xl font-bold transition"
                    >
                        Next 
                    </button>

                    <button
                        @click="toggleFullscreen"
                        class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-xl font-bold transition border border-slate-700 text-slate-300"
                    >
                        {{ isFullscreen ? 'Exit Fullscreen (F)' : 'Fullscreen (F)' }}
                    </button>
                </div>
            </div>
        </transition>

    </div>
</template>
