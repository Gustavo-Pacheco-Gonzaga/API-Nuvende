<?php

namespace Tests\Feature;

use App\Services\NuvendeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChargeTest extends TestCase
{
    /**
     * Testa se a página de criação de cobrança é exibida
     */
    public function test_charge_creation_page_is_displayed(): void
    {
        $response = $this->get('/charges/create');

        $response->assertStatus(200);
        $response->assertSee('Nova Cobrança PIX');
        $response->assertSee('Valor da Cobrança');
        $response->assertSee('Nome do Pagador');
    }

    /**
     * Testa validação de campos obrigatórios
     */
    public function test_charge_creation_requires_mandatory_fields(): void
    {
        $response = $this->post('/charges', []);

        $response->assertSessionHasErrors(['amount', 'payer_name']);
    }

    /**
     * Testa validação do valor mínimo
     */
    public function test_charge_amount_must_be_positive(): void
    {
        $response = $this->post('/charges', [
            'amount' => -10,
            'payer_name' => 'João Silva',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    /**
     * Testa criação de cobrança com sucesso (mock)
     */
    public function test_charge_can_be_created_successfully(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'fake-token-123',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test-txid-12345',
                'revisao' => 0,
                'status' => 'ATIVA',
                'calendario' => [
                    'criacao' => '2024-01-01T10:00:00Z',
                    'expiracao' => 3600,
                ],
                'valor' => [
                    'original' => '100.00',
                ],
                'chave' => '59ba4ca7-e1d4-433f-8dbf-77e692434a69',
                'qrCode' => '00020126580014br.gov.bcb.pix',
                'devedor' => [
                    'nome' => 'João Silva',
                    'cpf' => '12345678900',
                ],
            ], 201),
        ]);

        $response = $this->post('/charges', [
            'amount' => 100.00,
            'payer_name' => 'João Silva',
            'payer_document' => '12345678900',
            'description' => 'Teste de cobrança',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * Testa exibição da página de cobrança (mock)
     */
    public function test_charge_details_page_is_displayed(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'fake-token-123',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test-txid-123',
                'revisao' => 0,
                'status' => 'ATIVA',
                'calendario' => [
                    'criacao' => '2024-01-01T10:00:00Z',
                    'expiracao' => 3600,
                ],
                'valor' => [
                    'original' => '100.00',
                ],
                'chave' => 'test-pix-key',
                'qrCode' => '00020126580014br.gov.bcb.pix',
                'devedor' => [
                    'nome' => 'João Silva',
                ],
            ], 200),
        ]);

        $response = $this->get('/charges/test-txid-123');

        $response->assertStatus(200);
        $response->assertSee('Cobrança PIX Gerada');
        $response->assertSee('R$ 100,00');
        $response->assertSee('João Silva');
        $response->assertSee('ATIVA');
    }

    /**
     * Testa consulta de status via AJAX (mock)
     */
    public function test_charge_status_can_be_checked(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'fake-token-123',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test-txid-456',
                'status' => 'CONCLUIDA',
                'valor' => [
                    'original' => '200.00',
                ],
            ], 200),
        ]);

        $response = $this->get('/charges/test-txid-456/status');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'CONCLUIDA',
        ]);
    }

    /**
     * Testa redirecionamento da página inicial
     */
    public function test_home_redirects_to_charge_creation(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/charges/create');
    }

    /**
     * Testa tratamento de erro na API
     */
    public function test_handles_api_errors_gracefully(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $response = $this->post('/charges', [
            'amount' => 100.00,
            'payer_name' => 'João Silva',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Testa validação de tempo de expiração
     */
    public function test_expiration_time_validation(): void
    {
        $response = $this->post('/charges', [
            'amount' => 100.00,
            'payer_name' => 'João Silva',
            'expiration_hours' => 30, // Maior que o máximo permitido
        ]);

        $response->assertSessionHasErrors(['expiration_hours']);
    }

    /**
     * Testa criação de cobrança sem CPF
     */
    public function test_charge_can_be_created_without_cpf(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response([
                'access_token' => 'fake-token-123',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v2/cobranca/cob/*' => Http::response([
                'txid' => 'test-txid-no-cpf',
                'status' => 'ATIVA',
                'valor' => ['original' => '50.00'],
                'qrCode' => '00020126580014br.gov.bcb.pix',
            ], 201),
        ]);

        $response = $this->post('/charges', [
            'amount' => 50.00,
            'payer_name' => 'Maria Silva',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
