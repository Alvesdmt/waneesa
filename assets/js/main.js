// Configurações globais do AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Função para mostrar mensagens de alerta
function showAlert(message, type = 'success') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('#alertContainer').html(alertHtml);
    
    // Auto-fechar após 5 segundos
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}

// Função para confirmar ações
function confirmarAcao(mensagem = 'Tem certeza que deseja realizar esta ação?') {
    return new Promise((resolve) => {
        if (confirm(mensagem)) {
            resolve(true);
        } else {
            resolve(false);
        }
    });
}

// Formatação de moeda
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// Formatação de data
function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR');
}

// Inicializações globais
$(document).ready(function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Máscaras de input
    if (typeof IMask !== 'undefined') {
        // Máscara para moeda
        document.querySelectorAll('.mask-money').forEach(element => {
            IMask(element, {
                mask: Number,
                scale: 2,
                thousandsSeparator: '.',
                padFractionalZeros: true,
                normalizeZeros: true,
                radix: ','
            });
        });

        // Máscara para CPF
        document.querySelectorAll('.mask-cpf').forEach(element => {
            IMask(element, {
                mask: '000.000.000-00'
            });
        });

        // Máscara para telefone
        document.querySelectorAll('.mask-phone').forEach(element => {
            IMask(element, {
                mask: '(00) 00000-0000'
            });
        });
    }

    // DataTables configuração padrão
    if ($.fn.DataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
            },
            pageLength: 25,
            responsive: true
        });
    }
});

// Função para carregar conteúdo via AJAX
function carregarConteudo(url, elemento) {
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function() {
            $(elemento).html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>');
        },
        success: function(response) {
            $(elemento).html(response);
        },
        error: function() {
            showAlert('Erro ao carregar o conteúdo', 'danger');
        }
    });
}

// Função para submeter formulários via AJAX
function submitForm(form, callback) {
    $(form).submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (callback) callback(response);
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert(xhr.responseJSON.message, 'danger');
                } else {
                    showAlert('Erro ao processar requisição', 'danger');
                }
            }
        });
    });
}

// Função para deletar registro
async function deletarRegistro(url, id) {
    if (await confirmarAcao('Tem certeza que deseja excluir este registro?')) {
        $.ajax({
            url: `${url}/${id}`,
            type: 'DELETE',
            success: function(response) {
                showAlert('Registro excluído com sucesso');
                if (typeof atualizarTabela === 'function') {
                    atualizarTabela();
                }
            },
            error: function() {
                showAlert('Erro ao excluir registro', 'danger');
            }
        });
    }
} 