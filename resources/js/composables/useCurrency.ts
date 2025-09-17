export function useCurrency() {
    const currencyFormatter = new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    });

    const numberFormatter = new Intl.NumberFormat('en-GH');

    const formatCurrency = (amount: number): string => {
        if (isNaN(amount)) return 'GH₵0.00';
        return currencyFormatter.format(amount);
    };

    const formatNumber = (amount: number): string => {
        if (isNaN(amount)) return '0';
        return numberFormatter.format(amount);
    };

    return {
        formatCurrency,
        formatNumber,
        currencyFormatter,
        numberFormatter,
    };
}