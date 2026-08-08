<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    wisudawan: Object,
    syaratList: Array,
    berkasList: Object,
});

const uploadingId = ref(null);

const getBerkas = (syaratId) => {
    return props.berkasList[syaratId] || null;
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved': return { text: 'DISETUJUI (APPROVED)', class: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700' };
        case 'rejected': return { text: 'DITOLAK (REJECTED)', class: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/40 dark:text-red-300 dark:border-red-700' };
        default: return { text: 'MENUNGGU VERIFIKASI', class: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700' };
    }
};

const handleFileUpload = (syarat, event) => {
    const file = event.target.files[0];
    if (!file) return;

    uploadingId.value = syarat.id;

    const form = useForm({
        file: file,
    });

    form.post(route('wisudawan.berkas.upload', syarat.id), {
        forceFormData: true,
        onFinish: () => {
            uploadingId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Unggah Berkas Persyaratan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Dokumen Persyaratan Wisuda</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Unggah seluruh dokumen wajib untuk diverifikasi oleh Panitia & Prodi.
                    </p>
                </div>
                <div class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-mono text-xs font-bold rounded-xl border border-indigo-200 dark:border-indigo-700">
                    NIM: {{ wisudawan.nim }}
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Status Notice Banner -->
                <div v-if="wisudawan.status_verifikasi === 'rejected'" class="bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-700 rounded-2xl p-6 text-red-800 dark:text-red-200 space-y-1">
                    <h4 class="font-bold text-base flex items-center gap-2">
                        <span>❌</span> Catatan Ditolak Dari Verifikator:
                    </h4>
                    <p class="text-xs">{{ wisudawan.catatan_verifikasi || 'Silakan unggah kembali berkas yang belum sesuai.' }}</p>
                </div>

                <div class="space-y-4">
                    <div v-for="s in syaratList" :key="s.id" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-2 max-w-xl">
                            <div class="flex items-center gap-3">
                                <span :class="['px-2 py-0.5 text-[10px] font-extrabold uppercase rounded border tracking-wider', s.is_wajib ? 'bg-red-50 text-red-600 border-red-200 dark:bg-red-900/40 dark:text-red-300' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-700']">
                                    {{ s.is_wajib ? 'WAJIB' : 'OPSIONAL' }}
                                </span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ s.nama_syarat }}</h3>
                            </div>

                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ s.deskripsi || 'Tidak ada catatan petunjuk.' }}</p>

                            <div class="flex items-center gap-4 text-[11px] text-slate-400 font-mono">
                                <span>Format: <strong class="text-slate-600 dark:text-slate-300 uppercase">{{ s.format_file }}</strong></span>
                                <span>Max: <strong class="text-slate-600 dark:text-slate-300">{{ (s.max_file_size_kb / 1024).toFixed(1) }} MB</strong></span>
                            </div>
                        </div>

                        <!-- Status & Upload Action -->
                        <div class="flex flex-col md:items-end gap-3 shrink-0">
                            <template v-if="getBerkas(s.id)">
                                <span :class="['px-3 py-1 text-xs font-bold rounded-full border inline-flex items-center gap-1.5', getStatusBadge(getBerkas(s.id).status).class]">
                                    {{ getStatusBadge(getBerkas(s.id).status).text }}
                                </span>

                                <div class="flex items-center gap-2">
                                    <a
                                        :href="`/storage/${getBerkas(s.id).file_path}`"
                                        target="_blank"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-1"
                                    >
                                        📄 Lihat File
                                    </a>

                                    <label class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/40 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold rounded-xl cursor-pointer transition">
                                        {{ uploadingId === s.id ? 'Mengunggah...' : 'Ganti File' }}
                                        <input type="file" @change="handleFileUpload(s, $event)" class="hidden" />
                                    </label>
                                </div>

                                <p v-if="getBerkas(s.id).catatan" class="text-xs text-red-500 dark:text-red-400 max-w-xs text-right italic">
                                    "{{ getBerkas(s.id).catatan }}"
                                </p>
                            </template>

                            <template v-else>
                                <span class="px-3 py-1 text-xs font-bold rounded-full border bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                                    BELUM DIUNGGAH
                                </span>

                                <label class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl cursor-pointer transition shadow-md shadow-indigo-500/20">
                                    {{ uploadingId === s.id ? 'Mengunggah...' : '+ Unggah Dokumen' }}
                                    <input type="file" @change="handleFileUpload(s, $event)" class="hidden" />
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
