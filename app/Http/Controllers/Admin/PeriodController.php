<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminPeriodFilter;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    public function update(Request $request, AdminPeriodFilter $filter)
    {
        $request->validate([
            'period' => 'required|in:30d,60d,90d,ano,tudo,personalizado',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'end_date.after_or_equal' => 'A data final deve ser maior ou igual à inicial.',
        ]);

        if ($request->period === 'personalizado' && (! $request->filled('start_date') || ! $request->filled('end_date'))) {
            return redirect()->back()->with('error', 'Para o período personalizado, informe data inicial e final.');
        }

        $filter->setPeriod(
            $request->period,
            $request->start_date,
            $request->end_date
        );

        return redirect()->back();
    }
}
