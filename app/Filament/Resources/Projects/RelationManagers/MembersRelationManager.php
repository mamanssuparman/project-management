<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use App\Events\ProjectMemberAttached;
use App\Events\ProjectMemberDetached;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->members_count ?? $ownerRecord->members()->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->label('Add Member')
                    ->after(function (Model $record) {
                        $project = $this->getOwnerRecord();
                        $user = User::find($record->id);
                        $assignedBy = auth()->user();
                        
                        if ($user && $assignedBy) {
                            ProjectMemberAttached::dispatch($project, $user, $assignedBy);
                        }
                    }),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Remove')
                    ->after(function (Model $record) {
                        $project = $this->getOwnerRecord();
                        $user = User::find($record->id);
                        $removedBy = auth()->user();
                        
                        if ($user && $removedBy) {
                            ProjectMemberDetached::dispatch($project, $user, $removedBy);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ]);
    }
}