<?php

namespace App\Filament\Imports;

use App\Models\Applicant;
use App\Models\ImportRFIDUrl;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ImportRFIDImporter extends Importer
{
    protected static ?string $model = ImportRFIDUrl::class;

    public static function getColumns(): array
    {
        return [
             ImportColumn::make('license_number')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('rfid')
                ->requiredMapping()
                ->rules(['required']),
        ];
    }

    public function resolveRecord(): ?ImportRFIDUrl
    {
        $input = ltrim($this->data['license_number'], '0');

        return ImportRFIDUrl::query()
            ->whereRaw("TRIM(LEADING '0' FROM license_number) = ?", [$input])
            ->first();
    }


     /**
     * Only update rfid and url
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            'rfid' => $data['rfid'] ?? null,
            
        ];
    }

    protected function afterSave(): void
    {
        $this->record->update([
            'url' => 'https://dcb-rickshaw.com/applicant/' . $this->record->id . '/details',
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your import r f i d import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
