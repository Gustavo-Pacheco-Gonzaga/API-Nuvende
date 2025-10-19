<?php

namespace Tests\Unit;

use App\Services\NuvendeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NuvendeServiceTest extends TestCase
{
    private NuvendeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NuvendeService();
        Cache::flush();
    }

    /**
     * Testa autenticação com sucesso
     */
    public function test_authentication_returns_token(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'test-token-123',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $token = $this->service->authenticate();

        $this->assertEquals('test-token-123', $token);
    }

    /**
     * Testa cache do token de autenticação
     */
    public function test_authentication_token_is_cached(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'cached-token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        // Primeira chamada - deve fazer requisição
        $token1 = $this->service->authenticate();

        // Segunda chamada - deve usar cache
        $token2 = $this->service->authenticate();

        $this->assertEquals($token1, $token2);

        // Verifica que o token está no cache
        $this->assertTrue(Cache::has('nuvende_token'));
    }

    /**
     * Testa tratamento de erro na autenticação
     */
    public function test_authentication_throws_exception_on_failure(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'error' => 'Invalid credentials',
            ], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Falha na autenticação');

        $this->service->authenticate();
    }

    /**
     * Testa criação de cobrança com sucesso
     */
    public function test_create_pix_charge_returns_charge_data(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test-txid-abc123',
                'revisao' => 0,
                'status' => 'ATIVA',
                'calendario' => [
                    'criacao' => '2024-01-01T10:00:00Z',
                    'expiracao' => 3600,
                ],
                'valor' => [
                    'original' => '150.00',
                ],
                'chave' => 'test-pix-key',
                'qrCode' => '00020126580014br.gov.bcb.pix',
            ], 201),
        ]);

        $charge = $this->service->createPixCharge([
            'amount' => 150.00,
            'payer_name' => 'Maria Santos',
            'description' => 'Teste',
        ]);

        $this->assertArrayHasKey('txid', $charge);
        $this->assertArrayHasKey('qrCode', $charge);
        $this->assertEquals('test-txid-abc123', $charge['txid']);
        $this->assertEquals('150.00', $charge['valor']['original']);
    }

    /**
     * Testa tratamento de erro na criação de cobrança
     */
    public function test_create_pix_charge_throws_exception_on_failure(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'error' => 'Invalid data',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Falha ao criar cobrança');

        $this->service->createPixCharge([
            'amount' => 100.00,
            'payer_name' => 'Test User',
        ]);
    }

    /**
     * Testa consulta de status da cobrança
     */
    public function test_get_charge_status_returns_charge_data(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test-txid-456',
                'revisao' => 1,
                'status' => 'CONCLUIDA',
                'valor' => [
                    'original' => '200.00',
                ],
            ], 200),
        ]);

        $charge = $this->service->getChargeStatus('test-txid-456');

        $this->assertEquals('test-txid-456', $charge['txid']);
        $this->assertEquals('CONCLUIDA', $charge['status']);
    }

    /**
     * Testa geração de URL do QR Code
     */
    public function test_generate_qr_code_returns_valid_url(): void
    {
        $pixCode = '00020126580014br.gov.bcb.pix';
        $qrCodeUrl = $this->service->generateQRCode($pixCode);

        $this->assertStringContainsString('qrserver.com', $qrCodeUrl);
        $this->assertStringContainsString(urlencode($pixCode), $qrCodeUrl);
    }

    /**
     * Testa se os dados da cobrança são enviados corretamente
     */
    public function test_charge_data_is_formatted_correctly(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test',
                'status' => 'ATIVA'
            ], 201),
        ]);

        $this->service->createPixCharge([
            'amount' => 250.00,
            'payer_name' => 'José Silva',
            'payer_document' => '12345678900',
            'description' => 'Pagamento teste',
            'expiration_seconds' => 7200,
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['valor']['original'])
                && $data['valor']['original'] === '250.00'
                && $data['devedor']['nome'] === 'José Silva'
                && $data['solicitacaoPagador'] === 'Pagamento teste'
                && $data['calendario']['expiracao'] === 7200;
        });
    }

    /**
     * Testa criação de cobrança sem CPF
     */
    public function test_charge_without_cpf_doesnt_include_cpf_field(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test',
                'status' => 'ATIVA'
            ], 201),
        ]);

        $this->service->createPixCharge([
            'amount' => 100.00,
            'payer_name' => 'Maria Silva',
            'payer_document' => null,
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return !isset($data['devedor']['cpf']);
        });
    }

    /**
     * Testa se headers corretos são enviados
     */
    public function test_correct_headers_are_sent(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test',
                'status' => 'ATIVA'
            ], 201),
        ]);

        $this->service->createPixCharge([
            'amount' => 100.00,
            'payer_name' => 'Test User',
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization')
                && $request->hasHeader('Account-Id')
                && $request->hasHeader('Content-Type', 'application/json');
        });
    }
}
