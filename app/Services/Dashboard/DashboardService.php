<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use App\Models\LeadModel;
use App\Models\SalesModel;
use App\Support\ScopeKey;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getStats(?int $selectedMonth = null, ?int $selectedYear = null, ?string $branchId)
    {
        $user  = auth()->user();
        $month = $selectedMonth ?? now()->month;
        $year  = $selectedYear ?? now()->year;

        $targetBranchId = $user->hasAnyRole(['developer', 'super_admin'])
            ? ($branchId ?? 'none')
            : ($user->id_branch ?? 'none');

        // Ambil nomor versi terbaru dari Redis
        $branchVer = Cache::get("dashboard_ver_branch_{$targetBranchId}", 1);
        $userVer   = Cache::get("dashboard_ver_user_{$user->id}", 1);

        // Admin pusat & SPV berbagi cache yang sama untuk cabang tersebut;
        // Staff di bawah SPV memiliki cache pribadi per user.
        $scopeIdentifier = $user->hasAnyRole(['developer', 'super_admin', 'supervisor'])
            ? "branch_{$targetBranchId}_v{$branchVer}"
            : "user_{$user->id}_u{$userVer}_branch_{$targetBranchId}_v{$branchVer}";

        $cacheKey = "dashboard_stats_{$scopeIdentifier}_{$year}_{$month}";
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($month, $year, $branchId) {

            $date = $this->resolveDate($month, $year);
            $currentStart = $date->copy()->startOfMonth();
            $currentEnd = $date->copy()->endOfMonth();
            $previousStart = $date->copy()->subMonth()->startOfMonth();
            $previousEnd = $date->copy()->subMonth()->endOfMonth();

            /*
        |--------------------------------------------------------------------------
        | LEAD KPI
        |--------------------------------------------------------------------------
        */

            $leadStats = $this->getLeadStats(
                $currentStart,
                $currentEnd,
                $previousStart,
                $previousEnd,
                $branchId
            );

            /*
        |--------------------------------------------------------------------------
        | SALES KPI
        |--------------------------------------------------------------------------
        */

            $salesStats = $this->getSalesStats(
                $currentStart,
                $currentEnd,
                $branchId
            );

            /*
        |--------------------------------------------------------------------------
        | STATUS SNAPSHOT
        |--------------------------------------------------------------------------
        */

            $leadDistribution = $this->getLeadDistribution(
                $currentStart,
                $currentEnd,
                $branchId
            );

            /*
        |--------------------------------------------------------------------------
        | FUNNEL TREND
        |--------------------------------------------------------------------------
        */

            $monthlyTrend = $this->getMonthlyTrend(
                $currentStart,
                $currentEnd,
                $branchId
            );

            /*
        |--------------------------------------------------------------------------
        | CALCULATIONS
        |--------------------------------------------------------------------------
        */

            $growthLead = $leadStats->leads_this_month
                - $leadStats->leads_last_month;

            $growthClosedPercent = $this->calculateGrowthPercent(
                $leadStats->closed_last_month,
                $leadStats->closed_this_month
            );

            $closingRate = $this->calculateRate(
                $leadStats->closed_this_month,
                $leadStats->leads_this_month
            );

            $lunasRate = $this->calculateRate(
                $salesStats->total_paid,
                $salesStats->total_deal
            );

            return [

                /*
            |--------------------------------------------------------------------------
            | KPI
            |--------------------------------------------------------------------------
            */
                'total_leads' => (int) $leadStats->leads_this_month,

                'growth_leads' => (int) $growthLead,

                'closed_leads' => (int) $leadStats->closed_this_month,

                'growth_closed_percent' => $growthClosedPercent,

                'closing_rate' => $closingRate,

                /*
            |--------------------------------------------------------------------------
            | SALES
            |--------------------------------------------------------------------------
            */
                'total_nominal' => (int) $salesStats->total_paid,

                'total_deal' => (int) $salesStats->total_deal,

                'remaining_bill' => (int) $salesStats->remaining_bill,

                'lunas_rate' => $lunasRate,

                /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */
                'status' => [
                    'new' => (int) $leadDistribution->new_count,

                    'follow_up' => (int) $leadDistribution->followup_count,

                    'closed_won' => (int) $leadDistribution->won_count,

                    'closed_lost' => (int) $leadDistribution->lost_count,
                ],

                /*
            |--------------------------------------------------------------------------
            | TEMPERATURE
            |--------------------------------------------------------------------------
            */
                'temperature' => [
                    'hot' => (int) $leadDistribution->hot_count,

                    'warm' => (int) $leadDistribution->warm_count,

                    'cold' => (int) $leadDistribution->cold_count,
                ],

                /*
            |--------------------------------------------------------------------------
            | PAYMENT
            |--------------------------------------------------------------------------
            */
                'payment' => [
                    'lunas' => (int) $salesStats->lunas_count,

                    'dp' => (int) $salesStats->dp_count,
                ],

                /*
            |--------------------------------------------------------------------------
            | CHART
            |--------------------------------------------------------------------------
            */
                'monthly_trend' => $monthlyTrend,
            ];
        });
    }
    private function resolveDate(?int $selectedMonth, ?int $selectedYear)
    {
        if ($selectedMonth && $selectedYear) {
            return Carbon::create(
                $selectedYear,
                $selectedMonth,
                1
            );
        }

        return now();
    }

    private function getLeadStats(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd,  $branchId)
    {
        $query = LeadModel::query();
        $this->scope($query, $branchId);

        // Batasi pemindaian hanya pada rentang 2 bulan (bulan lalu s/d bulan ini)
        $query->where(function ($q) use ($previousStart, $currentEnd) {
            $q->whereBetween('created_at', [$previousStart, $currentEnd])
                ->orWhere(function ($sub) use ($previousStart, $currentEnd) {
                    $sub->where('status', 'CLOSED_WON')
                        ->whereBetween('closed_at', [$previousStart, $currentEnd]);
                });
        });

        return $query
            ->selectRaw("
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS leads_this_month,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS leads_last_month,
                SUM(CASE WHEN status = 'CLOSED_WON' AND closed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS closed_this_month,
                SUM(CASE WHEN status = 'CLOSED_WON' AND closed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS closed_last_month
            ", [
                $currentStart,
                $currentEnd,

                $previousStart,
                $previousEnd,

                $currentStart,
                $currentEnd,

                $previousStart,
                $previousEnd,
            ])
            ->first();
    }
    private function getSalesStats(Carbon $currentStart, Carbon $currentEnd, ?string $branchId)
    {
        $query = SalesModel::query()
            ->where('status', 'ACTIVE')
            ->whereBetween('deal_at', [$currentStart, $currentEnd]); // Batasi langsung pada bulan terpilih agar tidak memindai seluruh riwayat transaksi

        $this->scope($query, $branchId);

        return $query
            ->selectRaw("
                COUNT(*) AS total_sales,
                COALESCE(SUM(deal_price), 0) AS total_deal,
                COALESCE(SUM(paid_amount), 0) AS total_paid,
                COALESCE(SUM(remaining_bill), 0) AS remaining_bill,
                SUM(CASE WHEN payment_status = 'LUNAS' THEN 1 ELSE 0 END) AS lunas_count,
                SUM(CASE WHEN payment_status = 'DP' THEN 1 ELSE 0 END) AS dp_count
            ")
            ->first();
    }

    private function getLeadDistribution(Carbon $currentStart, Carbon $currentEnd, $branchId)
    {
        $query = LeadModel::query();

        $this->scope($query, $branchId);

        return $query
            ->whereBetween(
                'created_at',
                [$currentStart, $currentEnd]
            )
            ->selectRaw("
                COALESCE(SUM(status = 'NEW'), 0) AS new_count,
                COALESCE(SUM(status = 'FOLLOW_UP'), 0) AS followup_count,
                COALESCE(SUM(status = 'CLOSED_WON'), 0) AS won_count,
                COALESCE(SUM(status = 'CLOSED_LOST'), 0) AS lost_count,
                COALESCE(SUM(temperature = 'HOT'), 0) AS hot_count,
                COALESCE(SUM(temperature = 'WARM'), 0) AS warm_count,
                COALESCE(SUM(temperature = 'COLD'), 0) AS cold_count
            ")
            ->first();
    }

    private function getMonthlyTrend(Carbon $currentStart, Carbon $currentEnd, $branchId): array
    {
        $days = $currentStart->daysInMonth;

        $trend = [];

        for ($day = 1; $day <= $days; $day++) {
            $date = $currentStart
                ->copy()
                ->day($day);

            $dateKey = $date->format('Y-m-d');

            $trend[$dateKey] = [
                'label' => $date->format('d M'),
                'total' => 0,
                'closed' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Prospek Masuk
        |--------------------------------------------------------------------------
        */

        $leadQuery = LeadModel::query();

        $this->scope($leadQuery, $branchId);

        $leadTrend = $leadQuery
            ->whereBetween(
                'created_at',
                [$currentStart, $currentEnd]
            )
            ->selectRaw("
                DATE(created_at) AS date,
                COUNT(*) AS total
            ")
            ->groupByRaw('DATE(created_at)')
            ->get();

        foreach ($leadTrend as $row) {
            if (isset($trend[$row->date])) {
                $trend[$row->date]['total'] =
                    (int) $row->total;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Closing
        |--------------------------------------------------------------------------
        */

        $closedQuery = LeadModel::query();

        $this->scope($closedQuery, $branchId);

        $closedTrend = $closedQuery
            ->where('status', 'CLOSED_WON')
            ->whereNotNull('closed_at')
            ->whereBetween(
                'closed_at',
                [$currentStart, $currentEnd]
            )
            ->selectRaw("
                DATE(closed_at) AS date,
                COUNT(*) AS closed
            ")
            ->groupByRaw('DATE(closed_at)')
            ->get();

        foreach ($closedTrend as $row) {
            if (isset($trend[$row->date])) {
                $trend[$row->date]['closed'] =
                    (int) $row->closed;
            }
        }

        return array_values($trend);
    }
    private function calculateRate($numerator, $denominator): float
    {
        if ((int) $denominator <= 0) {
            return 0;
        }

        return round(
            ((int) $numerator / (int) $denominator) * 100,
            1
        );
    }
    private function calculateGrowthPercent($previous, $current): float
    {
        $previous = (int) $previous;
        $current = (int) $current;

        if ($previous > 0) {
            return round(
                (($current - $previous) / $previous) * 100,
                1
            );
        }

        return $current > 0 ? 100 : 0;
    }

    private function scope($query, ?string $branchId)
    {
        $user = auth()->user();
        return match (true) {
            $user->hasAnyRole(['developer', 'super_admin']) => $query->where('id_branch', $branchId ?? '__not_found__'),
            $user->hasRole('supervisor') => $query->where('id_branch', $user->id_branch ?? '__not_found__'),
            default => $query->where('id_branch', $user->id_branch ?? '__not_found__')
                ->where('created_by', $user->id),
        };
    }

    /**
     * Naikkan versi cache dashboard agar data langsung segar (real-time)
     * khusus untuk cabang dan user yang datanya berubah.
     */
    public static function invalidate(?string $branchId, ?string $userId = null)
    {
        if (!empty($branchId)) {
            $branchKey = "dashboard_ver_branch_{$branchId}";
            Cache::put($branchKey, (int) Cache::get($branchKey, 1) + 1, now()->addDays(30));
        }

        if (!empty($userId)) {
            $userKey = "dashboard_ver_user_{$userId}";
            Cache::put($userKey, (int) Cache::get($userKey, 1) + 1, now()->addDays(30));
        }
    }
}
