<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    programStudis: Array,
});

const showModal = ref(false);
const editingProdi = ref(null);

const form = useForm({
    kode_prodi: '',
    nama_prodi: '',
    jenjang: 'D3',
    kaprodi_nama: '',
    kaprodi_nip: '',
});

const openModal = (prodi = null) => {
    editingProdi.value = prodi;
    if (prodi) {
        form.kode_prodi = prodi.kode_prodi;
        form.nama_prodi = prodi.nama_prodi;
        form.jenjang = prodi.jenjang;
        form.kaprodi_nama = prodi.kaprodi_nama || '';
        form.kaprodi_nip = prodi.kaprodi_nip || '';
    } else {
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitProdi = () => {
    if (editingProdi.value) {
        form.put(route('admin.prodi.update', editingProdi.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.prodi.store'), {
            onSuccess: () => closeModal(),
        });
    }
};
</script>

<template>
    <Head title="Kelola Program Studi - Politeknik Indonusa Surakarta" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            
            <!-- Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v12a2 2 0 01-2 2m-6 0h6" />
                        </svg>
                        <span>Program Studi Politeknik Indonusa Surakarta</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Kelola daftar program studi diploma & sarjana terapan beserta data ketua program studi.
                    </p>
                </div>

                <button
                    @click="openModal()"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Program Studi</span>
                </button>
            </div>

            <!-- Program Studi Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="ps in programStudis"
                    :key="ps.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm space-y-4 hover:border-indigo-200 dark:hover:border-indigo-800 transition"
                >
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-mono text-xs font-bold rounded-lg border border-indigo-200 dark:border-indigo-800">
                            {{ ps.kode_prodi }}
                        </span>
                        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            {{ ps.jenjang }}
                        </span>
                    </div>

                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ ps.nama_prodi }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kaprodi: <strong class="text-gray-700 dark:text-gray-300">{{ ps.kaprodi_nama || '-' }}</strong>
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Total: <strong class="text-indigo-600 dark:text-indigo-400 font-bold">{{ ps.wisudawans_count || 0 }}</strong> Wisudawan</span>
                        <button
                            @click="openModal(ps)"
                            class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                        >
                            <span>Edit Prodi</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!programStudis || programStudis.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center text-gray-400 text-xs border border-gray-100 dark:border-gray-700">
                Belum ada program studi terdaftar.
            </div>

        </div>

        <!-- Modal Form Prodi -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-base text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>{{ editingProdi ? 'Edit Program Studi' : 'Tambah Program Studi Baru' }}</span>
                    </h3>
                </div>

                <form @submit.prevent="submitProdi" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Kode Prodi</label>
                        <input v-model="form.kode_prodi" type="text" placeholder="Contoh: D3-SI" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Nama Program Studi</label>
                        <input v-model="form.nama_prodi" type="text" placeholder="Contoh: D3 Sistem Informasi" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Jenjang</label>
                            <select v-model="form.jenjang" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs">
                                <option value="D3">D3 (Diploma 3)</option>
                                <option value="D4">D4 (Diploma 4)</option>
                                <option value="S1">S1 (Sarjana)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Nama Kaprodi</label>
                            <input v-model="form.kaprodi_nama" type="text" placeholder="Nama Kaprodi & Gelar" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">Batal</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
