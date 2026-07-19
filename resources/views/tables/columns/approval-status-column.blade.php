<div class="approval-status-column">
    @php
        $record = $getRecord();
        $approvalStatus = $record->approvalStatus;
        $isCompleted = $record->isApprovalCompleted();
        $creator = $record->creator();
        $nextApprover = $isCompleted ? null : $record->getNextApprover();
        $lastApprover = $isCompleted ? $record->getLastApprover() : null;
    @endphp

    @if($approvalStatus)
        <div class="space-y-1">
            <p class="px-3 text-sm">
                @if ($isCompleted)
                    <span class="approval-status-indicator approval-status-approved">
                        {{ __('filament-approvals::approvals.status_column.approval_complete') }}
                    </span>
                    {{ __('filament-approvals::approvals.status_column.approval_by_prefix') }}
                    <span class="font-medium">
                        {{ $lastApprover?->name ?? $creator?->name ?? __('Unknown') }}
                    </span>
                @else
                    <span class="approval-status-indicator approval-status-pending">
                        {{ $approvalStatus->status }}
                    </span>
                    {{ __('filament-approvals::approvals.status_column.approval_by_prefix') }}
                    <span class="font-medium">
                        {{ $nextApprover?->name ?? $creator?->name ?? __('Unknown') }}
                    </span>
                @endif
            </p>
            
            <p class="px-3 text-xs text-gray-600 dark:text-gray-400">
                {{ $isCompleted ?
                    __('filament-approvals::approvals.status_column.approval_complete') :
                    __('filament-approvals::approvals.status_column.approval_in_process') }}
            </p>
        </div>
    @else
        <span class="approval-status-indicator approval-status-rejected">
            {{ __('filament-approvals::approvals.status_column.approval_status_does_not_exist') }}
        </span>
    @endif
</div>
