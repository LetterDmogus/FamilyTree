<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Trees, ArrowRight, LogIn, UserPlus } from 'lucide-vue-next'
import FlashNotification from '@/components/FlashNotification.vue'
import { Toaster } from '@/components/ui/sonner'

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
})
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="min-h-screen bg-[#fcfcfc] flex flex-col items-center justify-center p-6 font-sans select-none">
        
        <!-- Center Content -->
        <div class="max-w-md w-full text-center space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-1000">
            
            <!-- Logo & Brand -->
            <div class="flex flex-col items-center gap-6">
                <div class="p-5 bg-emerald-600 text-white rounded-[2.5rem] shadow-2xl shadow-emerald-200">
                    <Trees class="w-12 h-12 stroke-[2.5]" />
                </div>
                <div class="space-y-2">
                    <h1 class="text-5xl font-black text-gray-900 tracking-tighter">Family Tree</h1>
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-[0.2em]">Visualisasikan Silsilah & Cerita Keluarga</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid gap-3 pt-6">
                <template v-if="$page.props.auth.user">
                    <Link href="/dashboard" class="w-full py-5 bg-gray-900 text-white rounded-3xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-emerald-600 transition-all shadow-xl shadow-gray-200 flex items-center justify-center gap-3">
                        Masuk ke Dashboard
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </template>

                <template v-else>
                    <Link href="/login" class="w-full py-5 bg-gray-900 text-white rounded-3xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-emerald-600 transition-all shadow-xl shadow-gray-200 flex items-center justify-center gap-3">
                        <LogIn class="w-4 h-4" />
                        Masuk Akun
                    </Link>
                </template>
            </div>

            <!-- Footer -->
            <div class="pt-12">
                <p class="text-[9px] font-black text-gray-300 uppercase tracking-widest leading-loose">
                    Dibuat dengan kasih sayang &bull; &copy; {{ new Date().getFullYear() }} Family Tree Project
                </p>
            </div>

        </div>

        <FlashNotification />
        <Toaster />
    </div>
</template>

<style scoped>
.min-h-screen {
    background-image: radial-gradient(#cbd5e1 1.2px, transparent 1px);
    background-size: 40px 40px;
}
</style>
