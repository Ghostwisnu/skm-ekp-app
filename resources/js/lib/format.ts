const numberFormat = new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
});

export function formatNumber(
    value: number | string | null | undefined,
): string {
    return numberFormat.format(Number(value ?? 0));
}

export function formatRupiah(
    value: number | string | null | undefined,
): string {
    return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value ?? 0))}`;
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const [year, month, day] = value.slice(0, 10).split('-');

    return `${day}/${month}/${year}`;
}

export function cleanQuery(
    values: Record<string, string | undefined>,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(values).filter(
            ([, value]) => value !== undefined && value !== '',
        ),
    ) as Record<string, string>;
}
