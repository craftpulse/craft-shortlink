module.exports = {
    content: [
        './src/**/*.{vue,js,ts}',
    ],
    safelist: [
        'bg-gray-100',
        'block',
        'md:block',
        'col-span-3',
        'md:col-span-6',
        'font-semibold',
        'gap-x-4',
        'grid',
        'grid-cols-3',
        'md:grid-cols-6',
        'hidden',
        'p-4',
        'px-4',
        'py-2',
        'text-gray-600',
        'text-xs',
        'uppercase'
    ],
    theme: {
        // Extend the default Tailwind config here
        extend: {
            colors: {
                red: {
                    craft: '#e5422b',
                }
            },
            minHeight: (theme) => ({
                12: theme('height.12')
            })
        },
    },
    important: true,
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
        require('@tailwindcss/line-clamp'),
        require('@tailwindcss/aspect-ratio'),
    ],
};
