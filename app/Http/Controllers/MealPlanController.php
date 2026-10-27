<?php

namespace App\Http\Controllers;

use App\Models\MealPlanGuest;
use App\Services\MealPlanPdfImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MealPlanController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();
        $guests = MealPlanGuest::whereDate('report_date', $date)->orderBy('room')->orderBy('id')->get();
        $canCheckInRole = $request->user()->canCheckInBuffet();
        $canCheckIn = $canCheckInRole && $this->isWithinCheckInHours();

        $manualNationalities = MealPlanGuest::MANUAL_NATIONALITIES;

        return view('meal-plans.index', compact('date', 'guests', 'canCheckIn', 'canCheckInRole', 'manualNationalities'));
    }

    public function store(Request $request, MealPlanPdfImporter $importer)
    {
        abort_unless($request->user()->isFo(), 403);

        $request->validate(['report' => ['required', 'file', 'mimes:xml,pdf', 'max:20480']]);

        try {
            $result = $importer->import($request->file('report'));
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['report' => $exception->getMessage()]);
        }

        return redirect()->route('meal-plans.index', ['date' => $result['report_date']])
            ->with('success', "Đã nhập {$result['rows']} khách cho ngày ".$result['report_date'].'.');
    }

    public function storeManual(Request $request)
    {
        abort_unless($request->user()->canManuallyAddMealPlanGuest(), 403);

        $data = $request->validate([
            'report_date' => ['required', 'date'],
            'room' => ['required', 'string', 'max:20'],
            'guest_name' => ['required', 'string', 'max:255'],
            'nationality' => ['required', 'string', Rule::in(MealPlanGuest::MANUAL_NATIONALITIES)],
            'is_child' => ['sometimes', 'boolean'],
        ]);

        MealPlanGuest::create([
            ...$data,
            'room_type' => 'Manual',
            'guest_count' => 1,
            'is_child' => (bool) ($data['is_child'] ?? false),
            'arrival' => $data['report_date'],
            'departure' => $data['report_date'],
            'meal_plan' => 'Breakfast Include',
            'source_line' => 'Manual entry',
        ]);

        return redirect()->route('meal-plans.index', ['date' => $data['report_date']])
            ->with('success', 'Đã thêm khách vào danh sách buffet.');
    }

    public function checkIn(Request $request, MealPlanGuest $guest)
    {
        abort_unless($request->user()->canCheckInBuffet(), 403);
        abort_unless($this->isWithinCheckInHours(), 403, 'Check-in buffet chỉ mở từ 05:00 đến trước 12:00.');

        $status = $request->validate([
            'status' => ['nullable', 'string', 'in:checked_in,no_breakfast'],
        ])['status'] ?? 'checked_in';

        $guest->update([
            'buffet_checked_in_at' => now(),
            'buffet_check_in_status' => $status,
        ]);

        return response()->json([
            'checked_in' => true,
            'status' => $status,
            'checked_in_at' => $guest->buffet_checked_in_at?->format('H:i'),
        ]);
    }

    private function isWithinCheckInHours(): bool
    {
        $time = now('Asia/Ho_Chi_Minh')->format('H:i:s');

        return $time >= '05:00:00' && $time <= '12:00:00';
    }
}
