<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    wisudawan: Object,
    quotaData: Object,
    tamuTambahan: Array,
});

const form = useForm({
    guests: props.tamuTambahan && props.tamuTambahan.length > 0 
        ? props.tamuTambahan.map(g => ({ nama_tamu: g.nama_tamu, hubungan: g.hubungan })) 
        : [
            { nama_tamu: '', hubungan: 'Orang Tua / Ayah' },
            { nama_tamu: '', hubungan: 'Orang Tua / Ibu' }
        ],
});

const maxGuests = props.quotaData?.total_allowed_guests || 2;

const addGuest = () => {
    if (form.guests.length < maxGuests) {
        form.guests.push({ nama_tamu: '', hubungan: 'Pendamping / Tamu Tambahan' });
    }
};

const removeGuest = (index) => {
    form.guests.splice(index, 1);
};

const submitForm = () => {
    form.post(route('wisudawan.tamu.store'));
};
</script>

<template>
    <Head title="Pendaftaran Tamu & Kalkulasi Snack Wisuda" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span>👨‍👩‍👧‍👦</span> Pendaftaran Tamu Undangan & Catering Snack
                </h2>
                <span class="px-4 py-1.5 bg-indigo-100 text-indigo-700 font-semibold rounded-full text-sm">
                    Referensi SIKEU: {{ quotaData?.total_allowed_guests }} Undangan
                </span>
            </div>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Financial Quota & Snack Calculation Card -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl font-black">🎟️</div>
                        <p class="text-indigo-200 text-sm font-semibold uppercase tracking-wider">Total Kuota Pendamping</p>
                        <h3 class="text-4xl font-extrabold mt-2">{{ quotaData?.total_allowed_guests }} Orang</h3>
                        <p class="text-xs text-indigo-100 mt-2">Termasuk 2 Undangan Utama + {{ quotaData?.tambahan_wisuda_paid_quota }} Extra dari SIKEU</p>
                    </div>

                    <div class="bg-gradient-to-br from-amber-500 to-amber-700 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl font-black">🍱</div>
                        <p class="text-amber-100 text-sm font-semibold uppercase tracking-wider">Perhitungan Snack Catering</p>
                        <h3 class="text-4xl font-extrabold mt-2">{{ quotaData?.snack_quota }} Porsi</h3>
                        <p class="text-xs text-amber-100 mt-2">1 Porsi Wisudawan + {{ quotaData?.total_allowed_guests }} Porsi Tamu Undangan</p>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl font-black">✅</div>
                        <p class="text-emerald-100 text-sm font-semibold uppercase tracking-wider">Status Verifikasi SIKEU</p>
                        <h3 class="text-2xl font-bold mt-2">Lunas / Terverifikasi</h3>
                        <p class="text-xs text-emerald-100 mt-2">NIM: {{ wisudawan?.nim }}</p>
                    </div>
                </div>

                <!-- Guest Form -->
                <div class="bg-white rounded-2xl p-8 shadow-md border border-slate-100">
                    <div class="flex items-center justify-between border-b pb-4 mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">Daftar Nama Tamu / Pendamping Wisuda</h3>
                            <p class="text-sm text-slate-500">Masukkan nama lengkap yang akan hadir untuk pencetakan id card & verifikasi presensi gate reception.</p>
                        </div>
                        <button 
                            type="button" 
                            @click="addGuest" 
                            :disabled="form.guests.length >= maxGuests"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-sm rounded-xl transition shadow-md flex items-center gap-1.5"
                        >
                            <span>➕</span> Tambah Tamu (Maks: {{ maxGuests }})
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        <div v-for="(guest, index) in form.guests" :key="index" class="p-4 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                            <div class="md:col-span-1 text-center font-bold text-slate-400">
                                #{{ index + 1 }}
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap Pendamping</label>
                                <input 
                                    v-model="guest.nama_tamu" 
                                    type="text" 
                                    required 
                                    placeholder="Contoh: Bapak H. Ahmad Subandi" 
                                    class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Hubungan / Status</label>
                                <select 
                                    v-model="guest.hubungan" 
                                    class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="Orang Tua / Ayah">Orang Tua / Ayah</option>
                                    <option value="Orang Tua / Ibu">Orang Tua / Ibu</option>
                                    <option value="Wali">Wali</option>
                                    <option value="Suami / Istri">Suami / Istri</option>
                                    <option value="Saudara / Tamu Tambahan">Saudara / Tamu Tambahan</option>
                                </select>
                            </div>
                            <div class="md:col-span-1 text-right">
                                <button 
                                    type="button" 
                                    @click="removeGuest(index)" 
                                    v-if="form.guests.length > 1"
                                    class="text-red-500 hover:text-red-700 p-2 text-sm font-semibold rounded-lg hover:bg-red-50"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-slate-100">
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg transition flex items-center gap-2"
                            >
                                <span>💾</span> Simpan Data Pendamping & Snack
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Guest QR Cards -->
                <div v-if="tamuTambahan && tamuTambahan.length > 0" class="bg-white rounded-2xl p-8 shadow-md border border-slate-100">
                    <h3 class="text-xl font-bold text-slate-800 mb-4">Kartu Undangan Pendamping Reguler</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="tamu in tamuTambahan" :key="tamu.id" class="p-5 border border-slate-200 rounded-2xl bg-gradient-to-r from-slate-50 to-indigo-50/30 flex items-center justify-between shadow-sm">
                            <div>
                                <span class="px-2.5 py-1 bg-indigo-100 text-indigo-700 font-semibold text-xs rounded-md">{{ tamu.hubungan }}</span>
                                <h4 class="font-bold text-slate-900 text-lg mt-1">{{ tamu.nama_tamu }}</h4>
                                <p class="text-xs text-slate-500 mt-1">Status Kehadiran: <span :class="tamu.is_hadir ? 'text-emerald-600 font-bold' : 'text-amber-600'">{{ tamu.is_hadir ? 'Sudah Masuk Gate' : 'Belum Presensi' }}</span></p>
                                <p class="text-xs text-slate-500">Status Snack: <span :class="tamu.snack_diambil ? 'text-emerald-600 font-bold' : 'text-slate-600'">{{ tamu.snack_diambil ? 'Terambil' : 'Siap Diambil di Reception' }}</span></p>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm text-center">
                                <div class="text-[10px] font-mono text-slate-400 mb-1">SCAN CODE</div>
                                <div class="text-xs font-bold font-mono text-indigo-600">{{ tamu.qr_guest_token || 'GST-WISUDA' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
