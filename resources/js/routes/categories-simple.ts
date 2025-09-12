// Simple route helpers for Categories (temporary replacement for Wayfinder)

export const index = (params?: Record<string, any>) => {
    const url = '/categories';
    const query = params ? '?' + new URLSearchParams(params).toString() : '';
    return { url: url + query };
};

export const show = (id: number | { id: number }, params?: Record<string, any>) => {
    const categoryId = typeof id === 'object' ? id.id : id;
    const url = `/categories/${categoryId}`;
    const query = params ? '?' + new URLSearchParams(params).toString() : '';
    return { url: url + query };
};

export const destroy = (id: number | { id: number }) => {
    const categoryId = typeof id === 'object' ? id.id : id;
    return { url: `/categories/${categoryId}` };
};

export const store = () => {
    return { url: '/categories' };
};

export const update = (id: number | { id: number }) => {
    const categoryId = typeof id === 'object' ? id.id : id;
    return { url: `/categories/${categoryId}` };
};