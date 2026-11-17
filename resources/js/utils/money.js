export function formatPrice(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const number = Number(value);
    if (Number.isNaN(number)) {
        return '';
    }

    if (Math.abs(number - Math.round(number)) < 1e-9) {
        return String(Math.round(number));
    }

    return String(number);
}

export function isPriceInput(value) {
    return value === '' || /^\d+([.,]\d{0,4})?$/.test(value);
}
