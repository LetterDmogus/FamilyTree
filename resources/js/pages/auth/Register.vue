<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Share2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

// Wayfinder Routes
import auth from '@/routes/auth';
import login from '@/routes/login';
import register from '@/routes/register';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(register.store.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Register" />

    <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    v-model="form.name"
                    :tabindex="1"
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    v-model="form.email"
                    :tabindex="2"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    v-model="form.password"
                    :tabindex="3"
                    autocomplete="new-password"
                    placeholder="Password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    v-model="form.password_confirmation"
                    :tabindex="4"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                Create account
            </Button>

            <div class="relative my-2">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-gray-100"></span>
                </div>
                <div class="relative flex justify-center text-[9px] uppercase font-black tracking-widest">
                    <span class="bg-white px-4 text-gray-300">Atau</span>
                </div>
            </div>

            <!-- Google Login -->
            <a 
                :href="auth.google.url()"
                class="flex items-center justify-center gap-4 w-full py-2.5 border border-gray-100 rounded-lg hover:bg-gray-50 hover:border-gray-200 transition-all group"
            >
                <Share2 class="w-4 h-4 text-gray-400 group-hover:text-emerald-600 transition-colors" />
                <span class="text-sm font-medium text-gray-600 group-hover:text-gray-900">Daftar dengan Google</span>
            </a>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login.store.url()"
                class="underline underline-offset-4"
                :tabindex="6"
                >Log in</TextLink
            >
        </div>
    </form>
</template>
