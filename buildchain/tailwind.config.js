// module exports
module.exports = {
    mode: 'jit',
    purge: {
        content: [
            '../src/templates/**/*.{twig,html}',
            './src/vue/**/*.{vue,html}',
        ],
        layers: [
            'base',
            'components',
            'utilities',
        ],
        mode: 'layers',
        options: {
            whitelist: [
                './src/css/components/*.css',
            ],
        }
    },
    important: true,
    theme: {
        extend: {
            colors: {
              red: {
                  craft: '#e5422b',
              }
            },
            minHeight: (theme) => ({
                12: theme('height.12')
            })
        }
    },
    corePlugins: {},
    plugins: [],
};
