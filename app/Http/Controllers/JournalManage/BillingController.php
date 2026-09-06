<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\Journal\JournalBillingService;
use App\Support\JournalActivation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingController extends Controller
{
    public function __construct(private JournalBillingService $billing)
    {
    }

    public function index(Request $request, Journal $journal): View|JsonResponse
    {
        $summary = $this->billing->summary($journal);
        $transactions = $this->billing->paginate($journal, $request);
        $filters = [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
            'type' => $request->string('type')->toString(),
            'direction' => $request->string('direction')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ];

        if ($request->boolean('partial') || $request->wantsJson()) {
            return response()->json([
                'html' => view('journal-manage.billing.partials.transactions', [
                    'journal' => $journal,
                    'transactions' => $transactions,
                    'filters' => $filters,
                ])->render(),
                'count' => $transactions->total(),
                'export_url' => route('journal.manage.billing.export', array_merge(
                    ['journal' => $journal],
                    array_filter($filters, fn ($v) => $v !== '')
                )),
            ]);
        }

        return view('journal-manage.billing.index', [
            'journal' => $journal,
            'summary' => $summary,
            'transactions' => $transactions,
            'filters' => $filters,
            'activation' => [
                'status' => $journal->activation_status,
                'expires_at' => $journal->activation_expires_at,
                'unlocked' => $journal->managementUnlocked(),
                'fee_enabled' => JournalActivation::enabled(),
                'price' => JournalActivation::price(),
                'days' => JournalActivation::durationDays(),
                'currency' => JournalActivation::currency(),
            ],
        ]);
    }

    public function export(Request $request, Journal $journal): StreamedResponse
    {
        $rows = $this->billing->exportRows($journal, $request);
        $filename = 'journal-'.$journal->slug.'-transactions-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Date',
                'Paid at',
                'Type',
                'Direction',
                'Detail',
                'Reference',
                'Status',
                'Amount',
                'Currency',
                'Payer name',
                'Payer email',
                'Provider',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    optional($row['created_at'])?->toDateTimeString(),
                    optional($row['paid_at'])?->toDateTimeString(),
                    $row['type_label'],
                    $row['direction'] === 'in' ? 'Income' : 'Expense',
                    $row['detail'],
                    $row['reference'],
                    $row['status'],
                    $row['amount'],
                    $row['currency'],
                    $row['payer_name'],
                    $row['payer_email'],
                    $row['provider'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
