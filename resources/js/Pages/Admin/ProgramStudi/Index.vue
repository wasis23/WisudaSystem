<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    programStudis: Array,
    dosenList: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const selectedDosenId = ref('');

const form = useForm({
    kode_prodi: '',
    nama_prodi: '',
    jenjang: 'D4',
    gelar: '',
    kaprodi_nama: '',
    kaprodi_nip: '',
});

const onDosenSelectChange = () => {
    if (!selectedDosenId.value) return;
    const dosen = props.dosenList.find(d => String(d.id) === String(selectedDosenId.value));
    if (dosen) {
        form.kaprodi_nama = dosen.nama;
        form.kaprodi_nip = dosen.nip && dosen.nip !== '-' ? dosen.nip : '';
    }
};

const openAddModal = () => {
    isEditing.value = false;
    editingId.value = null;
    selectedDosenId.value = '';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.kode_prodi = item.kode_prodi;
    form.nama_prodi = item.nama_prodi;
    form.jenjang = item.jenjang;
    form.gelar = item.gelar || '';
    form.kaprodi_nama = item.kaprodi_nama || '';
    form.kaprodi_nip = item.kaprodi_nip || '';

    // Match selected dosen if exists in list
    const match = props.dosenList.find(
        d => (item.kaprodi_nama && d.nama.toLowerCase().includes(item.kaprodi_nama.toLowerCase())) ||
             (item.kaprodi_nip && d.nip === item.kaprodi_nip)
    );
    selectedDosenId.value = match ? match.id : '';

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedDosenId.value = '';
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.program-studi.update', editingId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.program-studi.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteProdi = (item) => {
    if (confirm(`Apakah Anda yakin ingin menghapus Program Studi "${item.nama_prodi}"?`)) {
        form.delete(route('admin.program-studi.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Pengaturan Program Studi & Gelar Lulusan" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- PAGE HEADER CARD -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 font-bold text-xs rounded-full">
                        MASTER DATA
                    </span>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                        Pengaturan Program Studi & Gelar Lulusan
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola data program studi, jenjang pendidikan, serta penulisan gelar akademik kelulusan wisudawan.
                    </p>
                </div>

                <button
                    @click="openAddModal"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-2 shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Program Studi</span>
                </button>
            </div>

            <!-- FLASH NOTIFICATION -->
            <div v-if="$page.props.flash.success" class="p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-emerald-800 dark:text-emerald-200 text-xs font-bold">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="p-4 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 rounded-2xl text-rose-800 dark:text-rose-200 text-xs font-bold">
                {{ $page.props.flash.error }}
            </div>

            <!-- DATA TABLE -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 uppercase font-bold text-slate-500 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-5 py-3.5">Kode</th>
                                <th class="px-5 py-3.5">Program Studi</th>
                                <th class="px-5 py-3.5">Jenjang</th>
                                <th class="px-5 py-3.5">Gelar Akademik</th>
                                <th class="px-5 py-3.5">Ketua Program Studi (Kaprodi)</th>
                                <th class="px-5 py-3.5 text-center">Wisudawan</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            <tr v-for="item in programStudis" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition">
                                <td class="px-5 py-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ item.kode_prodi }}
                                </td>

                                <td class="px-5 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ item.nama_prodi }}
                                </td>

                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 rounded-full font-extrabold text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                        {{ item.jenjang }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    <span v-if="item.gelar" class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                        {{ item.gelar }}
                                    </span>
                                    <span v-else class="text-slate-400 italic font-normal">
                                        Belum diatur
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ item.kaprodi_nama || '-' }}
                                    </div>
                                    <div v-if="item.kaprodi_nip" class="text-[11px] text-slate-400 font-mono">
                                        NIP. {{ item.kaprodi_nip }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-center font-bold text-slate-900 dark:text-white">
                                    {{ item.wisudawans_count || 0 }}
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            @click="openEditModal(item)"
                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-lg transition"
                                        >
                                            Edit Gelar
                                        </button>

                                        <button
                                            v-if="item.wisudawans_count === 0"
                                            @click="deleteProdi(item)"
                                            class="px-3 py-1.5 bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 hover:bg-rose-100 font-bold text-[11px] rounded-lg transition border border-rose-200 dark:border-rose-800"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!programStudis?.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500 font-bold">
                                    Belum ada data Program Studi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL FORM PROGRAM STUDI & GELAR -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Edit Gelar & Program Studi' : 'Tambah Program Studi Baru' }}
                        </h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl">&times;</button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-4 text-xs">
                        <!-- Kode Prodi -->
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Program Studi *</label>
                            <input
                                v-model="form.kode_prodi"
                                type="text"
                                placeholder="Contoh: D4-TRPL"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                            <div v-if="form.errors.kode_prodi" class="text-rose-500 mt-1 text-[11px]">{{ form.errors.kode_prodi }}</div>
                        </div>

                        <!-- Nama Prodi -->
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Program Studi *</label>
                            <input
                                v-model="form.nama_prodi"
                                type="text"
                                placeholder="Contoh: D4 Teknologi Rekayasa Perangkat Lunak"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                            <div v-if="form.errors.nama_prodi" class="text-rose-500 mt-1 text-[11px]">{{ form.errors.nama_prodi }}</div>
                        </div>

                        <!-- Grid Jenjang & Gelar -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Jenjang *</label>
                                <select
                                    v-model="form.jenjang"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="D3">D3 (Diploma 3)</option>
                                    <option value="D4">D4 (Sarjana Terapan)</option>
                                    <option value="S1">S1 (Sarjana)</option>
                                    <option value="S2">S2 (Magister)</option>
                                    <option value="S3">S3 (Doktor)</option>
                                </select>
                                <div v-if="form.errors.jenjang" class="text-rose-500 mt-1 text-[11px]">{{ form.errors.jenjang }}</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Gelar Akademik</label>
                                <input
                                    v-model="form.gelar"
                                    type="text"
                                    placeholder="Contoh: S.Tr.Kom. / A.Md.Farm."
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                />
                                <div v-if="form.errors.gelar" class="text-rose-500 mt-1 text-[11px]">{{ form.errors.gelar }}</div>
                            </div>
                        </div>

                        <!-- Kaprodi Selection from SIMPEG Dosen API / Cache -->
                        <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Pilih Kaprodi (Dari Data SIMPEG Dosen)
                                </label>
                                <select
                                    v-model="selectedDosenId"
                                    @change="onDosenSelectChange"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="">-- Pilih Dosen Kaprodi --</option>
                                    <option v-for="dosen in dosenList" :key="dosen.id" :value="dosen.id">
                                        {{ dosen.nama }} (NIP/NIDN: {{ dosen.nip }})
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Kaprodi</label>
                                    <input
                                        v-model="form.kaprodi_nama"
                                        type="text"
                                        placeholder="Nama Kaprodi..."
                                        class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">NIP / NIDN Kaprodi</label>
                                    <input
                                        v-model="form.kaprodi_nip"
                                        type="text"
                                        placeholder="Otomatis dari dosen terpilih..."
                                        class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Submit Footer -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded-xl text-xs"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition shadow-sm disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Prodi') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
