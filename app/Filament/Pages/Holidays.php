<?php

namespace App\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class Holidays extends Page
{
    protected string $view = 'filament.pages.holidays';

    protected static ?string $title = 'Hari Libur';

    protected static ?string $navigationLabel = 'Hari Libur';

    protected static string | UnitEnum | null $navigationGroup = 'Kelola';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sun';

    protected static ?int $navigationSort = 3;

    protected ?string $subheading = 'Pengaturan hari libur nasional, perusahaan, dan cabang.';

    public ?int $holidayId = null;
    public string $date = '';
    public ?string $workLocationId = null;
    public ?string $note = null;

    public string $filterMonth = '';
    public string $filterWorkLocationId = '';
    public string $viewMode = 'list';
    public int $holidayPage = 1;

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'workLocationId' => ['nullable', 'integer', 'exists:work_locations,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updatedFilterMonth(): void
    {
        $this->holidayPage = 1;
    }

    public function updatedFilterWorkLocationId(): void
    {
        $this->holidayPage = 1;
    }

    public function updatedViewMode(): void
    {
        $this->holidayPage = 1;
    }

    public function saveHoliday(): void
    {
        $validated = $this->validate();

        $duplicate = DB::table('holidays')
            ->where('date', $validated['date'])
            ->when(
                filled($validated['workLocationId']),
                fn ($query) => $query->where('work_location_id', $validated['workLocationId']),
                fn ($query) => $query->whereNull('work_location_id'),
            )
            ->when($this->holidayId, fn ($query) => $query->where('id', '!=', $this->holidayId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'date' => 'Tanggal libur dengan cakupan cabang tersebut sudah terdaftar.',
            ]);
        }

        $data = [
            'date' => $validated['date'],
            'work_location_id' => filled($validated['workLocationId']) ? $validated['workLocationId'] : null,
            'note' => $validated['note'],
            'updated_at' => now(),
        ];

        if ($this->holidayId) {
            DB::table('holidays')->where('id', $this->holidayId)->update($data);
            $message = 'Hari libur berhasil diperbarui.';
        } else {
            $data['created_at'] = now();
            DB::table('holidays')->insert($data);
            $message = 'Hari libur berhasil ditambahkan.';
        }

        $this->resetForm();
        $this->holidayPage = 1;

        Notification::make()->title($message)->success()->send();
    }

    public function editHoliday(int $id): void
    {
        $holiday = DB::table('holidays')->where('id', $id)->first();
        abort_unless($holiday, 404);

        $this->holidayId = (int) $holiday->id;
        $this->date = $holiday->date;
        $this->workLocationId = $holiday->work_location_id === null ? null : (string) $holiday->work_location_id;
        $this->note = $holiday->note;
    }

    public function deleteHoliday(int $id): void
    {
        $deleted = DB::table('holidays')->where('id', $id)->delete();

        if (! $deleted) {
            Notification::make()->title('Data hari libur tidak ditemukan.')->warning()->send();
            return;
        }

        if ($this->holidayId === $id) {
            $this->resetForm();
        }

        Notification::make()->title('Hari libur berhasil dihapus.')->success()->send();
    }

    public function resetForm(): void
    {
        $this->reset(['holidayId', 'date', 'workLocationId', 'note']);
        $this->resetValidation();
    }

    protected function getViewData(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->filterMonth ?: now()->format('Y-m'))->startOfMonth();
        $startDate = $month->copy()->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->addDays(41);

        $baseQuery = DB::table('holidays')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'holidays.work_location_id')
            ->whereBetween('holidays.date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->when(
                filled($this->filterWorkLocationId),
                fn ($query) => $query->where(function ($query) {
                    $query->whereNull('holidays.work_location_id')
                        ->orWhere('holidays.work_location_id', $this->filterWorkLocationId);
                }),
            );

        $holidays = (clone $baseQuery)
            ->orderByDesc('holidays.date')
            ->orderBy('work_locations.name')
            ->paginate(15, [
                'holidays.id',
                'holidays.date',
                'holidays.work_location_id',
                'holidays.note',
                'work_locations.name as work_location_name',
            ], 'holidayPage', $this->holidayPage);

        $calendarHolidays = DB::table('holidays')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'holidays.work_location_id')
            ->whereBetween('holidays.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when(
                filled($this->filterWorkLocationId),
                fn ($query) => $query->where(function ($query) {
                    $query->whereNull('holidays.work_location_id')
                        ->orWhere('holidays.work_location_id', $this->filterWorkLocationId);
                }),
            )
            ->orderBy('holidays.date')
            ->get([
                'holidays.id',
                'holidays.date',
                'holidays.work_location_id',
                'holidays.note',
                'work_locations.name as work_location_name',
            ])
            ->groupBy('date');

        $calendarDays = collect();
        for ($day = $startDate->copy(); $day <= $endDate; $day->addDay()) {
            $dateKey = $day->toDateString();
            $calendarDays->push([
                'date' => $dateKey,
                'day' => $day->day,
                'inMonth' => $day->month === $month->month,
                'isToday' => $dateKey === today()->toDateString(),
                'holidays' => $calendarHolidays->get($dateKey, collect()),
            ]);
        }

        return [
            'holidays' => $holidays,
            'calendarDays' => $calendarDays,
            'calendarMonthLabel' => $month->translatedFormat('F Y'),
            'workLocations' => DB::table('work_locations')
                ->orderBy('is_active', 'desc')
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
        ];
    }
}
