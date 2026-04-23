import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function initializeFlashToast(): void {
    router.on('finish', (event: any) => {
        // Try to get props from event detail or the router global object safely
        const props: any = event?.detail?.page?.props || (router as any).page?.props;
        
        if (!props || !props.flash) {
            return;
        }

        const flash = props.flash;

        if (flash.success) {
            toast.success(flash.success);
            // Clear to prevent showing again on local state changes
            flash.success = null;
        }

        if (flash.error) {
            toast.error(flash.error);
            flash.error = null;
        }
    });
}
