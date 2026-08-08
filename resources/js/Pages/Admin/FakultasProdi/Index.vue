<script setup>
import AuthenticatedLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    fakultas: Array,
});

const showFakultasModal = ref(false);
const showProdiModal = ref(false);
const selectedFakultasId = ref(null);

const fakultasForm = useForm({
    kode_fakultas: '',
    nama_fakultas: '',
    dekan_nama: '',
    dekan_nip: '',
});

const prodiForm = useForm({
    fakultas_id: '',
    kode_prodi: '',
    nama_prodi: '',
    jenjang: 'S1',
    kaprodi_nama: '',
    kaprodi_nip: '',
});

const submitFakultas = () => {
    fakultasForm.post(route('admin.fakultas.store'), {
        onSuccess: () => {
            showFakultasModal.value = false;
            fakultasForm.reset();
        },
    });
};

const openProdiModal = (fId) => {
    prodiForm.fakultas_id = fId;
    showProdiModal.value = true;
};

const submitProdi = () => {
    prodiForm.post(route('admin.prodi.store'), {
        onSuccess: () => {
            showProdiModal.value = false;
            prodiForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Kelola Fakultas & Program Studi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Master Fakultas & Program Studi</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Struktur organisasi perguruan tinggi & pimpinan prodi.</p>
                </div>
                <button
                    @click="showFakultasModal = true"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-indigo-500/25"
                >
                    + Tambah Fakultas
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div v-for="f in fakultas" :key="f.id" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-mono text-xs font-bold rounded-lg border border-indigo-200 dark:border-indigo-700">
                                    {{ f.kode_fakultas }}
                                </span>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ f.nama_fakultas }}</h3>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Dekan: <span class="font-medium text-slate-700 dark:text-slate-300">{{ f.dekan_nama || '-' }}</span> (NIP: {{ f.dekan_nip || '-' }})
                            </p>
                        </div>
                        <button
                            @click="openProdiModal(f.id)"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition"
                        >
                            + Tambah Prodi
                        </button>
                    </div>

                    <!-- Program Studi List -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="p in f.program_studi" :key="p.id" class="p-4 rounded-xl border border-slate-100 dark:border-slate-700/60 bg-slate-50 dark:bg-slate-900/40 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-mono">{{ p.kode_prodi }} • {{ p.jenjang }}</span>
                                <span class="text-xs px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded font-semibold">
                                    {{ p.wisudawan_count || 0 }} Wisudawan
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-900 dark:text-white">{{ p.nama_prodi }}</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Kaprodi: {{ p.kaprodi_nama || '-' }}</p>
                        </div>
                        <div v-if="!f.program_studi || f.program_studi.length === 0" class="col-span-full py-4 text-center text-slate-400 text-sm">
                            Belum ada Program Studi di fakultas ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fakultas Modal -->
        <div v-if="showFakultasModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 shadow-2xl space-y-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Tambah Fakultas Baru</h3>
                <form @submit.prevent="submitFakultas" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Kode Fakultas</label>
                        <input v-model="fakultasForm.kode_fakultas" type="text" placeholder="FT" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Fakultas</label>
                        <input v-model="fakultasForm.nama_fakultas" type="text" placeholder="Fakultas Teknik" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Dekan</label>
                        <input v-model="fakultasForm.dekan_nama" type="text" placeholder="Dr. Eng..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">NIP Dekan</label>
                        <input v-model="fakultasForm.dekan_nip" type="text" placeholder="197..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showFakultasModal = false" class="px-4 py-2 text-sm text-slate-600 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-bold text-sm rounded-xl">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Prodi Modal -->
        <div v-if="showProdiModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 shadow-2xl space-y-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Tambah Program Studi</h3>
                <form @submit.prevent="submitProdi" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Kode Prodi</label>
                            <input v-model="prodiForm.kode_prodi" type="text" placeholder="IF-S1" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Jenjang</label>
                            <select v-model="prodiForm.jenjang" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm">
                                <option value="D3">D3</option>
                                <option value="D4">D4</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Program Studi</label>
                        <input v-model="prodiForm.nama_prodi" type="text" placeholder="Teknik Informatika" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Kaprodi</label>
                        <input v-model="prodiForm.kaprodi_nama" type="text" placeholder="Nama Kaprodi..." class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showProdiModal = false" class="px-4 py-2 text-sm text-slate-600 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-bold text-sm rounded-xl">Simpan Prodi</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
