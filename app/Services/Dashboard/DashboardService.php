<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use App\Models\User;
use App\Models\LeadModel;
use App\Models\SalesModel;
use App\Services\Authorization\DataScopeService;

class DashboardService
{
    protected $scope;
    public function __construct(DataScopeService $dataScopeService)
    {
        $this->scope = $dataScopeService;
    }
    public function getStats(?int $selectedMonth = null, ?int $selectedYear = null, ?string $branchId)
    {
        $date = $this->resolveDate($selectedMonth, $selectedYear);
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
        return $query
            ->selectRaw("
                SUM(
                    CASE
                        WHEN created_at BETWEEN ?
                        AND ?
                        THEN 1
                        ELSE 0
                    END
                ) AS leads_this_month,

                SUM(
                    CASE
                        WHEN created_at BETWEEN ?
                        AND ?
                        THEN 1
                        ELSE 0
                    END
                ) AS leads_last_month,

                SUM(
                    CASE
                        WHEN closed_at BETWEEN ?
                        AND ?
                        THEN 1
                        ELSE 0
                    END
                ) AS closed_this_month,

                SUM(
                    CASE
                        WHEN closed_at BETWEEN ?
                        AND ?
                        THEN 1
                        ELSE 0
                    END
                ) AS closed_last_month
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
    private function getSalesStats(Carbon $currentStart, Carbon $currentEnd, $branchId)
    {
        $query = SalesModel::query()
            ->where('status', 'ACTIVE');

        $this->scope($query, $branchId);

        return $query
            ->selectRaw("
                COUNT(
                    CASE
                        WHEN deal_at BETWEEN ?
                        AND ?
                        THEN 1
                    END
                ) AS total_sales,

                COALESCE(
                    SUM(
                        CASE
                            WHEN deal_at BETWEEN ?
                            AND ?
                            THEN deal_price
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_deal,

                COALESCE(
                    SUM(
                        CASE
                            WHEN deal_at BETWEEN ?
                            AND ?
                            THEN paid_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_paid,

                COALESCE(
                    SUM(
                        CASE
                            WHEN deal_at BETWEEN ?
                            AND ?
                            THEN remaining_bill
                            ELSE 0
                        END
                    ),
                    0
                ) AS remaining_bill,

                SUM(
                    CASE
                        WHEN payment_status = 'LUNAS'
                        AND deal_at BETWEEN ?
                        AND ?
                        THEN 1
                        ELSE 0
                    END
                ) AS lunas_count,

                SUM(
                    CASE
                        WHEN payment_status = 'DP'
                        AND deal_at BETWEEN ?
                        AND ?
                        THEN 1
                        ELSE 0
                    END
                ) AS dp_count
            ", [
                $currentStart,
                $currentEnd,

                $currentStart,
                $currentEnd,

                $currentStart,
                $currentEnd,

                $currentStart,
                $currentEnd,

                $currentStart,
                $currentEnd,

                $currentStart,
                $currentEnd,
            ])
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
                SUM(status = 'NEW') AS new_count,

                SUM(status = 'FOLLOW_UP') AS followup_count,

                SUM(status = 'CLOSED_WON') AS won_count,

                SUM(status = 'CLOSED_LOST') AS lost_count,

                SUM(temperature = 'HOT') AS hot_count,

                SUM(temperature = 'WARM') AS warm_count,

                SUM(temperature = 'COLD') AS cold_count
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

        if ($current > 0) {
            return 100;
        }

        return 0;
    }
    private function scope($query, $branchId)
    {
        $user = auth()->user();
        if ($user->hasRole('super_admin')) {
            // Super Admin: Lihat semua. Tapi kalau dia milih filter cabang, terapkan!
            if (!empty($branchId)) {
                $query->where('id_branch', $branchId);
            }
        } elseif ($user->hasRole('supervisor')) {
            // SPV: Kunci mati di cabangnya, bisa lihat kerjaan semua sales di cabang tersebut
            $query->where('id_branch', $user->id_branch);
        } else {
            // Sales/Admin dll: Kunci mati di cabang AND hanya miliknya sendiri
            $query->where('id_branch', $user->id_branch)
                ->where('created_by', $user->id);
        }
    }
}
