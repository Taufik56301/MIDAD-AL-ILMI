<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->toDateString();

        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $query = Attendance::query()->with('user')->latest('date')->latest('clock_in');

        if (! $user->isOwner()) {
            $query->where('user_id', $user->id);
        } else {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->string('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->string('end_date'));
            }
        }

        $attendances = $query->paginate(15)->withQueryString();
        $employees = $user->isOwner() ? User::query()->orderBy('name')->get() : collect();

        return view('attendances.index', compact('todayAttendance', 'attendances', 'employees'));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $existing = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            return back()->with('error', 'Anda sudah melakukan Absen Masuk hari ini.');
        }

        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => now(),
            'total_hours' => 0,
        ]);

        return back()->with('success', 'Absen Masuk berhasil dicatat pada '.now()->format('H:i:s').' WIB.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance || ! $attendance->clock_in) {
            return back()->with('error', 'Anda belum melakukan Absen Masuk hari ini.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'Anda sudah melakukan Absen Pulang hari ini.');
        }

        $clockOut = now();
        $totalHours = round(abs($clockOut->diffInSeconds($attendance->clock_in)) / 3600, 2);

        $attendance->update([
            'clock_out' => $clockOut,
            'total_hours' => $totalHours,
        ]);

        return back()->with('success', "Absen Pulang berhasil dicatat. Total jam kerja hari ini: {$totalHours} jam.");
    }
}

