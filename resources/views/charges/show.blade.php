<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cobrança PIX - Nuvende</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Cobrança PIX Gerada</h1>
                <p class="mt-2 text-sm text-gray-600">Escaneie o QR Code para realizar o pagamento</p>
            </div>

            <!-- Card Principal -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Status -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <span class="text-white text-sm font-medium">Status da Cobrança:</span>
                        <span id="status-badge" class="px-4 py-2 rounded-full text-sm font-semibold {{ isset($charge['status']) && $charge['status'] === 'CONCLUIDA' ? 'bg-green-500' : 'bg-yellow-400' }} text-white">
                            <span id="status-text">{{ $charge['status'] ?? 'ATIVA' }}</span>
                        </span>
                    </div>
                </div>

                <div class="p-8">
                    <!-- Valor -->
                    <div class="text-center mb-8">
                        <p class="text-sm text-gray-600 mb-2">Valor da Cobrança</p>
                        <p class="text-4xl font-bold text-gray-900">
                            R$ {{ number_format($charge['valor']['original'] ?? 0, 2, ',', '.') }}
                        </p>
                    </div>

                    <!-- QR Code -->
                    @if($qrCodeUrl)
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <div class="text-center mb-4">
                            <img src="{{ $qrCodeUrl }}" alt="QR Code PIX" class="mx-auto rounded-lg shadow-md">
                        </div>

                        <!-- PIX Copia e Cola -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Código PIX Copia e Cola:
                            </label>
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    id="pix-code"
                                    value="{{ $charge['qrCode'] ?? '' }}"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono bg-white overflow-x-auto"
                                    readonly>
                                <button
                                    onclick="copyPixCode()"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 text-sm font-medium whitespace-nowrap">
                                    Copiar
                                </button>
                            </div>
                            <p id="copy-feedback" class="mt-2 text-sm text-green-600 hidden">✓ Código copiado!</p>
                        </div>
                    </div>
                    @endif

                    <!-- Detalhes -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalhes da Cobrança</h3>

                        <div class="space-y-3">
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">TXID:</span>
                                <span class="font-mono text-sm text-gray-900">{{ $charge['txid'] ?? 'N/A' }}</span>
                            </div>

                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Revisão:</span>
                                <span class="text-gray-900">{{ $charge['revisao'] ?? 'N/A' }}</span>
                            </div>

                            @if(isset($charge['devedor']['nome']))
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Pagador:</span>
                                <span class="text-gray-900">{{ $charge['devedor']['nome'] }}</span>
                            </div>
                            @endif

                            @if(isset($charge['devedor']['cpf']))
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">CPF:</span>
                                <span class="text-gray-900 font-mono">{{ $charge['devedor']['cpf'] }}</span>
                            </div>
                            @endif

                            @if(isset($charge['solicitacaoPagador']))
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Descrição:</span>
                                <span class="text-gray-900">{{ $charge['solicitacaoPagador'] }}</span>
                            </div>
                            @endif

                            @if(isset($charge['chave']))
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Chave PIX:</span>
                                <span class="text-gray-900 font-mono text-sm">{{ substr($charge['chave'], 0, 20) }}...</span>
                            </div>
                            @endif

                            @if(isset($charge['calendario']['criacao']))
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Criado em:</span>
                                <span class="text-gray-900">{{ date('d/m/Y H:i', strtotime($charge['calendario']['criacao'])) }}</span>
                            </div>
                            @endif

                            @if(isset($charge['calendario']['expiracao']))
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Expira em:</span>
                                <span class="text-gray-900">{{ $charge['calendario']['expiracao'] }} segundos</span>
                            </div>
                            @endif

                            @if(isset($charge['pix']) && is_array($charge['pix']) && count($charge['pix']) > 0)
                            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-green-800 font-semibold mb-2">✓ Pagamento Recebido!</p>
                                <div class="text-sm text-green-700">
                                    <p><strong>Valor:</strong> R$ {{ number_format($charge['pix'][0]['valor'] ?? 0, 2, ',', '.') }}</p>
                                    @if(isset($charge['pix'][0]['horario']))
                                    <p><strong>Data:</strong> {{ date('d/m/Y H:i', strtotime($charge['pix'][0]['horario'])) }}</p>
                                    @endif
                                    @if(isset($charge['pix'][0]['endToEndId']))
                                    <p><strong>ID:</strong> {{ $charge['pix'][0]['endToEndId'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Atualização Automática -->
                    @if(($charge['status'] ?? 'ATIVA') !== 'CONCLUIDA')
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800 text-center">
                            <span class="inline-block w-2 h-2 bg-blue-600 rounded-full animate-pulse mr-2"></span>
                            Status atualizado automaticamente a cada 5 segundos
                        </p>
                    </div>
                    @endif

                    <!-- Botões -->
                    <div class="mt-8 flex gap-3">
                        <a
                            href="{{ route('charges.create') }}"
                            class="flex-1 text-center bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-medium hover:bg-gray-300 transition duration-200">
                            Nova Cobrança
                        </a>
                        <button
                            onclick="checkStatus()"
                            class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition duration-200">
                            Verificar Pagamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const txid = '{{ $charge["txid"] ?? "" }}';

        // Copia o código PIX
        function copyPixCode() {
            const pixCode = document.getElementById('pix-code');
            pixCode.select();
            pixCode.setSelectionRange(0, 99999); // Para mobile

            navigator.clipboard.writeText(pixCode.value).then(() => {
                const feedback = document.getElementById('copy-feedback');
                feedback.classList.remove('hidden');
                setTimeout(() => feedback.classList.add('hidden'), 2000);
            }).catch(err => {
                // Fallback para navegadores antigos
                document.execCommand('copy');
                const feedback = document.getElementById('copy-feedback');
                feedback.classList.remove('hidden');
                setTimeout(() => feedback.classList.add('hidden'), 2000);
            });
        }

        // Verifica o status da cobrança
        async function checkStatus() {
            try {
                const response = await fetch(`/charges/${txid}/status`);
                const data = await response.json();

                if (data.success) {
                    updateStatus(data.status, data.data);
                }
            } catch (error) {
                console.error('Erro ao verificar status:', error);
            }
        }

        // Atualiza o badge de status
        function updateStatus(status, chargeData) {
            const statusBadge = document.getElementById('status-badge');
            const statusText = document.getElementById('status-text');

            statusText.textContent = status;

            if (status === 'CONCLUIDA') {
                statusBadge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-green-500 text-white';
                showPaymentSuccess();

                // Recarrega a página para mostrar detalhes do pagamento
                setTimeout(() => location.reload(), 2000);
            } else if (status === 'REMOVIDA_PELO_USUARIO_RECEBEDOR' || status === 'REMOVIDA_PELO_PSP') {
                statusBadge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-red-500 text-white';
            } else {
                statusBadge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-yellow-400 text-white';
            }
        }

        // Mostra mensagem de sucesso no pagamento
        function showPaymentSuccess() {
            if (!document.getElementById('success-message')) {
                const successDiv = document.createElement('div');
                successDiv.id = 'success-message';
                successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-bounce';
                successDiv.innerHTML = `
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="font-semibold">Pagamento confirmado!</span>
                    </div>
                `;
                document.body.appendChild(successDiv);

                setTimeout(() => successDiv.remove(), 5000);
            }
        }

        // Atualização automática do status a cada 5 segundos (apenas se não estiver concluída)
        const currentStatus = '{{ $charge["status"] ?? "ATIVA" }}';
        if (currentStatus !== 'CONCLUIDA') {
            setInterval(checkStatus, 5000);
        }
    </script>
</body>

</html>