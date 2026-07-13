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
    public function getStats($selectedMonth = null, $selectedYear = null, $branchId = null): array
    {
        $dates  = $selectedMonth && $selectedYear ?
            Carbon::create($selectedYear, $selectedMonth, 1) : now();

        $currentMonth = (int) $dates->month;
        $currentYear = (int) $dates->year;

        $lastMonthDate = $dates->copy()->subMonth();
        $lastMonth = (int) $lastMonthDate->month;
        $lastYear = (int) $lastMonthDate->year;

        /*
        |--------------------------------------------------------------------------
        | LEADS
        |--------------------------------------------------------------------------
        */
        $leadQuery = LeadModel::query();
        $this->scope($leadQuery, $branchId);

        $lead = $leadQuery->selectRaw("
                COUNT(*) as total_leads,

                SUM(
                    CASE
                        WHEN MONTH(created_at)={$currentMonth}
                        AND YEAR(created_at)={$currentYear}
                        THEN 1 ELSE 0
                    END
                ) as leads_this_month,

                SUM(
                    CASE
                        WHEN MONTH(created_at)={$lastMonth}
                        AND YEAR(created_at)={$lastYear}
                        THEN 1 ELSE 0
                    END
                ) as leads_last_month,

                SUM(
                    CASE
                        WHEN status='CLOSED_WON'
                        AND MONTH(updated_at)={$currentMonth}
                        AND YEAR(updated_at)={$currentYear}
                        THEN 1 ELSE 0
                    END
                ) as closed_this_month,

                SUM(
                    CASE
                        WHEN status='CLOSED_WON'
                        AND MONTH(updated_at)={$lastMonth}
                        AND YEAR(updated_at)={$lastYear}
                        THEN 1 ELSE 0
                    END
                ) as closed_last_month,


                SUM(status='NEW' AND MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}) as new_count,
                SUM(status='FOLLOW_UP' AND MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}) as followup_count,
                SUM(status='CLOSED_WON' AND MONTH(updated_at)={$currentMonth} AND YEAR(updated_at)={$currentYear}) as won_count,
                SUM(status='CLOSED_LOST' AND MONTH(updated_at)={$currentMonth} AND YEAR(updated_at)={$currentYear}) as lost_count,

                SUM(temperature='HOT' AND MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}) as hot_count,
                SUM(temperature='WARM' AND MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}) as warm_count,
                SUM(temperature='COLD' AND MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}) as cold_count
            ")
            ->first();

        /*
        |--------------------------------------------------------------------------
        | SALES
        |--------------------------------------------------------------------------
        */

        $salesQuery = SalesModel::query();

        $this->scope($salesQuery, $branchId);

        $sales = $salesQuery
            ->selectRaw("
                COUNT(*) as total_sales,
                SUM(
                    CASE
                        WHEN MONTH(created_at)={$currentMonth}
                        AND YEAR(created_at)={$currentYear}
                        THEN deal_price ELSE 0
                    END
                ) as total_deal,

                SUM(
                    CASE
                        WHEN MONTH(created_at)={$currentMonth}
                        AND YEAR(created_at)={$currentYear}
                        THEN paid_amount ELSE 0
                    END
                ) as total_paid,

               SUM(
                    CASE
                        WHEN MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}
                        THEN remaining_bill ELSE 0
                    END
                ) as remaining_bill,
                SUM(payment_status='LUNAS' AND MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}) as lunas_count,
                SUM(payment_status='DP' AND MONTH(created_at)={$currentMonth} AND YEAR(created_at)={$currentYear}) as dp_count
            ")
            ->first();

        /*
        |--------------------------------------------------------------------------
        | FUNNEL TREND (SEPANJANG BULAN BERJALAN)
        |--------------------------------------------------------------------------
        */
        $monthlyTrend = [];
        $daysInMonth = $dates->daysInMonth;
        // Buat kerangka 7 hari terakhir (H-6 sampai H-0)
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dateKey = $dates->copy()->day($i)->format('Y-m-d');
            $dateLabel = $dates->copy()->day($i)->format('d M'); // Format UI: 12 Jul

            $monthlyTrend[$dateKey] = [
                'label'  => $dateLabel,
                'total'  => 0,
                'closed' => 0
            ];
        }

        // Query ke database khusus 7 hari terakhir
        $trendQuery = LeadModel::query();
        $this->scope($trendQuery, $branchId);

        $trendData = $trendQuery
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->selectRaw("
                DATE(created_at) as date,
                COUNT(*) as total_leads,
                SUM(status='CLOSED_WON') as closed_leads
            ")
            ->groupBy('date')
            ->get();

        // Gabungkan data dari database ke kerangka 7 hari
        foreach ($trendData as $row) {
            if (isset($monthlyTrend[$row->date])) {
                $monthlyTrend[$row->date]['total'] = (int) $row->total_leads;
                $monthlyTrend[$row->date]['closed'] = (int) $row->closed_leads;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | HITUNG DI PHP
        |--------------------------------------------------------------------------
        */

        $growthLead =
            $lead->leads_this_month -
            $lead->leads_last_month;

        $growthClosed = 0;

        if ($lead->closed_last_month > 0) {
            $growthClosed = round(
                (
                    ($lead->closed_this_month - $lead->closed_last_month)
                    / $lead->closed_last_month
                ) * 100,
                1
            );
        } elseif ($lead->closed_this_month > 0) {
            $growthClosed = 100;
        }

        $closingRate = $lead->leads_this_month > 0 ? round(($lead->closed_this_month / $lead->leads_this_month) * 100, 1) : 0;

        $lunasRate = $sales->total_deal > 0 ? round(($sales->total_paid / $sales->total_deal) * 100, 1) : 0;


        return [
            'total_leads' => (int) $lead->leads_this_month,
            'growth_leads' => $growthLead,
            'closed_leads' => (int) $lead->closed_this_month,
            'growth_closed_percent' => $growthClosed,
            'closing_rate' => $closingRate,
            'total_nominal' =>  (int) $sales->total_paid,
            'total_deal'      => (int) $sales->total_deal,
            'remaining_bill' => (int) $sales->remaining_bill,
            'lunas_rate' =>  $lunasRate,


            'status' => [
                'new' => (int) $lead->new_count,
                'follow_up' => (int) $lead->followup_count,
                'closed_won' => (int) $lead->won_count,
                'closed_lost' => (int) $lead->lost_count,
            ],

            'temperature' => [
                'hot' => (int) $lead->hot_count,
                'warm' => (int) $lead->warm_count,
                'cold' => (int) $lead->cold_count,
            ],

            'payment' => [
                'lunas' => (int) $sales->lunas_count,
                'dp' => (int) $sales->dp_count,
            ],

            'monthly_trend' => array_values($monthlyTrend),

        ];
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
                ->where('id_user', $user->id);
        }
    }
}
