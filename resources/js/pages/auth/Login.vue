<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Lock, Mail, Code2 } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

// Wayfinder Routes
import auth from '@/routes/auth';
import login from '@/routes/login';
import password from '@/routes/password';

defineOptions({
    layout: {
        title: '',
        description: '',
    },
});

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const loginMode = ref<'password' | 'otp'>('password');
const otpSent = ref(false);

const passwordForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const otpForm = useForm({
    email: '',
    code: '',
    remember: false,
});

function sendOtp() {
    otpForm.post(login.otp.send.url(), {
        onSuccess: () => {
            otpSent.value = true;
        },
    });
}

function submitPasswordLogin() {
    passwordForm.post(login.store.url(), {
        onFinish: () => passwordForm.reset('password'),
    });
}

function submitOtpLogin() {
    otpForm.post(login.otp.verify.url());
}
</script>

<template>
    <Head title="Masuk Akun" />

    <!-- Forms -->
    <div class="space-y-6">
        <div class="space-y-2 text-center mb-10">
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Selamat Datang</h1>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">Akses Panel Silsilah Keluarga</p>
        </div>

        <div v-if="status" class="mb-6 text-center text-xs font-bold text-emerald-600 bg-emerald-50 py-3 rounded-2xl border border-emerald-100 animate-in zoom-in duration-300">
            {{ status }}
        </div>

        <!-- Password Login Form -->
        <form v-if="loginMode === 'password'" @submit.prevent="submitPasswordLogin" class="space-y-6">
            <div class="space-y-5">
                <div class="grid gap-2 text-left">
                    <Label for="email" class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Alamat Email</Label>
                    <div class="relative group">
                        <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-emerald-600 transition-colors" />
                        <Input
                            id="email"
                            type="email"
                            v-model="passwordForm.email"
                            required
                            autofocus
                            class="pl-12 py-7 rounded-2xl border-gray-100 bg-gray-50 focus:bg-white transition-all font-bold text-gray-800"
                            placeholder="nama@email.com"
                        />
                    </div>
                    <InputError :message="passwordForm.errors.email" class="px-1 font-bold text-[10px] uppercase" />
                </div>

                <div class="grid gap-2 text-left">
                    <div class="flex items-center justify-between px-1">
                        <Label for="password" class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Kata Sandi</Label>
                        <Link
                            v-if="canResetPassword"
                            :href="password.request.url()"
                            class="text-[9px] font-black uppercase text-emerald-600 hover:text-emerald-600 transition-colors"
                        >
                            Lupa Password?
                        </Link>
                    </div>
                    <div class="relative group">
                        <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-emerald-600 transition-colors z-10" />
                        <PasswordInput
                            id="password"
                            v-model="passwordForm.password"
                            required
                            class="pl-12 py-7 rounded-2xl border-gray-100 bg-gray-50 focus:bg-white transition-all font-bold text-gray-800"
                            placeholder="••••••••"
                        />
                    </div>
                    <InputError :message="passwordForm.errors.password" class="px-1 font-bold text-[10px] uppercase" />
                </div>

                <div class="flex items-center justify-between px-1">
                    <Label for="remember" class="flex items-center gap-3 cursor-pointer group">
                        <Checkbox id="remember" v-model:checked="passwordForm.remember" class="w-5 h-5 rounded-lg border-2 border-gray-200" />
                        <span class="text-[10px] font-bold text-gray-400 group-hover:text-gray-900 transition-colors uppercase tracking-widest">Ingat saya</span>
                    </Label>
                </div>
            </div>

            <Button
                type="submit"
                class="w-full py-7 bg-gray-900 text-white rounded-3xl font-black uppercase text-[11px] tracking-[0.2em] hover:bg-emerald-600 transition-all shadow-xl shadow-gray-200 mt-4"
                :disabled="passwordForm.processing"
            >
                <Spinner v-if="passwordForm.processing" class="mr-2" />
                Masuk Sekarang
            </Button>
        </form>

        <!-- OTP Login Form -->
        <form v-else @submit.prevent="otpSent ? submitOtpLogin() : sendOtp()" class="space-y-6">
            <div class="space-y-5">
                <div class="grid gap-2 text-left">
                    <Label for="otp-email" class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Alamat Email</Label>
                    <div class="relative group">
                        <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-emerald-600 transition-colors" />
                        <Input
                            id="otp-email"
                            type="email"
                            v-model="otpForm.email"
                            required
                            :disabled="otpSent"
                            class="pl-12 py-7 rounded-2xl border-gray-100 bg-gray-50 focus:bg-white transition-all font-bold text-gray-800"
                            placeholder="nama@email.com"
                        />
                    </div>
                    <InputError :message="otpForm.errors.email" class="px-1 font-bold text-[10px] uppercase" />
                </div>

                <div v-if="otpSent" class="grid gap-2 text-left animate-in fade-in slide-in-from-top-4 duration-500">
                    <Label for="otp-code" class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Kode Verifikasi (OTP)</Label>
                    <div class="relative group">
                        <Code2 class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-emerald-600 transition-colors" />
                        <Input
                            id="otp-code"
                            type="text"
                            v-model="otpForm.code"
                            required
                            maxlength="6"
                            class="pl-12 py-7 rounded-2xl border-gray-100 bg-gray-50 focus:bg-white transition-all font-bold text-gray-800 tracking-[0.5em]"
                            placeholder="000000"
                        />
                    </div>
                    <InputError :message="otpForm.errors.code" class="px-1 font-bold text-[10px] uppercase" />
                    <button type="button" @click="otpSent = false" class="text-left px-1 text-[9px] font-bold text-emerald-600 uppercase">Ganti Email?</button>
                </div>
            </div>

            <Button
                type="submit"
                class="w-full py-7 bg-gray-900 text-white rounded-3xl font-black uppercase text-[11px] tracking-[0.2em] hover:bg-emerald-600 transition-all shadow-xl shadow-gray-200 mt-4"
                :disabled="otpForm.processing"
            >
                <Spinner v-if="otpForm.processing" class="mr-2" />
                {{ otpSent ? 'Verifikasi & Masuk' : 'Kirim Kode ke Email' }}
            </Button>
        </form>
    </div>

    <div class="relative mt-6 mb-4">
        <div class="absolute inset-0 flex items-center">
            <span class="w-full border-t border-gray-100"></span>
        </div>
        <div class="relative flex justify-center text-[9px] uppercase font-black tracking-[0.3em]">
            <span class="bg-white px-6 text-gray-300">Pilihan Lainnya</span>
        </div>
    </div>

    <!-- Alternative Login Methods -->
    <div class="grid grid-cols-2 gap-4">
        <!-- Google Login -->
        <a 
            :href="auth.google.url()"
            class="flex items-center justify-center gap-3 py-4 border border-gray-100 rounded-2xl hover:bg-gray-50 hover:border-gray-200 transition-all group shadow-sm bg-white"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-600">Google</span>
        </a>

        <!-- OTP Toggle -->
        <button 
            type="button"
            @click="loginMode = loginMode === 'otp' ? 'password' : 'otp'"
            class="flex items-center justify-center gap-3 py-4 border border-gray-100 rounded-2xl hover:bg-gray-50 hover:border-gray-200 transition-all group shadow-sm bg-white"
        >
            <component :is="loginMode === 'otp' ? Lock : Code2" class="w-4 h-4 text-gray-400 group-hover:text-emerald-600 transition-colors" />
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-600">
                {{ loginMode === 'otp' ? 'Password' : 'OTP' }}
            </span>
        </button>
    </div>
</template>
