import esbuild from 'esbuild'

const isDev = process.argv.includes('--dev')

async function compile(options) {
    const context = await esbuild.context(options)

    if (isDev) {
        await context.watch()
    } else {
        await context.rebuild()
        await context.dispose()
    }
}

const defaultOptions = {
    define: {
        'process.env.NODE_ENV': isDev ? `'development'` : `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    target: ['es2020'],
    minify: !isDev,
    // Optimize for smaller bundles
    splitting: false,
    format: 'iife',
    // Remove unused code
    metafile: !isDev,
    // Enable dead code elimination
    pure: ['console.log'],
    plugins: [{
        name: 'watchPlugin',
        setup: function (build) {
            build.onStart(() => {
                console.log(`Build started at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
            })

            build.onEnd((result) => {
                if (result.errors.length > 0) {
                    console.log(`Build failed at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`, result.errors)
                } else {
                    console.log(`Build finished at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
                    
                    // Log bundle size in production
                    if (!isDev && result.metafile) {
                        const { outputs } = result.metafile
                        Object.entries(outputs).forEach(([file, { bytes }]) => {
                            console.log(`Bundle size: ${file} - ${(bytes / 1024).toFixed(2)}KB`)
                        })
                    }
                }
            })
        }
    }],
}

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/index.js'],
    outfile: './resources/dist/filament-approvals.js',
})
