export const maskCPF = (value: string) => {
    return value
        .replace(/\D/g, '') // remove non-digits
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})/, '$1-$2')
        .replace(/(-\d{2})\d+?$/, '$1') // capture 2 digits after -
}

export const maskCNPJ = (value: string) => {
    return value
        .replace(/\D/g, '')
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2')
        .replace(/(-\d{2})\d+?$/, '$1')
}

export const maskPhone = (value: string) => {
    let v = value.replace(/\D/g, '')
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d)/, '($1) $2')
        v = v.replace(/(\d{4})(\d)/, '$1-$2')
    } else {
        v = v.replace(/(\d{2})(\d)/, '($1) $2')
        v = v.replace(/(\d{5})(\d)/, '$1-$2')
    }
    return v.substring(0, 15)
}

export const maskCEP = (value: string) => {
    return value
        .replace(/\D/g, '')
        .replace(/(\d{5})(\d)/, '$1-$2')
        .replace(/(-\d{3})\d+?$/, '$1')
}

export const maskCurrency = (value: string) => {
    let v = value.replace(/\D/g, '')
    if (!v) return ''
    v = (Number(v) / 100).toFixed(2)
    v = v.replace('.', ',')
    v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.')
    return `R$ ${v}`
}

export const parseCurrencyToCents = (value: string): number => {
    return Number(value.replace(/\D/g, '')) || 0;
}
