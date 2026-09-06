<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\Payments\PaymentReceiptService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentReceiptController extends Controller
{
    public function __construct(private PaymentReceiptService $receipts)
    {
    }

    public function index(Request $request): View
    {
        $transactions = PaymentTransaction::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $described = $transactions->getCollection()->map(function (PaymentTransaction $tx) {
            $info = $this->receipts->describe($tx);
            $info['id'] = $tx->id;
            $info['can_download'] = $tx->status === 'success';

            return $info;
        });

        $transactions->setCollection($described);

        return view('payments.index', [
            'transactions' => $transactions,
        ]);
    }

    public function download(Request $request, PaymentTransaction $paymentTransaction)
    {
        abort_unless($this->receipts->userCanDownload($request->user(), $paymentTransaction), 403);
        abort_unless($paymentTransaction->status === 'success', 404, 'Receipt is only available after a successful payment.');

        return $this->receipts->downloadResponse($paymentTransaction);
    }
}
