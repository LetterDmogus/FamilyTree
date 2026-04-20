<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $totalMembers = User::count();
        $aliveCount = UserProfile::where('is_alive', true)->count();
        $deceasedCount = UserProfile::where('is_alive', false)->count();

        // Get events for current month
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $events = [];

        // 1. Birthdays & Birth Anniversaries
        UserProfile::with('user')
            ->whereNotNull('birth_date')
            ->get()
            ->each(function ($profile) use (&$events) {
                $events[] = [
                    'date' => $profile->birth_date->format('Y-m-d'),
                    'day' => $profile->birth_date->day,
                    'month' => $profile->birth_date->month,
                    'type' => 'birth',
                    'label' => 'Ulang Tahun: ' . ($profile->full_name ?? $profile->user->name),
                    'user' => $profile->full_name ?? $profile->user->name,
                ];
            });

        // 2. Death Anniversaries
        UserProfile::with('user')
            ->whereNotNull('death_date')
            ->get()
            ->each(function ($profile) use (&$events) {
                $events[] = [
                    'date' => $profile->death_date->format('Y-m-d'),
                    'day' => $profile->death_date->day,
                    'month' => $profile->death_date->month,
                    'type' => 'death',
                    'label' => 'Mengenang: ' . ($profile->full_name ?? $profile->user->name),
                    'user' => $profile->full_name ?? $profile->user->name,
                ];
            });

        // 3. Joining Dates
        User::all()->each(function ($user) use (&$events) {
            $events[] = [
                'date' => $user->created_at->format('Y-m-d'),
                'day' => $user->created_at->day,
                'month' => $user->created_at->month,
                'type' => 'join',
                'label' => 'Bergabung: ' . $user->name,
                'user' => $user->name,
            ];
        });

        // Oldest living member
        $oldestMember = UserProfile::with('user')
            ->where('is_alive', true)
            ->whereNotNull('birth_date')
            ->orderBy('birth_date', 'asc')
            ->first();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => $totalMembers,
                'alive' => $aliveCount,
                'deceased' => $deceasedCount,
            ],
            'events' => $events,
            'oldestMember' => $oldestMember,
        ]);
    }
}
