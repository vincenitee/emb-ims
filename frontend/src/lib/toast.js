import { toast } from 'vue-sonner'

export function showToast(title, type = 'default', description, action) {
    const options = {
        description: description ?? '',
        action: action ?? null,
    }

    switch (type) {
        case 'success':
            toast.success(title ?? '', options)
            break
        case 'info':
            toast.info(title ?? '', options)
            break
        case 'warning':
            toast.warning(title ?? '', options)
            break
        case 'error':
            toast.error(title ?? '', options)
            break
        case 'promise':
            // `description` must be the promise itself for this type
            toast.promise(description, {
                loading: title ?? 'Loading...',
                success: 'Done.',
                error: 'Something went wrong.',
            })
            break
        default:
            toast(title ?? '', options)
    }
}
