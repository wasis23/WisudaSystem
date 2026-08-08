<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    wisudawan: Object,
    syaratList: Array,
});

const rejectingBerkasId = ref(null);
const rejectionNotes = ref('');

const getUploadedBerkas = (syaratId) => {
    return props.wisudawan.berkas?.find(b => b.syarat_wisuda_id === syaratId) || null;
};

const approveBerkas = (berkasId) => {
    router.patch(route('verifikator.berkas.approve', berkasId));
};

const submitReject = (berkasId) => {
    if (!rejectionNotes.value.trim()) {
        alert('Harap masukkan alasan/catatan penolakan.');
        return;
    }

    router.patch(route('verifikator.berkas.reject', berkasId), {
        catatan: rejectionNotes.value,
    }, {
        onSuccess: () => {
            rejectingBerkasId.value = null;
            rejectionNotes.value = '';
        },
    });
};
</script>

<template>
    <Head :title="`Verifikasi Berkas - ${wisudawan.nama_lengkap}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Lembar Verifikasi Wisudawan</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Periksa kelengkapan biodata & dokumen persyaratan akademik.</p>
                </div>
                <span
                    :class="[
                        'px-4 py-1.5 rounded-full text-xs font-extrabold uppercase border',
                        wisudawan.status_verifikasi === 'verified' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                        wisudawan.status_verifikasi === 'rejected' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-amber-50 text-amber-700 border-amber-200'
                    ]"
                >
                    Status: {{ wisudawan.status_verifikasi }}
                </span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Profile Header Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm flex flex-col md:flex-row gap-6 items-center md:items-start">
                    <div class="w-28 h-36 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shrink-0">
                        <img v-if="wisudawan.pas_foto" :src="`/storage/${wisudawan.pas_foto}`" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-xs">No Foto</div>
                    </div>

                    <div class="space-y-3 flex-1">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-bold text-indigo-600 dark:text-indigo-400">NIM: {{ wisudawan.nim }}</span>
                                <span class="text-xs px-2.5 py-0.5 bg-slate-100 dark:bg-slate-700 font-semibold rounded">{{ wisudawan.program_studi?.nama_prodi }}</span>
                            </div>
                            <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ wisudawan.nama_lengkap }}</h3>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                            <div>
                                <span class="block text-slate-400 uppercase font-bold">IPK</span>
                                <span class="font-bold text-slate-900 dark:text-white text-base">{{ wisudawan.ipk }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-400 uppercase font-bold">Predikat</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ wisudawan.predikat_kelulusan }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-400 uppercase font-bold">Tanggal Lulus</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ wisudawan.tanggal_lulus }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-400 uppercase font-bold">No. HP</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ wisudawan.nomor_hp }}</span>
                            </div>
                        </div>

                        <div class="text-xs pt-2 border-t border-slate-100 dark:border-slate-700">
                            <span class="font-bold uppercase text-slate-400">Judul Skripsi / TA:</span>
                            <p class="text-slate-700 dark:text-slate-300 font-medium italic mt-0.5">"{{ wisudawan.judul_ta }}"</p>
                        </div>
                    </div>
                </div>

                <!-- Document Verification List -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Pemeriksaan Berkas Dokumen</h3>

                    <div v-for="s in syaratList" :key="s.id" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span :class="['px-2 py-0.5 text-[10px] font-bold uppercase rounded border', s.is_wajib ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-100 text-slate-600']">
                                        {{ s.is_wajib ? 'WAJIB' : 'OPSIONAL' }}
                                    </span>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-base">{{ s.nama_syarat }}</h4>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">{{ s.deskripsi }}</p>
                            </div>

                            <!-- Document Uploaded Status & Action Buttons -->
                            <div class="flex items-center gap-3">
                                <template v-if="getUploadedBerkas(s.id)">
                                    <a
                                        :href="`/storage/${getUploadedBerkas(s.id).file_path}`"
                                        target="_blank"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs rounded-xl transition flex items-center gap-1"
                                    >
                                        📄 Buka PDF/Foto
                                    </a>

                                    <button
                                        v-if="getUploadedBerkas(s.id).status !== 'approved'"
                                        @click="approveBerkas(getUploadedBerkas(s.id).id)"
                                        class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-md shadow-emerald-500/20"
                                    >
                                        ✓ Setujui (Approve)
                                    </button>

                                    <button
                                        v-if="getUploadedBerkas(s.id).status !== 'rejected'"
                                        @click="rejectingBerkasId = getUploadedBerkas(s.id).id"
                                        class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition shadow-md shadow-red-500/20"
                                    >
                                        ✕ Tolak (Reject)
                                    </button>
                                </template>

                                <template v-else>
                                    <span class="px-3 py-1 bg-slate-100 text-slate-400 text-xs font-bold rounded-full">Belum Diunggah</span>
                                </template>
                            </div>
                        </div>

                        <!-- Reject Modal / Form Input -->
                        <div v-if="rejectingBerkasId === getUploadedBerkas(s.id)?.id" class="p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 space-y-3">
                            <h5 class="text-xs font-bold text-red-800 dark:text-red-200 uppercase">Alasan / Catatan Penolakan Dokumen</h5>
                            <textarea v-model="rejectionNotes" rows="2" placeholder="Jelaskan alasan penolakan agar mahasiswa dapat memperbaiki..." class="w-full rounded-xl border-red-200 dark:border-red-700 dark:bg-slate-900 text-xs" required></textarea>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="rejectingBerkasId = null" class="px-3 py-1 text-xs text-slate-600">Batal</button>
                                <button type="button" @click="submitReject(getUploadedBerkas(s.id).id)" class="px-4 py-1 bg-red-600 text-white font-bold text-xs rounded-lg">Kirim Penolakan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
