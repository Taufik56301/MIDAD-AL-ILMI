<?php

namespace App\Http\Controllers;

use App\Exports\ArrayReportExport;
use App\Models\Attendance;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $period = $this->reportService->resolvePeriod(
            $request->query('start_date'),
            $request->query('end_date')
        );

        $startDate = $period['start']->toDateString();
        $endDate = $period['end']->toDateString();
        $tab = $request->query('tab', 'sales');

        $sales = $this->reportService->salesData($period['start'], $period['end']);
        $cashflow = $this->reportService->cashflowData($period['start'], $period['end']);
        $inventory = $this->reportService->inventoryData();
        $attendance = $this->reportService->attendanceData($period['start'], $period['end']);

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'tab',
            'sales',
            'cashflow',
            'inventory',
            'attendance'
        ));
    }

    public function exportExcel(Request $request, string $type): BinaryFileResponse
    {
        $payload = $this->buildExportPayload(
            $type,
            $request->query('start_date'),
            $request->query('end_date')
        );

        $filename = "{$payload['slug']}-".now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new ArrayReportExport($payload['title'], $payload['headings'], $payload['rows']),
            $filename
        );
    }

    public function exportPdf(Request $request, string $type): Response
    {
        $payload = $this->buildExportPayload(
            $type,
            $request->query('start_date'),
            $request->query('end_date')
        );

        $pdf = Pdf::loadView('pdfs.report', $payload)->setPaper('a4', 'landscape');
        $filename = "{$payload['slug']}-".now()->format('Ymd-His').'.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     periodLabel: string,
     *     headings: list<string>,
     *     rows: list<list<string|int|float>>,
     *     summary: list<array{label: string, value: string}>
     * }
     */
    private function buildExportPayload(string $type, ?string $startDate, ?string $endDate): array
    {
        $period = $this->reportService->resolvePeriod($startDate, $endDate);
        $start = $period['start'];
        $end = $period['end'];
        $periodLabel = $start->format('d/m/Y').' - '.$end->format('d/m/Y');

        return match ($type) {
            'sales' => $this->salesPayload($start, $end, $periodLabel),
            'cashflow' => $this->cashflowPayload($start, $end, $periodLabel),
            'inventory' => $this->inventoryPayload($periodLabel),
            'attendance' => $this->attendancePayload($start, $end, $periodLabel),
            default => abort(404, 'Jenis laporan tidak dikenali.'),
        };
    }

    private function salesPayload(Carbon $start, Carbon $end, string $periodLabel): array
    {
        $data = $this->reportService->salesData($start, $end);
        $rows = $data['orders']->map(fn (Order $o) => [
            $o->order_number,
            $o->created_at->format('d/m/Y H:i'),
            $o->customer->name ?? '-',
            $o->user->name ?? '-',
            $o->paymentStatusLabel(),
            $o->orderStatusLabel(),
            (float) $o->total_amount,
        ])->values()->all();

        return [
            'slug' => 'laporan-penjualan-omset',
            'title' => 'Laporan Penjualan & Omset',
            'periodLabel' => $periodLabel,
            'headings' => ['No. Order', 'Tanggal', 'Pelanggan', 'Admin', 'Status Bayar', 'Status Produksi', 'Total Tagihan (Rp)'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Jumlah Transaksi', 'value' => (string) $data['total_count'].' Order'],
                ['label' => 'Total Omset', 'value' => 'Rp '.number_format($data['total_omset'], 0, ',', '.')],
            ],
        ];
    }

    private function cashflowPayload(Carbon $start, Carbon $end, string $periodLabel): array
    {
        $data = $this->reportService->cashflowData($start, $end);
        $rows = $data['payments']->map(fn (Payment $p) => [
            Carbon::parse($p->date_paid)->format('d/m/Y H:i'),
            $p->order->order_number ?? '-',
            $p->order->customer->name ?? '-',
            strtoupper($p->payment_type),
            ucfirst($p->payment_method),
            $p->user->name ?? '-',
            (float) $p->amount_paid,
        ])->values()->all();

        return [
            'slug' => 'laporan-kas-masuk',
            'title' => 'Laporan Kas Masuk (DP & Pelunasan)',
            'periodLabel' => $periodLabel,
            'headings' => ['Tanggal Bayar', 'No. Order', 'Pelanggan', 'Jenis', 'Metode', 'Kasir', 'Nominal (Rp)'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Kas Masuk', 'value' => 'Rp '.number_format($data['total_cash_in'], 0, ',', '.')],
                ['label' => 'Total DP', 'value' => 'Rp '.number_format($data['total_dp'], 0, ',', '.')],
                ['label' => 'Total Pelunasan', 'value' => 'Rp '.number_format($data['total_lunas'], 0, ',', '.')],
            ],
        ];
    }

    private function inventoryPayload(string $periodLabel): array
    {
        $data = $this->reportService->inventoryData();
        $rows = $data['materials']->map(fn (Material $m) => [
            $m->name,
            $m->type,
            (float) $m->current_stock,
            $m->unit,
            (float) $m->minimal_stock_alert,
            $m->isLowStock() ? 'STOK MENIPIS' : 'Aman',
        ])->values()->all();

        return [
            'slug' => 'laporan-sisa-stok-bahan',
            'title' => 'Laporan Sisa Stok Bahan Gudang',
            'periodLabel' => 'Per '.$periodLabel,
            'headings' => ['Nama Bahan', 'Jenis', 'Stok Tersedia', 'Satuan', 'Batas Minimum', 'Status'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Jenis Bahan', 'value' => $data['materials']->count().' Item'],
                ['label' => 'Perlu Restock (Low Stock)', 'value' => $data['low_stock_count'].' Item'],
            ],
        ];
    }

    private function attendancePayload(Carbon $start, Carbon $end, string $periodLabel): array
    {
        $data = $this->reportService->attendanceData($start, $end);
        $rows = $data['attendances']->map(fn (Attendance $a) => [
            $a->date->format('d/m/Y'),
            $a->user->name ?? '-',
            $a->user ? $a->user->roleLabel() : '-',
            $a->clock_in ? $a->clock_in->format('H:i:s') : '-',
            $a->clock_out ? $a->clock_out->format('H:i:s') : 'Belum Pulang',
            (float) $a->total_hours,
        ])->values()->all();

        return [
            'slug' => 'laporan-kehadiran-karyawan',
            'title' => 'Laporan Kehadiran Karyawan',
            'periodLabel' => $periodLabel,
            'headings' => ['Tanggal', 'Nama Karyawan', 'Jabatan', 'Jam Masuk', 'Jam Pulang', 'Total Jam Kerja'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Log Kehadiran', 'value' => $data['attendances']->count().' Catatan'],
                ['label' => 'Akumulasi Jam Kerja', 'value' => $data['total_hours'].' Jam'],
            ],
        ];
    }
}

