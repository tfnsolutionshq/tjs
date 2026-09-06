<?php

namespace App\Services\Payments;

use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Storage\HybridDisk;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use TCPDF;
use Throwable;

class PaymentReceiptService
{
    /**
     * @return array{
     *     reference: string,
     *     status: string,
     *     amount: int,
     *     currency: string,
     *     provider: string,
     *     paid_at: ?\Illuminate\Support\Carbon,
     *     created_at: ?\Illuminate\Support\Carbon,
     *     type: string,
     *     type_label: string,
     *     detail: string,
     *     payer_name: ?string,
     *     payer_email: ?string,
     *     journal: ?Journal,
     *     journal_title: ?string
     * }
     */
    public function describe(PaymentTransaction $tx): array
    {
        $tx->loadMissing(['user', 'payable']);

        $type = 'payment';
        $typeLabel = 'Payment';
        $detail = 'Platform payment';
        $journal = null;

        if ($tx->payable_type === Journal::class) {
            $type = 'activation';
            $typeLabel = 'Journal activation';
            $journal = $tx->payable instanceof Journal
                ? $tx->payable
                : Journal::query()->find($tx->payable_id);
            $detail = $journal?->title ?: 'Journal #'.$tx->payable_id;
        } elseif ($tx->payable_type === Article::class) {
            $type = 'article';
            $typeLabel = 'Article purchase';
            $article = $tx->payable instanceof Article
                ? $tx->payable
                : Article::query()->with('journal')->find($tx->payable_id);
            $detail = $article?->title ?: 'Article #'.$tx->payable_id;
            $journal = $article?->journal;
        } elseif ($tx->payable_type === MembershipPlan::class) {
            $type = 'membership';
            $typeLabel = 'Membership purchase';
            $plan = $tx->payable instanceof MembershipPlan
                ? $tx->payable
                : MembershipPlan::query()->with('journal')->find($tx->payable_id);
            $detail = $plan?->name ?: 'Plan #'.$tx->payable_id;
            $journal = $plan?->journal;
            if (($plan?->scope ?? null) === 'platform') {
                $typeLabel = 'Platform membership';
            }
        }

        return [
            'reference' => $tx->reference,
            'status' => $tx->status,
            'amount' => (int) $tx->amount,
            'currency' => strtoupper((string) $tx->currency),
            'provider' => (string) $tx->provider,
            'paid_at' => $tx->paid_at,
            'created_at' => $tx->created_at,
            'type' => $type,
            'type_label' => $typeLabel,
            'detail' => $detail,
            'payer_name' => $tx->user?->name,
            'payer_email' => $tx->user?->email,
            'journal' => $journal,
            'journal_title' => $journal?->title,
        ];
    }

    public function userCanDownload(User $user, PaymentTransaction $tx): bool
    {
        if ((int) $user->id === (int) $tx->user_id) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $journal = $this->describe($tx)['journal'];

        return $journal instanceof Journal && $user->canManageJournal($journal);
    }

    public function filename(PaymentTransaction $tx): string
    {
        $safeRef = preg_replace('/[^A-Za-z0-9_-]+/', '-', $tx->reference) ?: 'receipt';

        return 'receipt-'.$safeRef.'.pdf';
    }

    public function pdfBinary(PaymentTransaction $tx): string
    {
        if ($tx->status !== 'success') {
            throw new RuntimeException('Receipts are only available for successful payments.');
        }

        $info = $this->describe($tx);
        $platform = config('tjs.full_name', config('tjs.name', 'TJS'));
        $org = config('tjs.organization', $platform);
        $paidAt = ($info['paid_at'] ?? $info['created_at'])
            ?->timezone(config('app.timezone'))
            ->format('M j, Y · g:i A') ?? '—';

        $useJournalLogo = in_array($info['type'], ['article', 'membership'], true)
            && $info['journal'] instanceof Journal
            && filled($info['journal']->logo_path);

        $platformLogo = $this->platformLogoPath();
        $journalLogo = null;
        $tempFiles = [];

        if ($useJournalLogo) {
            $journalLogo = $this->materializeLogoPath($info['journal'], $tempFiles);
        }

        $primaryLogo = $journalLogo ?: $platformLogo;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($platform);
        $pdf->SetAuthor($org);
        $pdf->SetTitle('Payment receipt '.$info['reference']);
        $pdf->SetSubject($info['type_label']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pageW = $pdf->getPageWidth();
        $pageH = $pdf->getPageHeight();

        // Soft page background
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(0, 0, $pageW, $pageH, 'F');

        // Faint platform watermark for journal-scoped payments
        if ($useJournalLogo && $platformLogo) {
            $pdf->SetAlpha(0.055);
            $wm = 92;
            $pdf->Image($platformLogo, ($pageW - $wm) / 2, ($pageH - $wm) / 2 - 8, $wm, $wm, '', '', '', false, 300, '', false, false, 0, 'CM');
            $pdf->SetAlpha(1);
        }

        // Header band
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, 0, $pageW, 42, 'F');
        $pdf->SetFillColor(37, 99, 235);
        $pdf->Rect(0, 42, $pageW, 1.4, 'F');

        if ($primaryLogo) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->RoundedRect(14, 10, 22, 22, 3.5, '1111', 'F');
            $pdf->Image($primaryLogo, 16, 12, 18, 18, '', '', '', false, 300, '', false, false, 0, 'CM');
        }

        $textLeft = $primaryLogo ? 42 : 16;
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetXY($textLeft, 12);
        $pdf->Cell(120, 7, $this->tcpdfSafe($platform), 0, 1, 'L', false, '', 0, false, 'T', 'M');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetX($textLeft);
        $pdf->Cell(120, 5, $this->tcpdfSafe($org), 0, 1, 'L');

        // Paid badge (top right)
        $pdf->SetFillColor(22, 163, 74);
        $pdf->RoundedRect($pageW - 38, 14.5, 24, 8, 4, '1111', 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($pageW - 38, 14.5);
        $pdf->Cell(24, 8, 'PAID', 0, 0, 'C');

        // Main card
        $cardX = 14;
        $cardY = 52;
        $cardW = $pageW - 28;

        $detailRows = [
            ['Type', $info['type_label']],
            ['Description', $info['detail']],
        ];
        if (! empty($info['journal_title'])) {
            $detailRows[] = ['Journal', $info['journal_title']];
        }
        $payer = trim((string) ($info['payer_name'] ?: '—'));
        if (! empty($info['payer_email'])) {
            $payer .= "\n".$info['payer_email'];
        }
        $detailRows[] = ['Payer', $payer];
        $detailRows[] = ['Reference', $info['reference']];
        $detailRows[] = ['Paid at', $paidAt];
        $detailRows[] = ['Provider', ucfirst($info['provider'] ?: 'paystack')];
        $detailRows[] = ['Currency', $info['currency']];
        $detailRows[] = ['Status', 'Successful'];

        $rowsHeight = 0;
        foreach ($detailRows as [, $value]) {
            $rowsHeight += (str_contains((string) $value, "\n") ? 14 : 10) + 1.5;
        }
        $cardH = max(118, 50 + 12 + $rowsHeight + 14);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect($cardX, $cardY, $cardW, $cardH, 5, '1111', 'DF');

        // Accent strip inside card
        $pdf->SetFillColor(239, 246, 255);
        $pdf->RoundedRect($cardX + 8, $cardY + 8, $cardW - 16, 34, 3.5, '1111', 'F');

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($cardX + 14, $cardY + 12);
        $pdf->Cell(80, 4, 'AMOUNT PAID', 0, 1, 'L');

        $pdf->SetTextColor(21, 128, 61);
        $pdf->SetFont('helvetica', 'B', 22);
        $pdf->SetXY($cardX + 14, $cardY + 17);
        $pdf->Cell(110, 12, $this->tcpdfSafe(number_format($info['amount']).' '.$info['currency']), 0, 1, 'L');

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($cardX + $cardW - 78, $cardY + 12);
        $pdf->Cell(60, 4, 'RECEIPT', 0, 1, 'R');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($cardX + $cardW - 78, $cardY + 18);
        $pdf->Cell(60, 6, 'Official payment', 0, 1, 'R');
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($cardX + $cardW - 78, $cardY + 25);
        $pdf->Cell(60, 4, $this->tcpdfSafe($paidAt), 0, 1, 'R');

        // Section title
        $y = $cardY + 50;
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY($cardX + 12, $y);
        $pdf->Cell($cardW - 24, 6, 'Transaction details', 0, 1, 'L');

        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($cardX + 12, $y + 8, $cardX + $cardW - 12, $y + 8);

        $y = $y + 12;
        foreach ($detailRows as $i => [$label, $value]) {
            $bg = $i % 2 === 0;
            $blockH = str_contains((string) $value, "\n") ? 14 : 10;
            if ($bg) {
                $pdf->SetFillColor(248, 250, 252);
                $pdf->RoundedRect($cardX + 10, $y - 1.5, $cardW - 20, $blockH, 2, '1111', 'F');
            }

            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($cardX + 14, $y);
            $pdf->Cell(42, 7, strtoupper($label), 0, 0, 'L');

            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('helvetica', '', 9.5);
            $pdf->SetXY($cardX + 56, $y);
            $pdf->MultiCell($cardW - 72, 4.6, $this->tcpdfSafe((string) $value), 0, 'L', false, 1);

            $y += $blockH + 1.5;
        }

        // Footer band
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, $pageH - 28, $pageW, 28, 'F');
        $pdf->SetFillColor(37, 99, 235);
        $pdf->Rect(0, $pageH - 28, $pageW, 1.2, 'F');

        if ($platformLogo && $useJournalLogo) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->RoundedRect(14, $pageH - 22, 12, 12, 2.5, '1111', 'F');
            $pdf->Image($platformLogo, 15.2, $pageH - 20.8, 9.5, 9.5, '', '', '', false, 300, '', false, false, 0, 'CM');
            $footerTextX = 30;
        } elseif ($platformLogo && ! $useJournalLogo) {
            $footerTextX = 16;
        } else {
            $footerTextX = 16;
        }

        $pdf->SetTextColor(226, 232, 240);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($footerTextX, $pageH - 21);
        $pdf->MultiCell($pageW - $footerTextX - 14, 4, $this->tcpdfSafe(
            'Generated by '.$platform.'. Quote reference '.$info['reference'].' if you need support.'
        ), 0, 'L');

        $binary = $pdf->Output('', 'S');

        foreach (array_unique($tempFiles) as $temp) {
            if (is_string($temp) && is_file($temp)) {
                @unlink($temp);
            }
        }

        return $binary;
    }

    public function downloadResponse(PaymentTransaction $tx)
    {
        return response($this->pdfBinary($tx), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($tx).'"',
        ]);
    }

    public function notifyPayer(PaymentTransaction $tx): void
    {
        $tx->loadMissing('user');

        if ($tx->status !== 'success' || ! $tx->user) {
            return;
        }

        $tx->user->notify(new \App\Notifications\PaymentReceiptNotification($tx));
    }

    private function platformLogoPath(): ?string
    {
        $relative = ltrim((string) config('tjs.brand_icon', 'images/tfns-logo.jpeg'), '/');
        $path = public_path($relative);

        return is_file($path) ? $path : null;
    }

    /**
     * @param  list<string>  $tempFiles
     */
    private function materializeLogoPath(Journal $journal, array &$tempFiles): ?string
    {
        $path = $this->journalLogoPath($journal);
        if ($path && str_starts_with($path, sys_get_temp_dir())) {
            $tempFiles[] = $path;
        }

        return $path;
    }

    private function journalLogoPath(?Journal $journal): ?string
    {
        if (! $journal || ! filled($journal->logo_path)) {
            return null;
        }

        $path = (string) $journal->logo_path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $this->downloadRemoteToTemp($path);
        }

        try {
            $diskName = app(HybridDisk::class)->locate(
                $path,
                HybridDisk::KIND_MEDIA,
                $journal->logo_disk
            );
            if (! $diskName) {
                return null;
            }

            $disk = Storage::disk($diskName);
            if (method_exists($disk, 'path')) {
                try {
                    $local = $disk->path($path);
                    if (is_file($local)) {
                        return $local;
                    }
                } catch (Throwable) {
                    // fall through to stream copy
                }
            }

            $contents = $disk->get($path);
            if (! is_string($contents) || $contents === '') {
                return null;
            }

            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'png';
            $temp = tempnam(sys_get_temp_dir(), 'tjs-logo-');
            if ($temp === false) {
                return null;
            }
            $target = $temp.'.'.$ext;
            @unlink($temp);
            file_put_contents($target, $contents);

            return $target;
        } catch (Throwable) {
            return null;
        }
    }

    private function downloadRemoteToTemp(string $url): ?string
    {
        try {
            $contents = @file_get_contents($url);
            if (! is_string($contents) || $contents === '') {
                return null;
            }
            $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'png';
            $temp = tempnam(sys_get_temp_dir(), 'tjs-logo-');
            if ($temp === false) {
                return null;
            }
            $target = $temp.'.'.$ext;
            @unlink($temp);
            file_put_contents($target, $contents);

            return $target;
        } catch (Throwable) {
            return null;
        }
    }

    private function tcpdfSafe(string $value): string
    {
        return str_replace(['<', '>'], ['(', ')'], $value);
    }
}
