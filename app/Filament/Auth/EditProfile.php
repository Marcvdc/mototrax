<?php

namespace App\Filament\Auth;

use App\Enums\MotorType;
use App\Models\User;
use App\Services\ProfileService;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar')
                    ->label('Avatar')
                    ->avatar()
                    ->image()
                    ->disk(ProfileService::DISK)
                    ->directory(ProfileService::DIRECTORY)
                    ->visibility('public')
                    ->preventFilePathTampering()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                TextInput::make('location')
                    ->label('Locatie')
                    ->maxLength(100),
                Select::make('motor_type')
                    ->label('Motor-type')
                    ->options(MotorType::class),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        return app(ProfileService::class)->update($record, $data);
    }
}
