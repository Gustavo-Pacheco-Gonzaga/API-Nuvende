<?php

namespace App\Http\Controllers;

use App\Services\NuvendeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ChargeController extends Controller
{
    private NuvendeService $nuvendeService;

    public function __construct(NuvendeService $nuvendeService)
    {
        $this->nuvendeService = $nuvendeService;
    }

    public function create()
    {
        return view('charges.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payer_name' => 'required|string|max:255',
            'payer_document' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'expiration_hours' => 'nullable|integer|min:1|max:24',
        ], [
            'amount.required' => 'O valor é obrigatório',
            'amount.numeric' => 'O valor deve ser numérico',
            'amount.min' => 'O valor mínimo é R$ 0,01',
            'payer_name.required' => 'O nome do pagador é obrigatório',
            'expiration_hours.min' => 'O tempo mínimo é 1 hora',
            'expiration_hours.max' => 'O tempo máximo é 24 horas',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $expirationSeconds = ($request->expiration_hours ?? 24) * 3600;

            $charge = $this->nuvendeService->createPixCharge([
                'amount' => $request->amount,
                'payer_name' => $request->payer_name,
                'payer_document' => $request->payer_document,
                'description' => $request->description,
                'expiration_seconds' => $expirationSeconds,
            ]);

            return redirect()->route('charges.show', ['txid' => $charge['txid']])
                ->with('success', 'Cobrança criada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao criar cobrança: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(string $txid)
    {
        try {
            $charge = $this->nuvendeService->getChargeStatus($txid);
            
            $qrCodeUrl = null;
            if (isset($charge['qrCode'])) {
                $qrCodeUrl = $this->nuvendeService->generateQRCode($charge['qrCode']);
            }

            return view('charges.show', compact('charge', 'qrCodeUrl'));
        } catch (Exception $e) {
            return redirect()->route('charges.create')
                ->with('error', 'Erro ao buscar cobrança: ' . $e->getMessage());
        }
    }

    public function status(string $txid)
    {
        try {
            $charge = $this->nuvendeService->getChargeStatus($txid);
            
            return response()->json([
                'success' => true,
                'status' => $charge['status'] ?? 'ATIVA',
                'data' => $charge,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}