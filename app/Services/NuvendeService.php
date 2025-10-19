<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class NuvendeService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $pixKey;
    private string $accountId;

    public function __construct()
    {
        // Valores HARDCODED para garantir que funcione
        $this->baseUrl = 'https://api-h.nuvende.com.br';
        $this->clientId = 'de846a35-d51d-42c6-9ffe-b5a71a0685dd';
        $this->clientSecret = 'teste2025';
        $this->pixKey = '59ba4ca7-e1d4-433f-8dbf-77e692434a69';
        $this->accountId = 'd9c4e578-fd05-4de0-8543-fb32235114a5';
    }

    public function authenticate(): string
    {
        $cachedToken = Cache::get('nuvende_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        try {

            // Apenas grant_type e scope no body
            $bodyData = http_build_query([
                'grant_type' => 'client_credentials',
                'scope' => 'kyc.background-check.natural-person kyc.background-check.legal-person cob.write cob.read webhooks.read webhooks.write merchants.read merchants.write terminals.read terminals.write transactions.read transactions.write',
            ]);


            // Client ID e Secret vão no Basic Auth!
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
                ->withBody($bodyData, 'application/x-www-form-urlencoded')
                ->post("{$this->baseUrl}/api/v2/auth/login");


            if ($response->failed()) {
                throw new Exception('Falha na autenticação: ' . $response->body());
            }

            $responseData = $response->json();
            $token = $responseData['access_token'] ?? null;

            if (!$token) {
                throw new Exception('Token não retornado pela API. Resposta: ' . json_encode($responseData));
            }

            $expiresIn = $responseData['expires_in'] ?? 3600;
            Cache::put('nuvende_token', $token, now()->addSeconds($expiresIn - 60));


            return $token;
        } catch (Exception $e) {
            throw new Exception('Erro ao autenticar na Nuvende: ' . $e->getMessage());
        }
    }

    public function createPixCharge(array $data): array
    {
        $token = $this->authenticate();
        $txid = Str::random(32);

        try {

            $payload = [
                'chave' => $this->pixKey,
                'solicitacaoPagador' => $data['description'] ?? 'Pagamento via PIX',
                'calendario' => [
                    'expiracao' => $data['expiration_seconds'] ?? 3600,
                ],
                'valor' => [
                    'original' => number_format($data['amount'], 2, '.', ''),
                ],
                'devedor' => [
                    'nome' => $data['payer_name'],
                    'cpf' => $data['payer_document'] ?? null,
                ],
            ];

            if (empty($payload['devedor']['cpf'])) {
                unset($payload['devedor']['cpf']);
            }


            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Account-Id' => $this->accountId,
                'Authorization' => "Bearer {$token}",
            ])->put("{$this->baseUrl}/api/v2/cobranca/cob/{$txid}", $payload);


            if ($response->failed()) {
                throw new Exception('Falha ao criar cobrança: ' . $response->body());
            }

            $result = $response->json();

            return $result;
        } catch (Exception $e) {
            throw new Exception('Erro ao criar cobrança PIX: ' . $e->getMessage());
        }
    }

    public function getChargeStatus(string $txid): array
    {
        $token = $this->authenticate();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Account-Id' => $this->accountId,
                'Authorization' => "Bearer {$token}",
            ])->get("{$this->baseUrl}/api/v2/cobranca/cob/{$txid}");

            if ($response->failed()) {
                throw new Exception('Falha ao consultar cobrança: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            throw new Exception('Erro ao consultar status da cobrança: ' . $e->getMessage());
        }
    }

    public function generateQRCode(string $qrCodeData): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrCodeData);
    }
}
