<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Cobrança PIX - Nuvende</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Nova Cobrança PIX</h1>
                <p class="mt-2 text-sm text-gray-600">Integração com API Nuvende</p>
            </div>

            <!-- Mensagens de Erro/Sucesso -->
            @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                <p class="text-red-700">{{ session('error') }}</p>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
            @endif

            <!-- Formulário -->
            <div class="bg-white shadow-md rounded-lg p-8">
                <form action="{{ route('charges.store') }}" method="POST">
                    @csrf

                    <!-- Valor -->
                    <div class="mb-6">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Valor da Cobrança *
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500">R$</span>
                            <input
                                type="text"
                                name="amount_display"
                                id="amount_display"
                                value="{{ old('amount') }}"
                                class="w-full pl-12 pr-4 py-3 border @error('amount') border-red-500 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="0,00"
                                required>
                            <input type="hidden" name="amount" id="amount" value="{{ old('amount') }}">
                        </div>
                        @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Digite apenas números (ex: 1050 vira R$ 10,50)</p>
                    </div>

                    <!-- Nome do Pagador -->
                    <div class="mb-6">
                        <label for="payer_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome do Pagador *
                        </label>
                        <input
                            type="text"
                            name="payer_name"
                            id="payer_name"
                            value="{{ old('payer_name') }}"
                            class="w-full px-4 py-3 border @error('payer_name') border-red-500 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Digite o nome completo"
                            required>
                        @error('payer_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- CPF -->
                    <div class="mb-6">
                        <label for="payer_document" class="block text-sm font-medium text-gray-700 mb-2">
                            CPF (Opcional)
                        </label>
                        <input
                            type="text"
                            name="payer_document"
                            id="payer_document"
                            value="{{ old('payer_document') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="123.456.789-00"
                            maxlength="14">
                        <p class="mt-1 text-xs text-gray-500">Digite apenas números (formatação automática)</p>
                    </div>

                    <!-- Descrição -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descrição (Opcional)
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            maxlength="140"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Descreva o motivo da cobrança (máx. 140 caracteres)">{{ old('description') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Será exibido ao pagador</p>
                    </div>

                    <!-- Tempo de Expiração -->
                    <div class="mb-6">
                        <label for="expiration_hours" class="block text-sm font-medium text-gray-700 mb-2">
                            Validade da Cobrança
                        </label>
                        <select
                            name="expiration_hours"
                            id="expiration_hours"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="1" {{ old('expiration_hours') == '1' ? 'selected' : '' }}>1 hora</option>
                            <option value="2" {{ old('expiration_hours') == '2' ? 'selected' : '' }}>2 horas</option>
                            <option value="6" {{ old('expiration_hours') == '6' ? 'selected' : '' }}>6 horas</option>
                            <option value="12" {{ old('expiration_hours') == '12' ? 'selected' : '' }}>12 horas</option>
                            <option value="24" {{ old('expiration_hours', '24') == '24' ? 'selected' : '' }}>24 horas (padrão)</option>
                        </select>
                    </div>

                    <!-- Botões -->
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Gerar Cobrança PIX
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>* Campos obrigatórios</p>
            </div>
        </div>
    </div>

    <!-- Script externo -->
    <script src="{{ asset('js/charge-form.js') }}"></script>
</body>

</html>