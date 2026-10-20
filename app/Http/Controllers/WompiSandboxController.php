<?php

namespace App\Http\Controllers;

use App\Services\OrderPaymentService;
use App\Services\WompiService;
use Illuminate\Http\Request;

class WompiSandboxController extends Controller
{
    public function show(Request $request, WompiService $wompi)
    {
        $reference = (string) $request->query('reference', '');
        $amountInCents = (int) $request->query('amount_in_cents', 0);
        $currency = (string) $request->query('currency', 'COP');
        $signature = (string) $request->query('signature', '');
        $redirectUrl = (string) $request->query('redirect_url', route('consumer.home'));

        $expectedSignature = $wompi->integritySignature($reference, $amountInCents, $currency);
        $signatureValid = hash_equals($expectedSignature, $signature);

        return view('payment.wompi-sandbox', [
            'reference'       => $reference,
            'amountInCents'   => $amountInCents,
            'amountCop'       => $amountInCents / 100,
            'currency'        => $currency,
            'signature'       => $signature,
            'signatureValid'  => $signatureValid,
            'redirectUrl'     => $redirectUrl,
        ]);
    }

    public function complete(Request $request, WompiService $wompi, OrderPaymentService $payments)
    {
        $data = $request->validate([
            'reference'       => 'required|string',
            'amount_in_cents' => 'required|integer|min:100',
            'currency'        => 'required|string|max:10',
            'signature'       => 'required|string',
            'redirect_url'    => 'required|string',
            'status'          => 'required|in:APPROVED,DECLINED',
        ]);

        $expected = $wompi->integritySignature($data['reference'], (int) $data['amount_in_cents'], $data['currency']);
        abort_unless(hash_equals($expected, $data['signature']), 403, 'Firma de integridad SHA-256 inválida.');

        $txId = 'sbx_' . strtolower(substr(md5($data['reference'] . microtime(true)), 0, 16));

        $tx = [
            'id'              => $txId,
            'status'          => $data['status'],
            'reference'       => $data['reference'],
            'amount_in_cents' => (int) $data['amount_in_cents'],
            'currency'        => $data['currency'],
        ];

        $payments->handleTransaction($tx);

        $separator = str_contains($data['redirect_url'], '?') ? '&' : '?';
        return redirect()->to($data['redirect_url'] . $separator . 'id=' . urlencode($txId));
    }
}
