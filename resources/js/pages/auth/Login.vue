<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Lock, Mail } from 'lucide-vue-next';

defineOptions({
    layout: {
        title: 'Selamat Datang Kembali',
        description: 'Akses panel pengelolaan silsilah keluarga.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Masuk Akun" />

    <div v-if="status" class="mb-6 text-center text-xs font-bold text-emerald-600 bg-emerald-50 py-3 rounded-2xl border border-emerald-100 animate-in zoom-in duration-300">
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="space-y-6"
    >
        <div class="space-y-5">
            <!-- Email Input -->
            <div class="grid gap-2 text-left">
                <Label for="email" class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Alamat Email</Label>
                <div class="relative group">
                    <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-indigo-600 transition-colors" />
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        class="pl-12 py-7 rounded-2xl border-gray-100 bg-gray-50 focus:bg-white transition-all font-bold text-gray-800"
                        placeholder="nama@email.com"
                    />
                </div>
                <InputError :message="errors.email" class="px-1 font-bold text-[10px] uppercase" />
            </div>

            <!-- Password Input -->
            <div class="grid gap-2 text-left">
                <div class="flex items-center justify-between px-1">
                    <Label for="password" class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Kata Sandi</Label>
                    <Link
                        v-if="canResetPassword"
                        :href="request().url"
                        class="text-[9px] font-black uppercase text-blue-600 hover:text-indigo-600 transition-colors"
                    >
                        Lupa Password?
                    </Link>
                </div>
                <div class="relative group">
                    <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-indigo-600 transition-colors z-10" />
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        class="pl-12 py-7 rounded-2xl border-gray-100 bg-gray-50 focus:bg-white transition-all font-bold text-gray-800"
                        placeholder="••••••••"
                    />
                </div>
                <InputError :message="errors.password" class="px-1 font-bold text-[10px] uppercase" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between px-1">
                <Label for="remember" class="flex items-center gap-3 cursor-pointer group">
                    <Checkbox id="remember" name="remember" class="w-5 h-5 rounded-lg border-2 border-gray-200" />
                    <span class="text-[10px] font-bold text-gray-400 group-hover:text-gray-900 transition-colors uppercase tracking-widest">Ingat saya</span>
                </Label>
            </div>
        </div>

        <!-- Submit Button -->
        <Button
            type="submit"
            class="w-full py-7 bg-gray-900 text-white rounded-3xl font-black uppercase text-[11px] tracking-[0.2em] hover:bg-indigo-600 transition-all shadow-xl shadow-gray-200 mt-4"
            :disabled="processing"
        >
            <Spinner v-if="processing" class="mr-2" />
            Masuk Sekarang
        </Button>
    </Form>
</template>
