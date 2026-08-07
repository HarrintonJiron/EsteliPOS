<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Loan;
use App\Models\Payroll;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    // Tasas según leyes laborales de Nicaragua
    private const INSS_EMPLOYEE_RATE = 0.0625; // 6.25%

    private const INSS_EMPLOYER_RATE = 0.0225; // 2.25% (INATEC)

    private const INSS_TOTAL_RATE = 0.085; // 8.5%

    private const IR_BRACKETS = [
        ['min' => 0, 'max' => 100000, 'rate' => 0, 'excess' => 0],
        ['min' => 100000.01, 'max' => 200000, 'rate' => 0.15, 'excess' => 0],
        ['min' => 200000.01, 'max' => 350000, 'rate' => 0.20, 'excess' => 15000],
        ['min' => 350000.01, 'max' => 500000, 'rate' => 0.25, 'excess' => 45000],
        ['min' => 500000.01, 'max' => null, 'rate' => 0.30, 'excess' => 82500],
    ];

    private const MINIMUM_WAGE = 6726.72; // Salario mínimo mensual 2024 (C$)

    /**
     * Calcular nómina para un empleado en un período específico
     */
    public function calculatePayroll(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $grossSalary = $this->calculateGrossSalary($employee, $startDate, $endDate);

        // Calcular bonos del período
        $bonuses = $this->calculateBonuses($employee, $startDate, $endDate);

        // Calcular deducciones del período
        $deductions = $this->calculateDeductions($employee, $startDate, $endDate);

        // Calcular pagos de préstamos del período
        $loanPayments = $this->calculateLoanPayments($employee, $startDate, $endDate);

        // Salario bruto ajustado con bonos
        $adjustedGrossSalary = $grossSalary + $bonuses;

        $inssDeduction = $this->calculateINSS($adjustedGrossSalary);
        $taxableIncome = $adjustedGrossSalary - $inssDeduction;
        $irDeduction = $this->calculateIR($taxableIncome);

        // Total de deducciones (INSS + IR + deducciones específicas + préstamos)
        $totalDeductions = $inssDeduction + $irDeduction + $deductions + $loanPayments;

        $netSalary = $adjustedGrossSalary - $totalDeductions;

        // Calcular días trabajados considerando permisos
        $workedDays = $this->calculateWorkedDays($employee, $startDate, $endDate);
        $leaveDays = $this->calculateLeaveDays($employee, $startDate, $endDate);

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'position' => $employee->position,
            'base_salary' => $grossSalary,
            'bonuses' => $bonuses,
            'gross_salary' => $adjustedGrossSalary,
            'inss_deduction' => $inssDeduction,
            'ir_deduction' => $irDeduction,
            'other_deductions' => $deductions,
            'loan_payments' => $loanPayments,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'worked_days' => $workedDays,
            'leave_days' => $leaveDays,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * Calcular salario bruto según frecuencia de pago
     */
    private function calculateGrossSalary(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        $monthlySalary = $employee->salary;

        return match ($employee->payment_frequency) {
            'weekly' => $monthlySalary / 4,
            'biweekly' => $monthlySalary / 2,
            'monthly' => $monthlySalary,
            default => $monthlySalary,
        };
    }

    /**
     * Calcular deducción de INSS (6.25% empleado)
     */
    public function calculateINSS(float $grossSalary): float
    {
        // INSS se calcula sobre el salario bruto
        // El tope máximo para INSS es de 25,000 C$ mensuales
        $maxINSSBase = 25000;
        $base = min($grossSalary, $maxINSSBase);

        return round($base * self::INSS_EMPLOYEE_RATE, 2);
    }

    /**
     * Calcular IR (Impuesto sobre la Renta) según tabla Nicaragua
     */
    public function calculateIR(float $taxableIncome): float
    {
        // IR se calcula sobre el ingreso gravable (salario - INSS)
        // Exención anual de 100,000 C$ (aprox 8,333.33 C$ mensuales)
        $annualExemption = 100000;
        $monthlyExemption = $annualExemption / 12;

        $taxableAfterExemption = max(0, $taxableIncome - $monthlyExemption);

        // Si no hay ingreso gravable después de exención, no hay IR
        if ($taxableAfterExemption <= 0) {
            return 0;
        }

        // Calcular IR según tramos
        foreach (self::IR_BRACKETS as $bracket) {
            if ($bracket['max'] === null || $taxableAfterExemption <= $bracket['max']) {
                $excess = $taxableAfterExemption - $bracket['min'];

                return round(($excess * $bracket['rate']) + $bracket['excess'], 2);
            }
        }

        return 0;
    }

    /**
     * Calcular días trabajados en el período
     */
    private function calculateWorkedDays(Employee $employee, Carbon $startDate, Carbon $endDate): int
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $workedDays = 0;

        foreach ($period as $date) {
            // Excluir fines de semana (sábado y domingo)
            if ($date->isWeekend()) {
                continue;
            }

            // Excluir días de permiso aprobado
            $hasLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->exists();

            if (! $hasLeave) {
                $workedDays++;
            }
        }

        return $workedDays;
    }

    /**
     * Calcular días de permiso en el período
     */
    private function calculateLeaveDays(Employee $employee, Carbon $startDate, Carbon $endDate): int
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->sum('days');
    }

    /**
     * Calcular vacaciones acumuladas
     * En Nicaragua: 30 días de vacaciones por año trabajado
     */
    public function calculateVacationBalance(Employee $employee): array
    {
        if (! $employee->hire_date) {
            return [
                'total_days' => 0,
                'used_days' => 0,
                'available_days' => 0,
            ];
        }

        $yearsWorked = $employee->years_of_service;
        $totalVacationDays = $yearsWorked * 30;

        // Calcular días de vacaciones usados
        $usedVacationDays = LeaveRequest::where('employee_id', $employee->id)
            ->where('type', 'vacation')
            ->where('status', 'approved')
            ->sum('days');

        return [
            'total_days' => $totalVacationDays,
            'used_days' => $usedVacationDays,
            'available_days' => max(0, $totalVacationDays - $usedVacationDays),
        ];
    }

    /**
     * Calcular prestaciones laborales (13° mes, vacaciones, etc.)
     */
    public function calculateBenefits(Employee $employee): array
    {
        if (! $employee->hire_date) {
            return [
                'thirteenth_month' => 0,
                'vacation_pay' => 0,
                'severance' => 0,
            ];
        }

        $monthlySalary = $employee->salary;
        $yearsWorked = $employee->years_of_service;

        // 13° mes: 1 mes de salario por año trabajado
        $thirteenthMonth = $monthlySalary * $yearsWorked;

        // Pago de vacaciones: 1 mes de salario por año trabajado
        $vacationPay = $monthlySalary * $yearsWorked;

        // Indemnización por despido injustificado (varía según años)
        $severance = $this->calculateSeverance($monthlySalary, $yearsWorked);

        return [
            'thirteenth_month' => round($thirteenthMonth, 2),
            'vacation_pay' => round($vacationPay, 2),
            'severance' => round($severance, 2),
        ];
    }

    /**
     * Calcular indemnización por despido
     */
    private function calculateSeverance(float $monthlySalary, int $yearsWorked): float
    {
        if ($yearsWorked == 0) {
            return 0;
        }

        // Primer año: 1 mes de salario
        // Años subsiguientes: 1.5 meses de salario por año
        if ($yearsWorked == 1) {
            return $monthlySalary;
        }

        return $monthlySalary + (($yearsWorked - 1) * ($monthlySalary * 1.5));
    }

    /**
     * Calcular horas extras
     */
    public function calculateOvertime(float $hourlyRate, int $regularHours, int $extraHours): array
    {
        // Horas extras diurnas: 125% del salario normal
        // Horas extras nocturnas: 150% del salario normal
        // Horas extras en día de descanso: 200% del salario normal

        $regularOvertimePay = $regularHours * ($hourlyRate * 1.25);
        $extraOvertimePay = $extraHours * ($hourlyRate * 1.5);

        return [
            'regular_hours' => $regularHours,
            'regular_pay' => round($regularOvertimePay, 2),
            'extra_hours' => $extraHours,
            'extra_pay' => round($extraOvertimePay, 2),
            'total_overtime_pay' => round($regularOvertimePay + $extraOvertimePay, 2),
        ];
    }

    /**
     * Generar reporte de nómina para todos los empleados
     */
    public function generatePayrollReport(Carbon $startDate, Carbon $endDate): array
    {
        $employees = Employee::where('is_active', true)->get();
        $payrollData = [];

        $totalGross = 0;
        $totalINSS = 0;
        $totalIR = 0;
        $totalBonuses = 0;
        $totalDeductions = 0;
        $totalLoanPayments = 0;
        $totalNet = 0;

        foreach ($employees as $employee) {
            $payroll = $this->calculatePayroll($employee, $startDate, $endDate);
            $payrollData[] = $payroll;

            $totalGross += $payroll['gross_salary'];
            $totalINSS += $payroll['inss_deduction'];
            $totalIR += $payroll['ir_deduction'];
            $totalBonuses += $payroll['bonuses'];
            $totalDeductions += $payroll['other_deductions'];
            $totalLoanPayments += $payroll['loan_payments'];
            $totalNet += $payroll['net_salary'];
        }

        return [
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'employees' => $payrollData,
            'totals' => [
                'base_salary' => round($totalGross - $totalBonuses, 2),
                'bonuses' => round($totalBonuses, 2),
                'gross_salary' => round($totalGross, 2),
                'inss_deduction' => round($totalINSS, 2),
                'ir_deduction' => round($totalIR, 2),
                'other_deductions' => round($totalDeductions, 2),
                'loan_payments' => round($totalLoanPayments, 2),
                'total_deductions' => round($totalINSS + $totalIR + $totalDeductions + $totalLoanPayments, 2),
                'net_salary' => round($totalNet, 2),
            ],
        ];
    }

    /**
     * Calcular bonos del período para un empleado
     */
    private function calculateBonuses(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        return Bonus::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Calcular deducciones del período para un empleado
     */
    private function calculateDeductions(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        return Deduction::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Calcular pagos de préstamos del período para un empleado
     */
    private function calculateLoanPayments(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        return Loan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->sum('monthly_payment');
    }

    /**
     * Datos agregados para el dashboard de planilla
     */
    public function getDashboardData(?Carbon $referenceDate = null): array
    {
        $referenceDate ??= Carbon::now();
        $startDate = $referenceDate->copy()->startOfMonth();
        $endDate = $referenceDate->copy()->endOfMonth();

        $payrollReport = $this->getPayrollReportForPeriod($startDate, $endDate);
        $trend = $this->getPayrollTrend(6, $referenceDate);

        $activeEmployees = Employee::where('is_active', true)->count();
        $inactiveEmployees = Employee::where('is_active', false)->count();

        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
        $pendingLoans = Loan::where('status', 'pending')->count();
        $pendingBonuses = Bonus::where('status', 'pending')->count();
        $pendingDeductions = Deduction::where('status', 'pending')->count();

        $activeLoansBalance = Loan::where('status', 'active')->sum('remaining_balance');
        $activeLoansCount = Loan::where('status', 'active')->count();

        $contractDistribution = Employee::where('is_active', true)
            ->selectRaw('contract_type, COUNT(*) as total')
            ->groupBy('contract_type')
            ->pluck('total', 'contract_type')
            ->mapWithKeys(fn ($total, $type) => [
                match ($type) {
                    'full_time' => 'Tiempo Completo',
                    'part_time' => 'Medio Tiempo',
                    'temporary' => 'Temporal',
                    'seasonal' => 'Por Temporada',
                    default => $type ?: 'Sin definir',
                } => (int) $total,
            ])
            ->all();

        $salaryByEmployee = collect($payrollReport['employees'])
            ->sortByDesc('net_salary')
            ->take(8)
            ->map(fn (array $row) => [
                'name' => $row['employee_name'],
                'net' => round($row['net_salary'], 2),
                'gross' => round($row['gross_salary'], 2),
            ])
            ->values()
            ->all();

        $deductionBreakdown = [
            ['label' => 'INSS', 'value' => $payrollReport['totals']['inss_deduction']],
            ['label' => 'IR', 'value' => $payrollReport['totals']['ir_deduction']],
            ['label' => 'Otras', 'value' => $payrollReport['totals']['other_deductions']],
            ['label' => 'Préstamos', 'value' => $payrollReport['totals']['loan_payments']],
        ];

        return [
            'period_label' => $startDate->translatedFormat('F Y'),
            'month' => $startDate->format('Y-m'),
            'payroll' => $payrollReport,
            'trend' => $trend,
            'stats' => [
                'active_employees' => $activeEmployees,
                'inactive_employees' => $inactiveEmployees,
                'total_employees' => $activeEmployees + $inactiveEmployees,
                'monthly_base_payroll' => round(Employee::where('is_active', true)->sum('salary'), 2),
                'pending_leaves' => $pendingLeaves,
                'pending_loans' => $pendingLoans,
                'pending_bonuses' => $pendingBonuses,
                'pending_deductions' => $pendingDeductions,
                'pending_total' => $pendingLeaves + $pendingLoans + $pendingBonuses + $pendingDeductions,
                'active_loans_count' => $activeLoansCount,
                'active_loans_balance' => round((float) $activeLoansBalance, 2),
            ],
            'charts' => [
                'salary_by_employee' => $salaryByEmployee,
                'deduction_breakdown' => $deductionBreakdown,
                'contract_distribution' => collect($contractDistribution)
                    ->map(fn ($total, $label) => ['label' => $label, 'value' => $total])
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * Tendencia de nómina de los últimos N meses
     */
    public function getPayrollTrend(int $months = 6, ?Carbon $referenceDate = null): array
    {
        $referenceDate ??= Carbon::now();

        return collect(range($months - 1, 0))->map(function (int $monthsAgo) use ($referenceDate) {
            $month = $referenceDate->copy()->subMonths($monthsAgo);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $report = $this->getPayrollTicketData($start, $end);

            return [
                'label' => $start->translatedFormat('M Y'),
                'month' => $start->format('Y-m'),
                'gross' => $report['totals']['gross_salary'],
                'net' => $report['totals']['net_salary'],
                'deductions' => $report['totals']['total_deductions'],
                'bonuses' => $report['totals']['bonuses'],
            ];
        })->values()->all();
    }

    /**
     * @return array{start: Carbon, end: Carbon, selected_month: string}
     */
    public function resolvePeriodDates(?string $month = null): array
    {
        $startDate = Carbon::now()->startOfMonth();

        if ($month) {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return [
            'start' => $startDate,
            'end' => $startDate->copy()->endOfMonth(),
            'selected_month' => $startDate->format('Y-m'),
        ];
    }

    public function isPeriodPaid(Carbon $startDate): bool
    {
        return Payroll::query()
            ->where('month', $startDate->format('m'))
            ->where('year', $startDate->year)
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * @return array{
     *     paid_at: ?Carbon,
     *     paid_by_name: ?string,
     *     payrolls: Collection<int, Payroll>
     * }
     */
    public function getPeriodPaymentSummary(Carbon $startDate): array
    {
        $payrolls = Payroll::query()
            ->with(['employee', 'paidBy'])
            ->where('month', $startDate->format('m'))
            ->where('year', $startDate->year)
            ->where('status', 'paid')
            ->orderBy('id')
            ->get();

        $first = $payrolls->first();

        return [
            'paid_at' => $first?->paid_at,
            'paid_by_name' => $first?->paidBy?->name,
            'payrolls' => $payrolls,
        ];
    }

    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     employees: array<int, array<string, mixed>>,
     *     totals: array<string, float>,
     *     is_paid: bool,
     *     paid_at: ?Carbon,
     *     paid_by_name: ?string
     * }
     */
    public function getPayrollTicketData(Carbon $startDate, Carbon $endDate): array
    {
        $paymentSummary = $this->getPeriodPaymentSummary($startDate);

        if ($paymentSummary['payrolls']->isNotEmpty()) {
            $employees = $paymentSummary['payrolls']->map(function (Payroll $payroll): array {
                return [
                    'employee_id' => $payroll->employee_id,
                    'employee_name' => $payroll->employee?->name ?? 'Empleado no disponible',
                    'position' => $payroll->employee?->position,
                    'base_salary' => (float) $payroll->base_salary,
                    'bonuses' => (float) $payroll->bonuses,
                    'gross_salary' => (float) $payroll->gross_salary,
                    'inss_deduction' => (float) $payroll->inss_deduction,
                    'ir_deduction' => (float) $payroll->ir_deduction,
                    'other_deductions' => (float) $payroll->deductions,
                    'loan_payments' => (float) $payroll->loan_payments,
                    'total_deductions' => round(
                        (float) $payroll->inss_deduction
                        + (float) $payroll->ir_deduction
                        + (float) $payroll->deductions
                        + (float) $payroll->loan_payments,
                        2
                    ),
                    'net_salary' => (float) $payroll->net_salary,
                ];
            })->values()->all();

            $totals = [
                'base_salary' => round(collect($employees)->sum('base_salary'), 2),
                'bonuses' => round(collect($employees)->sum('bonuses'), 2),
                'gross_salary' => round(collect($employees)->sum('gross_salary'), 2),
                'inss_deduction' => round(collect($employees)->sum('inss_deduction'), 2),
                'ir_deduction' => round(collect($employees)->sum('ir_deduction'), 2),
                'other_deductions' => round(collect($employees)->sum('other_deductions'), 2),
                'loan_payments' => round(collect($employees)->sum('loan_payments'), 2),
                'total_deductions' => round(collect($employees)->sum('total_deductions'), 2),
                'net_salary' => round(collect($employees)->sum('net_salary'), 2),
            ];

            return [
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'employees' => $employees,
                'totals' => $totals,
                'is_paid' => true,
                'paid_at' => $paymentSummary['paid_at'],
                'paid_by_name' => $paymentSummary['paid_by_name'],
            ];
        }

        $report = $this->generatePayrollReport($startDate, $endDate);

        return [
            ...$report,
            'is_paid' => false,
            'paid_at' => null,
            'paid_by_name' => null,
        ];
    }

    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     employees: array<int, array<string, mixed>>,
     *     totals: array<string, float>,
     *     is_paid: bool,
     *     paid_at: ?Carbon,
     *     paid_by_name: ?string
     * }
     */
    public function getPayrollReportForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $data = $this->getPayrollTicketData($startDate, $endDate);

        if (! $data['is_paid']) {
            return $data;
        }

        $liveEmployees = collect($this->generatePayrollReport($startDate, $endDate)['employees'])
            ->keyBy('employee_id');

        $data['employees'] = collect($data['employees'])
            ->map(function (array $row) use ($liveEmployees): array {
                $live = $liveEmployees->get($row['employee_id'], []);

                return array_merge($row, [
                    'worked_days' => $live['worked_days'] ?? 0,
                    'leave_days' => $live['leave_days'] ?? 0,
                ]);
            })
            ->values()
            ->all();

        return $data;
    }

    /**
     * @return array{
     *     salary_by_employee: array<int, array{name: string, net: float, gross: float, deductions: float}>,
     *     deduction_breakdown: array<int, array{label: string, value: float}>
     * }
     */
    public function buildNominaChartSeries(array $report): array
    {
        return [
            'salary_by_employee' => collect($report['employees'])
                ->sortByDesc('net_salary')
                ->values()
                ->map(fn (array $row) => [
                    'name' => $row['employee_name'],
                    'net' => round($row['net_salary'], 2),
                    'gross' => round($row['gross_salary'], 2),
                    'deductions' => round($row['total_deductions'], 2),
                ])
                ->all(),
            'deduction_breakdown' => [
                ['label' => 'INSS', 'value' => $report['totals']['inss_deduction']],
                ['label' => 'IR', 'value' => $report['totals']['ir_deduction']],
                ['label' => 'Otras', 'value' => $report['totals']['other_deductions']],
                ['label' => 'Préstamos', 'value' => $report['totals']['loan_payments']],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getNominaAnalyticsPayload(?string $month = null): array
    {
        $period = $this->resolvePeriodDates($month);
        $startDate = $period['start'];
        $endDate = $period['end'];
        $report = $this->getPayrollReportForPeriod($startDate, $endDate);

        return [
            'selectedMonth' => $period['selected_month'],
            'periodLabel' => $startDate->translatedFormat('F Y'),
            'periodStart' => $startDate->format('d/m/Y'),
            'periodEnd' => $endDate->format('d/m/Y'),
            'employeeCount' => count($report['employees']),
            'isPaid' => $report['is_paid'],
            'paidAt' => $report['paid_at']?->format('d/m/Y H:i'),
            'paidByName' => $report['paid_by_name'],
            'payrollReport' => $report,
            'trend' => $this->getPayrollTrend(6, $startDate),
            'charts' => $this->buildNominaChartSeries($report),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPlanillaDashboardAnalyticsPayload(?string $month = null): array
    {
        $referenceDate = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $dashboard = $this->getDashboardData($referenceDate);

        return [
            'selectedMonth' => $dashboard['month'],
            'periodLabel' => $dashboard['period_label'],
            'stats' => $dashboard['stats'],
            'totals' => $dashboard['payroll']['totals'],
            'trend' => $dashboard['trend'],
            'charts' => $dashboard['charts'],
        ];
    }

    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     employees_count: int,
     *     net_salary: float
     * }
     */
    public function payPayroll(Carbon $startDate, Carbon $endDate, int $paidByUserId): array
    {
        if ($this->isPeriodPaid($startDate)) {
            throw new \RuntimeException('La nómina de este período ya fue pagada.');
        }

        $report = $this->generatePayrollReport($startDate, $endDate);

        if (count($report['employees']) === 0) {
            throw new \RuntimeException('No hay empleados activos para pagar en este período.');
        }

        DB::transaction(function () use ($report, $startDate, $endDate, $paidByUserId): void {
            $paidAt = now();
            $month = $startDate->format('m');
            $year = $startDate->year;

            foreach ($report['employees'] as $row) {
                Payroll::query()->create([
                    'employee_id' => $row['employee_id'],
                    'month' => $month,
                    'year' => $year,
                    'base_salary' => $row['base_salary'],
                    'gross_salary' => $row['gross_salary'],
                    'bonuses' => $row['bonuses'],
                    'inss_deduction' => $row['inss_deduction'],
                    'ir_deduction' => $row['ir_deduction'],
                    'deductions' => $row['other_deductions'],
                    'loan_payments' => $row['loan_payments'],
                    'net_salary' => $row['net_salary'],
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'paid_by' => $paidByUserId,
                ]);

                Bonus::query()
                    ->where('employee_id', $row['employee_id'])
                    ->where('status', 'approved')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->update(['status' => 'paid']);

                Deduction::query()
                    ->where('employee_id', $row['employee_id'])
                    ->where('status', 'approved')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->update(['status' => 'deducted']);

                Loan::query()
                    ->where('employee_id', $row['employee_id'])
                    ->where('status', 'active')
                    ->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate)
                    ->get()
                    ->each(function (Loan $loan): void {
                        $remainingBalance = max(0, (float) $loan->remaining_balance - (float) $loan->monthly_payment);

                        $loan->update([
                            'remaining_balance' => $remainingBalance,
                            'status' => $remainingBalance <= 0 ? 'completed' : 'active',
                        ]);
                    });
            }
        });

        return [
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'employees_count' => count($report['employees']),
            'net_salary' => $report['totals']['net_salary'],
        ];
    }
}
