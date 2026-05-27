export const maskCPF = (value: string): string => {
    return value
        .replace(/\D/g, "") // remove non-digits
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d{1,2})/, "$1-$2")
        .replace(/(-\d{2})\d+?$/, "$1"); // capture 2 digits after -
};

export const maskCNPJ = (value: string): string => {
    return value
        .replace(/\D/g, "")
        .replace(/(\d{2})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1/$2")
        .replace(/(\d{4})(\d)/, "$1-$2")
        .replace(/(-\d{2})\d+?$/, "$1");
};

export const maskPhone = (value: string): string => {
    let v = value.replace(/\D/g, "");
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d)/, "($1) $2");
        v = v.replace(/(\d{4})(\d)/, "$1-$2");
    } else {
        v = v.replace(/(\d{2})(\d)/, "($1) $2");
        v = v.replace(/(\d{5})(\d)/, "$1-$2");
    }
    return v.substring(0, 15);
};

export const maskCEP = (value: string): string => {
    return value
        .replace(/\D/g, "")
        .replace(/(\d{5})(\d)/, "$1-$2")
        .replace(/(-\d{3})\d+?$/, "$1");
};

export const maskCurrency = (value: string): string => {
    let v = value.replace(/\D/g, "");
    if (!v) return "";
    v = (Number(v) / 100).toFixed(2);
    v = v.replace(".", ",");
    v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
    return `R$ ${v}`;
};

export const parseCurrencyToCents = (value: string): number => {
    return Number(value.replace(/\D/g, "")) || 0;
};

export const maskRG = (value: string): string => {
    let raw = value.replace(/[^0-9Xx]/g, "");

    let clean = "";
    for (let i = 0; i < raw.length; i++) {
        const char = raw[i];
        const isLast = i === raw.length - 1;

        if (/\d/.test(char)) {
            clean += char;
        } else if (/[xX]/.test(char)) {
            // X is only allowed if it's the last character of the raw input
            // and we have at least 4 digits before it to prevent letters in the first digits.
            if (isLast && i >= 4) {
                clean += char.toUpperCase();
            }
        }
    }

    // If the clean string ends with X, ensure there is a hyphen before it
    if (clean.endsWith("X")) {
        const digits = clean.slice(0, -1);
        const formattedDigits = digits
            .replace(/(\d{2})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d)/, "$1.$2");
        return `${formattedDigits}-${clean.slice(-1)}`;
    }

    // For purely numeric strings or standard 9-digit formats
    return clean
        .substring(0, 9)
        .replace(/(\d{2})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})([\dX])/, "$1-$2");
};

/**
 * Reusable event handler to apply a mask function and sync the DOM element's value,
 * solving the Vue 3 virtual DOM synchronization bug when invalid characters are filtered.
 */
export const handleMask = (
    event: Event,
    maskFn: (val: string) => string,
    updateFn: (val: string) => void,
) => {
    const target = event.target as HTMLInputElement;
    if (!target) return;
    const masked = maskFn(target.value);
    updateFn(masked);
    target.value = masked;
};

export const onRGKeypress = (event: KeyboardEvent) => {
    const char = event.key;
    if (char.length === 1 && !/^[0-9Xx]$/.test(char)) {
        event.preventDefault();
    }
};
