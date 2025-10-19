/**
 * Máscaras e validações para o formulário de cobrança
 */

document.addEventListener('DOMContentLoaded', function () {
    initAmountMask();
    initCpfMask();
    initFormSubmit();
});

/**
 * Máscara para Valor Monetário (R$ 1.234,56)
 */
function initAmountMask() {
    const amountDisplay = document.getElementById('amount_display');
    const amountHidden = document.getElementById('amount');

    if (!amountDisplay || !amountHidden) return;

    amountDisplay.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');

        if (value === '') {
            e.target.value = '';
            amountHidden.value = '';
            return;
        }

        // Converte para número (centavos)
        let numValue = parseInt(value);

        // Converte para formato decimal
        let floatValue = numValue / 100;

        // Atualiza o valor hidden (formato para enviar: 10.50)
        amountHidden.value = floatValue.toFixed(2);

        // Formata para exibição (1.234,56)
        let formatted = floatValue.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        e.target.value = formatted;
    });

    // Validação ao sair do campo
    amountDisplay.addEventListener('blur', function (e) {
        const value = parseFloat(amountHidden.value);

        if (isNaN(value) || value <= 0) {
            e.target.classList.add('border-red-500');
            showFieldError('amount_display', 'Digite um valor válido maior que zero');
        } else {
            e.target.classList.remove('border-red-500');
            hideFieldError('amount_display');
        }
    });
}

/**
 * Máscara para CPF (xxx.xxx.xxx-xx)
 */
function initCpfMask() {
    const cpfInput = document.getElementById('payer_document');

    if (!cpfInput) return;

    cpfInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');

        // Limita a 11 dígitos
        if (value.length > 11) {
            value = value.substr(0, 11);
        }

        // Aplica a máscara
        if (value.length > 9) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        } else if (value.length > 6) {
            value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
        } else if (value.length > 3) {
            value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
        }

        e.target.value = value;
    });

    // Validação do CPF ao sair do campo
    cpfInput.addEventListener('blur', function (e) {
        const cpf = e.target.value.replace(/\D/g, '');

        if (cpf && cpf.length > 0 && cpf.length !== 11) {
            e.target.classList.add('border-red-500');
            showFieldError('payer_document', 'CPF deve ter 11 dígitos');
        } else if (cpf && !isValidCPF(cpf)) {
            e.target.classList.add('border-red-500');
            showFieldError('payer_document', 'CPF inválido');
        } else {
            e.target.classList.remove('border-red-500');
            hideFieldError('payer_document');
        }
    });
}

/**
 * Valida CPF
 */
function isValidCPF(cpf) {
    if (cpf.length !== 11) return false;

    // Elimina CPFs conhecidos como inválidos
    if (/^(\d)\1{10}$/.test(cpf)) return false;

    // Valida 1º dígito verificador
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let checkDigit = 11 - (sum % 11);
    if (checkDigit === 10 || checkDigit === 11) checkDigit = 0;
    if (checkDigit !== parseInt(cpf.charAt(9))) return false;

    // Valida 2º dígito verificador
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    checkDigit = 11 - (sum % 11);
    if (checkDigit === 10 || checkDigit === 11) checkDigit = 0;
    if (checkDigit !== parseInt(cpf.charAt(10))) return false;

    return true;
}

/**
 * Preparação do formulário antes do envio
 */
function initFormSubmit() {
    const form = document.querySelector('form');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        // Remove a máscara do CPF antes de enviar (envia apenas números)
        const cpf = document.getElementById('payer_document');
        if (cpf && cpf.value) {
            cpf.value = cpf.value.replace(/\D/g, '');
        }

        // Validação final do valor
        const amount = document.getElementById('amount');
        const value = parseFloat(amount.value);

        if (isNaN(value) || value <= 0) {
            e.preventDefault();
            showFormError('Por favor, digite um valor válido maior que zero.');
            return false;
        }

        // Adiciona loading no botão
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
        }
    });
}

/**
 * Mostra erro em um campo específico
 */
function showFieldError(fieldId, message) {
    hideFieldError(fieldId);

    const field = document.getElementById(fieldId);
    if (!field) return;

    const errorDiv = document.createElement('p');
    errorDiv.className = 'mt-1 text-sm text-red-600 field-error';
    errorDiv.textContent = message;
    errorDiv.id = `${fieldId}_error`;

    field.parentNode.appendChild(errorDiv);
}

/**
 * Remove erro de um campo
 */
function hideFieldError(fieldId) {
    const errorElement = document.getElementById(`${fieldId}_error`);
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Mostra erro geral do formulário
 */
function showFormError(message) {
    const existingError = document.querySelector('.form-error');
    if (existingError) existingError.remove();

    const errorDiv = document.createElement('div');
    errorDiv.className = 'mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded form-error';
    errorDiv.innerHTML = `<p class="text-red-700">${message}</p>`;

    const form = document.querySelector('form');
    if (form) {
        form.parentNode.insertBefore(errorDiv, form);
    }

    // Remove após 5 segundos
    setTimeout(() => errorDiv.remove(), 5000);
}