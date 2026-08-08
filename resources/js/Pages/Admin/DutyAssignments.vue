<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    simpegEmployees: Array,
    dutyAssignments: Array,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');

const form = useForm({
    simpeg_username: '',
    simpeg_id_sdm: '',
    simpeg_nip: '',
    nama_pegawai: '',
    duty_role: 'security',
});

const searchSimpeg = () => {
    router.get(route('admin.duty-assignments.index'), { search: searchQuery.value }, { preserveState: true });
};

const assignEmployee = (emp, role) => {
    form.simpeg_username = emp.username;
    form.simpeg_id_sdm = emp.id_sdm;
    form.simpeg_nip = emp.nip;
    form.nama_pegawai = emp.nama;
    form.duty_role = role;

    form.post(route('admin.duty-assignments.store'), {
        onSuccess: () => form.reset(),
    });
};

const toggleStatus = (duty) => {
    router.patch(route('admin.duty-assignments.toggle', duty.id));
};

const deleteDuty = (duty) => {
    if (confirm(`Hapus hak akses scan untuk ${duty.nama_pegawai}?`)) {
        router.delete(route('admin.duty-assignments.destroy', duty.id));
    }
};
</script>

<template>
    <Head title="Manajemen Penugasan Scan Security & Receptionist (SIMPEG)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span>👮‍♂️</span> Penugasan Gate Scan Security & Receptionist
                </h2>
                <span class="px-4 py-1.5 bg-sky-100 text-sky-800 font-semibold rounded-full text-xs">
                    Referensi Database SIMPEG (wsia_profil)
                </span>
            </div>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Active Assigned Officers -->
                <div class="bg-white rounded-2xl p-8 shadow-md border border-slate-100">
                    <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span>📋</span> Daftar Pegawai Bertugas Scan Presensi QR Code
                    </h3>

                    <div v-if="dutyAssignments.length === 0" class="text-center py-10 border-2 border-dashed border-slate-200 rounded-2xl">
                        <span class="text-5xl">🛑</span>
                        <p class="text-slate-500 font-medium mt-3">Belum ada pegawai SIMPEG yang ditugaskan untuk scan presensi.</p>
                        <p class="text-xs text-slate-400 mt-1">Gunakan form pencarian di bawah untuk memilih pegawai dari SIMPEG.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="duty in dutyAssignments" :key="duty.id" class="p-6 rounded-2xl border transition shadow-sm relative overflow-hidden" :class="duty.is_active ? 'bg-gradient-to-br from-white to-slate-50 border-slate-200' : 'bg-slate-100 border-slate-300 opacity-60'">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="px-3 py-1 text-xs font-extrabold uppercase rounded-full tracking-wider" :class="duty.duty_role === 'security' ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800'">
                                        {{ duty.duty_role === 'security' ? '👮 Security Officer' : '👩‍💼 Receptionist' }}
                                    </span>
                                    <h4 class="font-bold text-lg text-slate-900 mt-3">{{ duty.nama_pegawai }}</h4>
                                    <p class="text-xs text-slate-500 font-mono mt-0.5">Username: {{ duty.simpeg_username }}</p>
                                    <p class="text-xs text-slate-500 font-mono">NIP/NIDN: {{ duty.simpeg_nip || duty.simpeg_id_sdm || '-' }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <button @click="toggleStatus(duty)" class="px-3 py-1 rounded-lg text-xs font-bold transition" :class="duty.is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-200 text-slate-700 hover:bg-slate-300'">
                                        {{ duty.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </button>
                                    <button @click="deleteDuty(duty)" class="text-xs text-red-500 hover:text-red-700 font-semibold p-1">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SIMPEG Employee Search & Duty Assignment -->
                <div class="bg-white rounded-2xl p-8 shadow-md border border-slate-100 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">Cari Pegawai dari SIMPEG (Live API)</h3>
                            <p class="text-sm text-slate-500">Pilih pegawai dari SIMPEG untuk diberikan hak akses scan presensi dari HP maupun laptop.</p>
                        </div>
                        <form @submit.prevent="searchSimpeg" class="flex items-center gap-2">
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Cari NIDN / Nama Pegawai..." 
                                class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-2 w-64"
                            />
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold text-sm rounded-xl hover:bg-indigo-700 transition">
                                🔍 Cari API
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-slate-600 uppercase text-xs tracking-wider border-b">
                                    <th class="py-3.5 px-4 font-bold">Pegawai</th>
                                    <th class="py-3.5 px-4 font-bold">Username / Login SIMPEG</th>
                                    <th class="py-3.5 px-4 font-bold">NIP / NIDN</th>
                                    <th class="py-3.5 px-4 font-bold text-center">Tugaskan Sebagai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr v-for="emp in simpegEmployees" :key="emp.username" class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-4 font-bold text-slate-900">
                                        {{ emp.nama }}
                                        <span class="block text-xs font-normal text-slate-400">{{ emp.email || emp.status }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-700">{{ emp.username }}</td>
                                    <td class="py-3.5 px-4 font-mono text-slate-500">{{ emp.nip || emp.nidn || '-' }}</td>
                                    <td class="py-3.5 px-4 text-center space-x-2">
                                        <button 
                                            @click="assignEmployee(emp, 'security')" 
                                            class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition shadow-sm"
                                        >
                                            👮 Security
                                        </button>
                                        <button 
                                            @click="assignEmployee(emp, 'receptionist')" 
                                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition shadow-sm"
                                        >
                                            👩‍💼 Receptionist
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Direct Form Input Fallback -->
                    <div class="pt-6 border-t border-slate-100 space-y-4">
                        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                            <span>✏️</span> Penugasan Pegawai SIMPEG Manual (Ketik Langsung)
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <input 
                                v-model="form.nama_pegawai" 
                                type="text" 
                                placeholder="Nama Lengkap Pegawai..." 
                                class="rounded-xl border-slate-300 text-xs px-3 py-2"
                            />
                            <input 
                                v-model="form.simpeg_username" 
                                type="text" 
                                placeholder="Username SIMPEG / NIDN..." 
                                class="rounded-xl border-slate-300 text-xs px-3 py-2 font-mono"
                            />
                            <select v-model="form.duty_role" class="rounded-xl border-slate-300 text-xs px-3 py-2">
                                <option value="security">👮 Duty Security</option>
                                <option value="receptionist">👩‍💼 Duty Receptionist</option>
                            </select>
                            <button 
                                @click="form.post(route('admin.duty-assignments.store'))" 
                                :disabled="form.processing || !form.nama_pegawai || !form.simpeg_username"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition"
                            >
                                + Simpan Penugasan
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
