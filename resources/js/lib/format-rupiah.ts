export const formatRupiah = (value: number | string): string => {
    const number = typeof value === 'string' ? Number(value) : value;

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })
        .format(number)
        .replace('Rp', 'Rp ');
};
