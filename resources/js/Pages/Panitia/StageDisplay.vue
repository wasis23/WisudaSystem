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

// Auto-scale 1280x720 canvas calculation for full projector screen
const stageWrapperRef = ref(null);
const scaleFactor = ref(1);

const updateScale = () => {
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    
    const scaleX = screenWidth / 1280;
    const scaleY = screenHeight / 720;
    
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
        <!-- Fullscreen Canvas Box 1280x720 Scaled to exact screen dimensions -->
        <div
            class="relative w-[1280px] h-[720px] shrink-0 overflow-hidden shadow-2xl origin-center"
            :style="{ transform: `scale(${scaleFactor})` }"
        >
            <!-- Background Image uploaded by Admin -->
            <img
                v-if="stageConfig?.bg_image"
                :src="`/storage/${stageConfig.bg_image}`"
                class="absolute inset-0 w-full h-full object-cover z-0"
            />

            <!-- Decorative Gradient Fallback if no Background Image uploaded -->
            <div v-else class="absolute inset-0 bg-gradient-to-br from-indigo-950 via-slate-950 to-purple-950 z-0">
                <div class="absolute top-10 left-10 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
            </div>

            <!-- Main Wisudawan Projection Content -->
            <template v-if="currentWisudawan">
                <div class="relative z-10 w-full h-full">
                    
                    <!-- Pas Foto Wisudawan -->
                    <div
                        :style="{
                            left: (stageConfig?.photo_x || 100) + 'px',
                            top: (stageConfig?.photo_y || 150) + 'px',
                            width: (stageConfig?.photo_w || 320) + 'px',
                            height: (stageConfig?.photo_h || 420) + 'px',
                        }"
                        class="absolute border-4 border-white/20 bg-slate-900 rounded-3xl overflow-hidden shadow-2xl"
                    >
                        <img v-if="currentWisudawan.pas_foto" :src="`/storage/${currentWisudawan.pas_foto}`" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center text-slate-500 font-bold text-lg">Pas Foto</div>
                    </div>

                    <!-- Nama Lengkap Wisudawan & Gelar -->
                    <div
                        :style="{
                            left: (stageConfig?.nama_x || 480) + 'px',
                            top: (stageConfig?.nama_y || 180) + 'px',
                            fontSize: (stageConfig?.nama_font_size || 48) + 'px',
                        }"
                        class="absolute font-black text-white whitespace-nowrap leading-none tracking-tight drop-shadow-xl"
                    >
                        {{ currentWisudawan.nama_lengkap }}{{ currentWisudawan.gelar ? `, ${currentWisudawan.gelar}` : '' }}
                    </div>

                    <!-- NIM Wisudawan -->
                    <div
                        :style="{
                            left: (stageConfig?.nim_x || 480) + 'px',
                            top: (stageConfig?.nim_y || 250) + 'px',
                            fontSize: (stageConfig?.nim_font_size || 24) + 'px',
                        }"
                        class="absolute font-mono font-extrabold text-indigo-300 leading-none drop-shadow-md"
                    >
                        NIM: {{ currentWisudawan.nim }}
                    </div>

                    <!-- Program Studi -->
                    <div
                        :style="{
                            left: (stageConfig?.prodi_x || 480) + 'px',
                            top: (stageConfig?.prodi_y || 290) + 'px',
                            fontSize: (stageConfig?.prodi_font_size || 24) + 'px',
                        }"
                        class="absolute font-bold text-slate-200 leading-none drop-shadow-md"
                    >
                        {{ currentWisudawan.program_studi?.nama_prodi }} - Politeknik Indonusa Surakarta
                    </div>

                    <!-- IPK & Status Cumlaude -->
                    <div
                        :style="{
                            left: (stageConfig?.ipk_x || 480) + 'px',
                            top: (stageConfig?.ipk_y || 340) + 'px',
                            fontSize: (stageConfig?.ipk_font_size || 28) + 'px',
                        }"
                        class="absolute font-mono font-bold text-emerald-400 leading-none flex items-center gap-3 drop-shadow-md"
                    >
                        <span>IPK: {{ currentWisudawan.ipk }}</span>
                        <span v-if="Number(currentWisudawan.ipk) >= 3.51" class="px-3 py-1 bg-amber-500 text-slate-950 font-black text-xs uppercase tracking-wider rounded-full shadow-lg">
                            Cumlaude ★
                        </span>
                    </div>

                    <!-- Judul Tugas Akhir / Skripsi -->
                    <div
                        :style="{
                            left: (stageConfig?.ta_x || 480) + 'px',
                            top: (stageConfig?.ta_y || 400) + 'px',
                            fontSize: (stageConfig?.ta_font_size || 20) + 'px',
                            maxWidth: (stageConfig?.ta_max_w || 700) + 'px',
                        }"
                        class="absolute font-medium italic text-slate-200 leading-snug drop-shadow-md line-clamp-2"
                    >
                        "{{ currentWisudawan.judul_ta }}"
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
                        ◀ Prev
                    </button>

                    <button
                        @click="nextCandidate"
                        :disabled="currentIndex === wisudawans.length - 1"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-30 text-white rounded-xl font-bold transition"
                    >
                        Next ▶
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
