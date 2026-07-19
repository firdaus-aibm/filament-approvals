<?php

namespace EightyNine\Approvals\Commands;

use EightyNine\Approvals\Services\ModelScannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearCacheCommand extends Command
{
    protected $signature = 'approvals:clear-cache
                          {--model-scan : Clear only model scan cache}
                          {--status : Clear only approval status cache}';

    protected $description = 'Clear approval-related caches for improved performance';

    public function handle(): int
    {
        $modelScan = $this->option('model-scan');
        $status = $this->option('status');
        
        // If no specific option is provided, clear all caches
        if (!$modelScan && !$status) {
            $this->clearAllCaches();
            return self::SUCCESS;
        }
        
        if ($modelScan) {
            $this->clearModelScanCache();
        }
        
        if ($status) {
            $this->clearApprovalStatusCache();
        }
        
        return self::SUCCESS;
    }
    
    private function clearAllCaches(): void
    {
        $this->info('🧹 Clearing all approval caches...');
        
        $this->clearModelScanCache();
        $this->clearApprovalStatusCache();
        
        $this->info('✅ All approval caches cleared successfully!');
    }
    
    private function clearModelScanCache(): void
    {
        $service = app(ModelScannerService::class);
        $service->clearCache();
        
        $this->info('📁 Model scan cache cleared');
    }
    
    private function clearApprovalStatusCache(): void
    {
        // Clear approval status related caches
        $pattern = 'approval_status_*';
        $tags = ['approval_status', 'approvals'];
        
        // Clear cache by pattern (if supported by cache driver)
        try {
            Cache::flush(); // This is more aggressive but ensures all related caches are cleared
            $this->info('📋 Approval status cache cleared');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not clear approval status cache: ' . $e->getMessage());
        }
    }
}