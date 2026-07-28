<?php

namespace App\Filament\Pages;

use App\Filament\Imports\ImportRFIDImporter;
use App\Helpers\Helper;
use Dom\Text;

use Filament\Actions\Exports\Models\Export;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use UnitEnum;

class ImportRFIDUrl extends Page implements HasTable,HasForms
{
    use InteractsWithTable,InteractsWithForms;
    protected string $view = 'filament.pages.import-r-f-i-d-url';
    protected static ?string $navigationLabel = 'আর এফ আই ডি ইম্পোর্ট';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowRightEndOnRectangle;

    protected static string | UnitEnum | null $navigationGroup = 'ডাটা ইম্পোর্ট';

    public function getTitle(): string
    {
        return 'আর এফ আই ডি ইম্পোর্ট';
    }

    public ?int $year= null;

    public function mount()
    {
        $this->year = now()->format('Y');
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make(4)
                ->schema([
                    Select::make('year')
                        ->label('সাল')
                        ->options(function () {
                            $currentYear = date('Y');
                            $years = [];
                            for ($year = $currentYear; $year >= 2000; $year--) {
                                $years[$year] = $year;
                            }
                            return $years;
                        })
                        ->placeholder('সাল নির্বাচন করুন')
                        ->reactive(),
                ]),
        ];
    }

    protected function getTableQuery()
    {
        return \App\Models\ImportRFIDUrl::query()
            ->when($this->year, fn ($query) => $query->where('applicant_year', $this->year))
            ->where('status', 'approved');
    }

    protected function getTableColumns(): array
    {
        return [
            // ImageColumn::make('applicant_image')
            //     ->label(__('forms.applicant_image'))
            //     ->disk('public')
            //     ->visibility('public'),
            TextColumn::make('application_number')->label('আবেদন নং')
                ->formatStateUsing(fn (string $state): string => Helper::en2bn($state))
                ->searchable()
                ->sortable(),
            TextColumn::make('license_number')
                ->label('লাইসেন্স নাম্বার')
                ->formatStateUsing(fn (string $state): string => Helper::en2bn($state))
                ->sortable()
                 ->searchable(),
            TextColumn::make('applicant_name')->label('নাম')->sortable(),
            // TextColumn::make('area.area_name')->label('এলাকা')->sortable(),
            TextColumn::make('category.category_name')->label('ক্যাটাগরি')->sortable(),
            TextColumn::make('rfid')->label('আর এফ আই ডি')->sortable(),
            TextColumn::make('url')->label('URL')
                ->formatStateUsing(fn (string $state): string => "<a href='{$state}' target='_blank'>View Details</a>")
                ->html()
                ->sortable(),
            // TextColumn::make('status')->label('অবস্থা')->sortable()
            //     ->badge()
            //     ->color(fn (string $state): string => match ($state) {
            //         'confirmed' => 'info',
            //         'pending' => 'warning',
            //         'approved' => 'success',
            //         'rejected' => 'danger',
            //         'selected'=>'primary',
            //         'unselected'=>'teal',
            //     }),
            // TextColumn::make('applicaton_date')->label('আবেদনের তারিখ')
            //     ->formatStateUsing(fn (string $state): string => Helper::en2bn(date('d-m-Y', strtotime($state))))
            //     ->sortable(),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import RFID')
                ->importer(ImportRFIDImporter::class)
                ->icon(Heroicon::ArrowUpTray)
                ->color('warning'),
            ExportAction::make('XLXS')
                ->label('এক্সেল এক্সপোর্ট')
                ->icon(Heroicon::ArrowDownTray)
                ->exports([
                    ExcelExport::make()
                    ->withFilename('রিক্সার আবেদন লিস্ট_' . now()->format('Y-m-d'))
                    ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ->useTableQuery()
                    ->withColumns([
                        Column::make('serial')
                            ->heading('ক্রমিক নং')
                            ->getStateUsing(function () {
                                static $i = 0;
                                return  Helper::en2bn(++$i); // Bangla serial
                            }),
                        Column::make('applicant_name')
                                ->heading('আবেদনকারীর নাম'),
                        Column::make('category.category_name')
                            ->heading('ক্যাটাগরি')
                            ->formatStateUsing(fn (string $state): string => Helper::en2bn($state)),
                        Column::make('rfid')
                            ->heading('আর এফ আই ডি'),
                        Column::make('url')
                            ->heading('লিংক'),
                    ]),
                ]),
        ];
    }

}
