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
    // Toggle do sidebar
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
    });

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

// Controle da Sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    let isSidebarOpen = false;

    // Função para alternar a sidebar
    function toggleSidebar() {
        isSidebarOpen = !isSidebarOpen;
        sidebar.classList.toggle('active');
        content.classList.toggle('active');
        
        // Ajusta o conteúdo quando a sidebar está aberta
        if (isSidebarOpen) {
            content.style.marginLeft = '250px';
            content.style.width = 'calc(100% - 250px)';
        } else {
            content.style.marginLeft = '0';
            content.style.width = '100%';
        }
    }

    // Evento de clique no botão
    sidebarCollapse.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });

    // Fechar sidebar ao clicar fora em telas menores
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && 
                !sidebarCollapse.contains(event.target) && 
                isSidebarOpen) {
                toggleSidebar();
            }
        }
    });

    // Ajustar layout ao redimensionar a janela
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            content.classList.remove('active');
            content.style.marginLeft = '250px';
            content.style.width = 'calc(100% - 250px)';
            isSidebarOpen = false;
        }
    });

    // Marcar item ativo na sidebar
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('#sidebar ul li a');
    
    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath.split('/').pop()) {
            link.parentElement.classList.add('active');
        }
    });
});

// Controle da Navbar
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;

    // Efeito de scroll na navbar
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });

    // Animar dropdowns
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('show.bs.dropdown', function() {
            this.querySelector('.dropdown-menu').classList.add('show');
        });
        
        dropdown.addEventListener('hide.bs.dropdown', function() {
            this.querySelector('.dropdown-menu').classList.remove('show');
        });
    });
}); 