<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    syaratList: Array,
});

const showModal = ref(false);

const form = useForm({
    nama_syarat: '',
    deskripsi: '',
    format_file: 'pdf,jpg,png',
    max_file_size_kb: 2048,
    is_wajib: true,
});

const submitStore = () => {
    form.post(route('admin.syarat.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const deleteSyarat = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus persyaratan ini?')) {
        router.delete(route('admin.syarat.destroy', id));
    }
};
</script>

<template>
    <Head title="Kelola Syarat Wisuda" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Master Syarat Dokumen Wisuda</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Konfigurasi dokumen wajib & pendukung pendaftaran wisuda.</p>
                </div>
                <button
                    @click="showModal = true"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-indigo-500/25"
                >
                    + Tambah Syarat Baru
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="s in syaratList" :key="s.id" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg border uppercase tracking-wider', s.is_wajib ? 'bg-red-50 text-red-600 border-red-200 dark:bg-red-900/40 dark:text-red-300' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-700']">
                                    {{ s.is_wajib ? 'Dokumen Wajib' : 'Opsional' }}
                                </span>
                                <span class="font-mono text-xs text-slate-400">Max: {{ (s.max_file_size_kb / 1024).toFixed(1) }} MB</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ s.nama_syarat }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ s.deskripsi || 'Tidak ada deskripsi.' }}</p>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700">
                            <span class="text-xs font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/40 px-2 py-1 rounded">
                                Format: {{ s.format_file }}
                            </span>
                            <button @click="deleteSyarat(s.id)" class="text-xs text-red-600 dark:text-red-400 font-semibold hover:underline">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 shadow-2xl space-y-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Tambah Syarat Wisuda</h3>
                <form @submit.prevent="submitStore" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Syarat Dokumen</label>
                        <input v-model="form.nama_syarat" type="text" placeholder="Bebas Pustaka / Ijazah..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Deskripsi / Petunjuk</label>
                        <textarea v-model="form.deskripsi" rows="2" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Format File Allowed</label>
                            <input v-model="form.format_file" type="text" placeholder="pdf,jpg,png" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Max Size (KB)</label>
                            <input v-model="form.max_file_size_kb" type="number" placeholder="2048" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <input v-model="form.is_wajib" type="checkbox" id="is_wajib" class="rounded border-slate-300 text-indigo-600" />
                        <label for="is_wajib" class="text-sm text-slate-700 dark:text-slate-300">Wajib diunggah calon wisudawan</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-slate-600 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-bold text-sm rounded-xl">Simpan Syarat</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
