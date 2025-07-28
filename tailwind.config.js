module.exports = {
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './src/**/*.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                // Add only necessary color overrides
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
            },
        }
    },
    plugins: [
        // Only include necessary plugins to reduce bundle size
        require('@tailwindcss/forms'),
    ],
    // Optimize for production
    corePlugins: {
        // Disable unused core plugins for smaller CSS
        container: false,
        // Add other unused plugins here as needed
    },
    // Enable JIT mode for better performance
    mode: 'jit',
}
