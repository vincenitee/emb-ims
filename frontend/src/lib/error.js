export function getErrorMessage(err) {
    const status = err.response?.status;
    let message;

    if (status === 400) {
        message = Object.values(err.response?.data?.messages ?? {}).join(' ');
    } else if (status >= 500) {
        message = 'Server error, please try again later';
    } else {
        message = err.response?.data?.messages?.error ?? 'An unexpected error occurred';
    }

    return message;
}