<?php

namespace App\Http\Controllers;

use App\Models\MealPlanGuest;
use App\Services\MealPlanPdfImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MealPlanController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();
        $guests = MealPlanGuest::whereDate('report_date', $date)->orderBy('room')->orderBy('id')->get();

        return view('meal-plans.index', compact('date', 'guests'));
    }

    public function store(Request $request, MealPlanPdfImporter $importer)
    {
        $request->validate(['report' => ['required', 'file', 'mimes:xml,pdf', 'max:20480']]);

        try {
            $result = $importer->import($request->file('report'));
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['report' => $exception->getMessage()]);
        }

        return redirect()->route('meal-plans.index', ['date' => $result['report_date']])
            ->with('success', "Đã nhập {$result['rows']} khách cho ngày " . $result['report_date'] . '.');
    }

    public function checkIn(Request $request, MealPlanGuest $guest)
    {
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
}
