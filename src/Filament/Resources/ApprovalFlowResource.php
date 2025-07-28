<?php

namespace EightyNine\Approvals\Filament\Resources;

use EightyNine\Approvals\Filament\Resources\ApprovalFlowResource\Pages;
use EightyNine\Approvals\Filament\Resources\ApprovalFlowResource\RelationManagers;
use App\Models\ApprovalFlow;
use EightyNine\Approvals\Filament\Resources\ApprovalFlowResource\RelationManagers\StepsRelationManager;
use EightyNine\Approvals\Models\ApprovableModel;
use EightyNine\Approvals\Services\ModelScannerService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlow;

class ApprovalFlowResource extends Resource
{
    protected static ?string $model = ProcessApprovalFlow::class;

    protected static ?string $modelLabel = 'Approval flow';

    protected static ?string $pluralModelLabel = 'Approval flows';

    public static function getNavigationIcon(): ?string
    {
        return  config('approvals.navigation.icon', 'heroicon-o-clipboard-document-check');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('approvals.navigation.should_register_navigation', true);
    }

    public static function getLabel(): string
    {
        return __('filament-approvals::approvals.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-approvals::approvals.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('approvals.navigation.sort', 1);
    }

    public static function getPluralLabel(): string
    {
        return __('filament-approvals::approvals.navigation.plural_label');
    }
    
    public static function form(Form $form): Form
    {
        // Use dependency injection for better performance
        $modelScanner = app(ModelScannerService::class);
        $models = $modelScanner->getApprovableModels();

        return $form
            ->columns(12)
            ->schema([
                TextInput::make("name")
                    ->columnSpan(fn($context) => $context === 'create' ? 12 : 6)
                    ->required()
                    ->maxLength(255),
                    
                Select::make('approvable_type')
                    ->columnSpan(fn($context) => $context === 'create' ? 12 : 6)
                    ->options(function() use ($models) {
                        // Optimize model name processing
                        return collect($models)->mapWithKeys(function($model) {
                            $shortName = class_basename($model);
                            return [$model => $shortName];
                        })->toArray();
                    })
                    ->required()
                    ->searchable(),
                    
                Forms\Components\Placeholder::make('warning')
                    ->visible(fn() => empty($models))
                    ->columnSpanFull()
                    ->content(new HtmlString('No models in <b>App\Models</b> extend the <b>ApprovableModel</b>. Please see our documentation.'))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // Optimize query with proper relationships
                static::getModel()::query()
                    ->when(
                        config('approvals.performance.eager_load_relationships', true),
                        fn($query) => $query->with(['steps'])
                    )
            )
            ->columns([
                TextColumn::make("name")
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make("approvable_type")
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => class_basename($state)),
                    
                TextColumn::make('steps_count')
                    ->counts('steps')
                    ->label('Steps')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add useful filters for better UX
                Tables\Filters\SelectFilter::make('approvable_type')
                    ->options(function() {
                        $modelScanner = app(ModelScannerService::class);
                        $models = $modelScanner->getApprovableModels();
                        return collect($models)->mapWithKeys(function($model) {
                            return [$model => class_basename($model)];
                        })->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll(config('approvals.ui.poll_interval', null)) // Optional auto-refresh
            ->persistFiltersInSession()
            ->persistSortInSession();
    }

    public static function getRelations(): array
    {
        return [
            StepsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovalFlows::route('/'),
//            'create' => Pages\CreateApprovalFlow::route('/create'),
            'edit' => Pages\EditApprovalFlow::route('/{record}/edit'),
        ];
    }
    
    /**
     * Get the Eloquent query builder for the resource
     * Optimized for performance
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Apply performance optimizations based on config
        if (config('approvals.performance.eager_load_relationships', true)) {
            $query->with(['steps']);
        }
        
        return $query;
    }
}
