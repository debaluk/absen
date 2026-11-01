<?php

namespace App\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;
use UnitEnum;

class Holidays extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.holidays';

    protected static ?string $title = 'Hari Libur';
    protected static ?string $navigationLabel = 'Hari Libur';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sun';
    protected static ?int $navigationSort = 3;
    protected ?string $subheading = 'Pengaturan hari libur nasional, perusahaan, dan cabang.';

    public ?int $holidayId = null;
    public string $date = '';
    public array $workLocationIds = [];
    public ?string $note = null;

    public string $filterMonth = '';
    public string $filterWorkLocationId = '';
    public string $viewMode = 'list';

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'workLocationIds' => ['array'],
            'workLocationIds.*' => ['integer', 'distinct', 'exists:work_locations,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updatedFilterMonth(): void
    {
        $this->resetPage('holidayPage');
    }

    public function updatedFilterWorkLocationId(): void
    {
        $this->resetPage('holidayPage');
    }

    public function updatedViewMode(): void
    {
        $this->resetPage('holidayPage');
    }

    public function saveHoliday(): void
    {
        $validated = $this->validate();
        $locationIds = collect($validated['workLocationIds'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $duplicate = DB::table('holidays')
            ->whereDate('date', $validated['date'])
            ->where('note', $validated['note'])
            ->when($this->holidayId, fn ($query) => $query->where('id', '!=', $this->holidayId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'date' => 'Hari libur dengan tanggal dan keterangan yang sama sudah terdaftar.',
            ]);
        }

        $data = [
            'date' => $validated['date'],
            'note' => $validated['note'],
            'updated_at' => now(),
        ];

        [$holidayId, $message] = DB::transaction(function () use ($data, $locationIds) {
            if ($this->holidayId) {
                $holidayId = $this->holidayId;
                DB::table('holidays')->where('id', $holidayId)->update($data);
                $message = 'Hari libur berhasil diperbarui.';
            } else {
                $data['created_at'] = now();
                $holidayId = DB::table('holidays')->insertGetId($data);
                $message = 'Hari libur berhasil ditambahkan.';
            }

            DB::table('holiday_work_locations')->where('holiday_id', $holidayId)->delete();

            if ($locationIds->isNotEmpty()) {
                $now = now();
                DB::table('holiday_work_locations')->insert(
                    $locationIds->map(fn ($locationId) => [
                        'holiday_id' => $holidayId,
                        'work_location_id' => $locationId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            }

            return [$holidayId, $message];
        });

        $this->resetForm();
        $this->resetPage('holidayPage');

        Notification::make()->title($message)->success()->send();
    }

    public function editHoliday(int $id): void
    {
        $holiday = DB::table('holidays')->where('id', $id)->first();
        abort_unless($holiday, 404);

        $this->holidayId = (int) $holiday->id;
        $this->date = $holiday->date;
        $this->workLocationIds = DB::table('holiday_work_locations')
            ->where('holiday_id', $id)
            ->pluck('work_location_id')
            ->map(fn ($id) => (string) $id)
            ->all();
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
        $this->reset(['holidayId', 'date', 'workLocationIds', 'note']);
        $this->resetValidation();
    }

    protected function getViewData(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->filterMonth ?: now()->format('Y-m'))->startOfMonth();
        $startDate = $month->copy()->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->addDays(41);

        $scopeNames = DB::table('holiday_work_locations')
            ->join('work_locations', 'work_locations.id', '=', 'holiday_work_locations.work_location_id')
            ->select(
                'holiday_work_locations.holiday_id',
                DB::raw('GROUP_CONCAT(work_locations.name ORDER BY work_locations.name SEPARATOR ", ") as work_location_names')
            )
            ->groupBy('holiday_work_locations.holiday_id');

        $holidays = DB::table('holidays')
            ->leftJoinSub($scopeNames, 'holiday_scopes', fn ($join) => $join->on('holiday_scopes.holiday_id', '=', 'holidays.id'))
            ->whereBetween('holidays.date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->when(
                filled($this->filterWorkLocationId),
                fn ($query) => $query->where(function ($query) {
                    $query->whereNotExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id');
                    })->orWhereExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id')
                            ->where('holiday_work_locations.work_location_id', $this->filterWorkLocationId);
                    });
                }),
            )
            ->orderByDesc('holidays.date')
            ->orderBy('holidays.id')
            ->paginate(15, ['holidays.*', 'holiday_scopes.work_location_names'], 'holidayPage');

        $calendarHolidays = DB::table('holidays')
            ->leftJoinSub($scopeNames, 'holiday_scopes', fn ($join) => $join->on('holiday_scopes.holiday_id', '=', 'holidays.id'))
            ->whereBetween('holidays.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when(
                filled($this->filterWorkLocationId),
                fn ($query) => $query->where(function ($query) {
                    $query->whereNotExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id');
                    })->orWhereExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id')
                            ->where('holiday_work_locations.work_location_id', $this->filterWorkLocationId);
                    });
                }),
            )
            ->orderBy('holidays.date')
            ->orderBy('holidays.id')
            ->get(['holidays.*', 'holiday_scopes.work_location_names'])
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
