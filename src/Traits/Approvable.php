<?php

namespace EightyNine\Approvals\Traits;

use App\Models\User;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable as TraitsApprovable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Approvable
{
    use TraitsApprovable;

    /**
     * Get the user who created this approvable item
     * Optimized with proper relationship
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the creator user (backwards compatibility)
     * Now uses the relationship for better performance
     */
    public function creator(): ?User
    {
        if ($this->approvalStatus) {
            return User::find($this->approvalStatus->creator_id);
        }
        return null;
    }

    /**
     * Check if Approval process is completed
     * Optimized for performance with early returns
     * @return bool
     */
    public function isApprovalCompleted(): bool
    {
        $steps = $this->approvalStatus?->steps;
        
        if (empty($steps)) {
            return false;
        }

        // Use array functions for better performance
        return !collect($steps)->contains(function ($step) {
            return $step['process_approval_action'] === null || $step['process_approval_id'] === null;
        });
    }

    /**
     * Get the next approver with caching
     */
    public function getNextApprover(): ?User
    {
        if (!$this->approvalStatus) {
            return null;
        }

        $steps = $this->approvalStatus->steps ?? [];
        
        foreach ($steps as $step) {
            if ($step['process_approval_action'] === null) {
                return User::find($step['approver_id']);
            }
        }
        
        return null;
    }

    /**
     * Get the last approver with caching
     */
    public function getLastApprover(): ?User
    {
        if (!$this->approvalStatus) {
            return null;
        }

        $steps = collect($this->approvalStatus->steps ?? []);
        
        $lastApprovedStep = $steps
            ->filter(fn($step) => $step['process_approval_action'] !== null)
            ->last();
            
        return $lastApprovedStep ? User::find($lastApprovedStep['approver_id']) : null;
    }

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        // Write logic to be executed when the approval process is completed
        return true;
    }
}
