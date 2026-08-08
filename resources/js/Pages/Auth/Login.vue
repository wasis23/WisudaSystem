<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login Portal Wisuda - Politeknik Indonusa Surakarta" />

    <div class="min-h-screen bg-slate-100 flex flex-col justify-center items-center p-4 sm:p-6 lg:p-8 font-sans antialiased text-slate-800">
        
        <!-- Main Login Container Card (Split Screen Layout) -->
        <div class="w-full max-w-5xl bg-white border border-slate-200 rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[580px]">
            
            <!-- Left Branding & Information Side -->
            <div class="lg:col-span-5 bg-blue-950 text-white p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden">
                <!-- Ambient Subtle Background Gradient -->
                <div class="absolute -top-24 -left-24 w-72 h-72 bg-blue-800/30 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Campus Identity Header -->
                <div class="relative z-10 space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-900 text-amber-400 flex items-center justify-center font-bold text-2xl shadow-md border-2 border-amber-400/40">
                            🏛️
                        </div>
                        <div>
                            <h1 class="text-sm font-bold tracking-tight text-white leading-tight">
                                POLITEKNIK INDONUSA
                            </h1>
                            <p class="text-[11px] text-amber-400 font-semibold uppercase tracking-wider">
                                SURAKARTA
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2 pt-4">
                        <span class="px-2.5 py-1 bg-amber-400/20 text-amber-300 text-[10px] font-bold uppercase tracking-wider rounded border border-amber-400/30">
                            Portal Resmi
                        </span>
                        <h2 class="text-2xl font-extrabold text-white leading-snug">
                            Sistem Informasi Wisuda & Layanan Kelulusan
                        </h2>
                        <p class="text-xs text-blue-200/90 leading-relaxed">
                            Layanan terpadu pendataan calon wisudawan, pengumpulan pas foto resmi, kutipan motto, dan sistem presensi layar panggung.
                        </p>
                    </div>
                </div>

                <!-- Important Login Notes -->
                <div class="relative z-10 my-8 p-4 rounded-2xl bg-blue-900/60 border border-blue-800 space-y-2.5 text-xs text-blue-200">
                    <div class="flex items-center gap-2 text-amber-300 font-bold text-[11px] uppercase tracking-wide">
                        <span>ℹ️ Petunjuk Akses</span>
                    </div>
                    <ul class="space-y-1.5 text-[11px] text-blue-200/90">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-400 font-bold">•</span>
                            <span>Gunakan Email / NIM dan Password terdaftar Anda.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-400 font-bold">•</span>
                            <span>Sistem melayani akses Wisudawan, Panitia Presensi, dan Administrator.</span>
                        </li>
                    </ul>
                </div>

                <!-- Footer Note -->
                <div class="relative z-10 text-[11px] text-blue-300/60 pt-4 border-t border-blue-900/80 flex items-center justify-between">
                    <span>© {{ new Date().getFullYear() }} Poltekindonusa</span>
                    <span>Surakarta</span>
                </div>
            </div>

            <!-- Right Login Form Side -->
            <div class="lg:col-span-7 bg-white p-8 sm:p-12 flex flex-col justify-between">
                
                <!-- Top Status Header -->
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 bg-blue-50 text-blue-800 text-[11px] font-bold rounded-md border border-blue-200">
                        Politeknik Indonusa Surakarta
                    </span>
                    <span class="text-xs text-slate-400 font-mono">Status: Online</span>
                </div>

                <!-- Form Area -->
                <div class="my-auto py-6 max-w-md w-full mx-auto space-y-6">
                    <div class="space-y-1.5">
                        <h3 class="text-2xl font-black text-slate-900">Masuk Akun</h3>
                        <p class="text-xs text-slate-500">
                            Silakan masukkan kredensial akun Anda untuk mengakses portal wisuda.
                        </p>
                    </div>

                    <!-- Status Notification -->
                    <div v-if="status" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        
                        <!-- Email / NIM Field -->
                        <div class="space-y-1.5">
                            <InputLabel for="email" value="Alamat Email / NIM" class="text-xs font-bold text-slate-700" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                    📧
                                </div>
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="pl-10 block w-full text-sm border-slate-300 focus:border-blue-700 focus:ring-blue-700 rounded-xl py-2.5 shadow-sm"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="nama@email.com atau NIM"
                                />
                            </div>
                            <InputError class="text-xs mt-1" :message="form.errors.email" />
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <InputLabel for="password" value="Kata Sandi (Password)" class="text-xs font-bold text-slate-700" />
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-[11px] font-semibold text-blue-700 hover:underline"
                                >
                                    Lupa Password?
                                </Link>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                    🔒
                                </div>
                                <TextInput
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="pl-10 pr-10 block w-full text-sm border-slate-300 focus:border-blue-700 focus:ring-blue-700 rounded-xl py-2.5 shadow-sm"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs text-slate-400 hover:text-slate-600 font-semibold"
                                >
                                    {{ showPassword ? 'Sembunyikan' : 'Lihat' }}
                                </button>
                            </div>
                            <InputError class="text-xs mt-1" :message="form.errors.password" />
                        </div>

                        <!-- Remember Me Option -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center cursor-pointer">
                                <Checkbox name="remember" v-model:checked="form.remember" class="rounded text-blue-700 border-slate-300 focus:ring-blue-700" />
                                <span class="ms-2 text-xs text-slate-600 font-medium">Ingat Saya</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3 px-5 bg-blue-700 hover:bg-blue-800 text-white font-bold text-sm rounded-xl shadow-md transition duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing">Memproses...</span>
                                <template v-else>
                                    <span>Masuk ke Portal Wisuda</span>
                                    <span>→</span>
                                </template>
                            </button>
                        </div>

                    </form>
                </div>

                <!-- Footer Help Info -->
                <div class="text-center text-xs text-slate-400 pt-4 border-t border-slate-100">
                    Kendala akses akun? Hubungi Panitia Wisuda Politeknik Indonusa Surakarta
                </div>

            </div>

        </div>

    </div>
</template>
