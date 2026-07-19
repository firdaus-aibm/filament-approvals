import { promises as fs } from 'fs'
import path from 'path'

async function analyzeBundle() {
    const distPath = './resources/dist/'
    
    try {
        const files = await fs.readdir(distPath)
        let totalSize = 0
        
        console.log('\n=== Bundle Size Analysis ===')
        
        for (const file of files) {
            if (file === '.gitkeep') continue
            
            const filePath = path.join(distPath, file)
            const stats = await fs.stat(filePath)
            const sizeKB = (stats.size / 1024).toFixed(2)
            totalSize += stats.size
            
            console.log(`${file}: ${sizeKB}KB`)
        }
        
        console.log(`\nTotal bundle size: ${(totalSize / 1024).toFixed(2)}KB`)
        
        // Recommendations
        if (totalSize > 50 * 1024) { // 50KB
            console.log('\n⚠️  Bundle size is large. Consider:')
            console.log('   - Removing unused CSS with PurgeCSS')
            console.log('   - Code splitting for JavaScript')
            console.log('   - Using dynamic imports')
        } else {
            console.log('\n✅ Bundle size is optimized!')
        }
        
    } catch (error) {
        console.error('Error analyzing bundle:', error.message)
    }
}

analyzeBundle()