/**
 * Gerenciamento de status e funcionalidades da página de cobrança
 */

// Configuração
const CONFIG = {
    updateInterval: 5000, // 5 segundos
    reloadDelay: 2000,    // 2 segundos após pagamento confirmado
};

let updateIntervalId = null;

document.addEventListener('DOMContentLoaded', function () {
    initCopyButton();
    initStatusCheck();
    startAutoUpdate();
});

/**
 * Inicializa funcionalidade de copiar código PIX
 */
function initCopyButton() {
    const copyBtn = document.querySelector('[onclick="copyPixCode()"]');
    if (copyBtn) {
        copyBtn.removeAttribute('onclick');
        copyBtn.addEventListener('click', copyPixCode);
    }
}

/**
 * Copia o código PIX para a área de transferência
 */
function copyPixCode() {
    const pixCode = document.getElementById('pix-code');
    const feedback = document.getElementById('copy-feedback');

    if (!pixCode) return;

    pixCode.select();
    pixCode.setSelectionRange(0, 99999); // Para dispositivos móveis

    // Tenta usar a API moderna do Clipboard
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(pixCode.value)
            .then(() => {
                showCopyFeedback(feedback);
            })
            .catch(() => {
                // Fallback para método antigo
                fallbackCopyText(pixCode, feedback);
            });
    } else {
        // Fallback para navegadores antigos
        fallbackCopyText(pixCode, feedback);
    }
}

/**
 * Método fallback para copiar texto
 */
function fallbackCopyText(element, feedback) {
    try {
        document.execCommand('copy');
        showCopyFeedback(feedback);
    } catch (err) {
        console.error('Erro ao copiar:', err);
        alert('Não foi possível copiar automaticamente. Por favor, copie manualmente.');
    }
}

/**
 * Mostra feedback visual de cópia
 */
function showCopyFeedback(feedbackElement) {
    if (feedbackElement) {
        feedbackElement.classList.remove('hidden');
        setTimeout(() => {
            feedbackElement.classList.add('hidden');
        }, 2000);
    }
}

/**
 * Inicializa botão de verificar status manual
 */
function initStatusCheck() {
    const checkBtn = document.querySelector('[onclick="checkStatus()"]');
    if (checkBtn) {
        checkBtn.removeAttribute('onclick');
        checkBtn.addEventListener('click', () => checkStatus(true));
    }
}

/**
 * Verifica o status da cobrança
 */
async function checkStatus(showLoading = false) {
    const txid = getTxid();
    if (!txid) return;

    const checkBtn = document.querySelector('button.bg-blue-600');

    if (showLoading && checkBtn) {
        checkBtn.disabled = true;
        checkBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        `;
    }

    try {
        const response = await fetch(`/charges/${txid}/status`);
        const data = await response.json();

        if (data.success) {
            updateStatus(data.status, data.data);
        }
    } catch (error) {
        console.error('Erro ao verificar status:', error);
    } finally {
        if (showLoading && checkBtn) {
            checkBtn.disabled = false;
            checkBtn.innerHTML = 'Verificar Pagamento';
        }
    }
}

/**
 * Atualiza o badge de status
 */
function updateStatus(status, chargeData) {
    const statusBadge = document.getElementById('status-badge');
    const statusText = document.getElementById('status-text');

    if (!statusBadge || !statusText) return;

    statusText.textContent = status;

    // Define a cor do badge baseado no status
    const statusClasses = getStatusClasses(status);
    statusBadge.className = statusClasses;

    // Ações específicas por status
    handleStatusChange(status, chargeData);
}

/**
 * Retorna as classes CSS baseadas no status
 */
function getStatusClasses(status) {
    const baseClasses = 'px-4 py-2 rounded-full text-sm font-semibold text-white';

    const statusColors = {
        'CONCLUIDA': 'bg-green-500',
        'ATIVA': 'bg-yellow-400',
        'REMOVIDA_PELO_USUARIO_RECEBEDOR': 'bg-red-500',
        'REMOVIDA_PELO_PSP': 'bg-red-500',
    };

    const color = statusColors[status] || 'bg-gray-500';
    return `${baseClasses} ${color}`;
}

/**
 * Lida com mudanças de status
 */
function handleStatusChange(status, chargeData) {
    if (status === 'CONCLUIDA') {
        stopAutoUpdate();
        showPaymentSuccess();

        // Recarrega a página após delay para mostrar dados do pagamento
        setTimeout(() => {
            location.reload();
        }, CONFIG.reloadDelay);
    } else if (status === 'REMOVIDA_PELO_USUARIO_RECEBEDOR' || status === 'REMOVIDA_PELO_PSP') {
        stopAutoUpdate();
        showPaymentCancelled();
    }
}

/**
 * Mostra notificação de pagamento confirmado
 */
function showPaymentSuccess() {
    if (document.getElementById('success-message')) return;

    const successDiv = document.createElement('div');
    successDiv.id = 'success-message';
    successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-bounce';
    successDiv.innerHTML = `
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="font-semibold">Pagamento confirmado! 🎉</span>
        </div>
    `;

    document.body.appendChild(successDiv);

    // Remove após 5 segundos
    setTimeout(() => successDiv.remove(), 5000);
}

/**
 * Mostra notificação de pagamento cancelado
 */
function showPaymentCancelled() {
    if (document.getElementById('cancelled-message')) return;

    const cancelledDiv = document.createElement('div');
    cancelledDiv.id = 'cancelled-message';
    cancelledDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50';
    cancelledDiv.innerHTML = `
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span class="font-semibold">Cobrança cancelada</span>
        </div>
    `;

    document.body.appendChild(cancelledDiv);
}

/**
 * Inicia atualização automática do status
 */
function startAutoUpdate() {
    const currentStatus = getCurrentStatus();

    // Só atualiza automaticamente se não estiver concluída
    if (currentStatus !== 'CONCLUIDA') {
        updateIntervalId = setInterval(() => {
            checkStatus(false);
        }, CONFIG.updateInterval);
    }
}

/**
 * Para a atualização automática
 */
function stopAutoUpdate() {
    if (updateIntervalId) {
        clearInterval(updateIntervalId);
        updateIntervalId = null;
    }
}

/**
 * Obtém o TXID da página
 */
function getTxid() {
    // Tenta pegar do elemento de script inline primeiro
    const scriptElements = document.querySelectorAll('script');
    for (let script of scriptElements) {
        const match = script.textContent.match(/const txid = ['"](.+?)['"]/);
        if (match) return match[1];
    }

    // Alternativa: pegar da URL
    const pathParts = window.location.pathname.split('/');
    return pathParts[pathParts.length - 1];
}

/**
 * Obtém o status atual da página
 */
function getCurrentStatus() {
    const statusText = document.getElementById('status-text');
    return statusText ? statusText.textContent.trim() : 'ATIVA';
}

/**
 * Para a atualização automática quando o usuário sai da página
 */
window.addEventListener('beforeunload', () => {
    stopAutoUpdate();
});

/**
 * Retoma atualização quando a página volta ao foco
 */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAutoUpdate();
    } else {
        const currentStatus = getCurrentStatus();
        if (currentStatus !== 'CONCLUIDA') {
            checkStatus(false);
            startAutoUpdate();
        }
    }
});