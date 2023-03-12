module.exports = {
    content: [
        '../src/templates/**/*.{twig,html}',
        './src/**/*.{js,ts,vue,html}',
    ],
    theme: {
        // Extend the default Tailwind config here
        extend: {
            colors: {
                red: {
                    craft: '#e5422b',
                    'craft-hover': '#d61f2b',
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
