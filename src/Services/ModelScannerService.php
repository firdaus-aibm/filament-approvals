<?php

namespace EightyNine\Approvals\Services;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ModelScannerService
{
    private const CACHE_KEY = 'filament_approvals_approvable_models';
    private const CACHE_TTL = 3600; // 1 hour cache
    
    private static $models = null; // Memoization
    
    /**
     * Scan for models extending from a specific base model.
     * Uses caching and memoization for improved performance.
     *
     * @return array
     */
    public function getApprovableModels(): array
    {
        // Return memoized result if available
        if (self::$models !== null) {
            return self::$models;
        }
        
        // Try to get from cache first
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return self::$models = $cached;
        }
        
        try {
            $models = $this->scanModels();
            
            // Cache the results
            Cache::put(self::CACHE_KEY, $models, self::CACHE_TTL);
            
            return self::$models = $models;
        } catch (\Exception $e) {
            Log::error('Failed to scan approvable models: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear the models cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$models = null;
    }
    
    /**
     * Perform the actual model scanning
     */
    private function scanModels(): array
    {
        $directory = app_path('Models');
        
        // Check if Models directory exists
        if (!is_dir($directory)) {
            return [];
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $models = [];
        
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            
            $class = $this->getClassFullName($file->getRealPath());
            
            if ($this->isApprovableModel($class)) {
                $models[$class] = $class;
            }
        }
        
        return $models;
    }
    
    /**
     * Check if a class is an approvable model
     */
    private function isApprovableModel(string $class): bool
    {
        if (empty($class) || !class_exists($class)) {
            return false;
        }
        
        try {
            return is_subclass_of($class, 'EightyNine\Approvals\Models\ApprovableModel');
        } catch (\Exception $e) {
            Log::warning("Failed to check if {$class} is approvable: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract the full class name including the namespace from a PHP file.
     * Optimized for better performance.
     *
     * @param string $path
     * @return string
     */
    private function getClassFullName($path): string
    {
        $content = file_get_contents($path);
        
        if ($content === false) {
            return '';
        }
        
        $namespace = '';
        $class = '';
        
        // Use more efficient regex matching
        if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
            $namespace = trim($matches[1]);
        }
        
        if (preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $content, $matches)) {
            $class = $matches[1];
        }
        
        return $namespace && $class ? $namespace . '\\' . $class : '';
    }
}
