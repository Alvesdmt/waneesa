// Função para ver detalhes do carnê
function verDetalhes(carneId) {
    console.log('ID do carnê recebido:', carneId);
    document.getElementById('carne_id').value = carneId;
    
    fetch('carnes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ver_detalhes=1&compra_id=${carneId}`
    })
    .then(response => {
        console.log('Resposta do servidor:', response);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos:', data);
        if (data.success) {
            const carne = data.data;
            const modalElement = document.getElementById('modalDetalhes');
            const modal = new bootstrap.Modal(modalElement);
            
            // Adiciona evento para prevenir atualização ao fechar
            modalElement.addEventListener('hidden.bs.modal', function () {
                // Limpa o conteúdo do modal
                document.getElementById('detalhesCarne').innerHTML = '';
            });
            
            let html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Dados do Cliente</h5>
                        <p><strong>Nome:</strong> ${carne.cliente_nome || 'Não informado'}</p>
                        <p><strong>CPF:</strong> ${carne.cpf || 'Não informado'}</p>
                        <p><strong>Telefone:</strong> ${carne.telefone || 'Não informado'}</p>
                        <p><strong>Endereço:</strong> ${carne.endereco || 'Não informado'}</p>
                        <p><strong>Cidade/UF:</strong> ${carne.cidade || 'Não informado'}/${carne.estado || 'Não informado'}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Dados do Carnê</h5>
                        <p><strong>Valor Total:</strong> R$ ${formatarValor(carne.valor_total || 0)}</p>
                        <p><strong>Número de Parcelas:</strong> ${carne.num_parcelas || 0}</p>
                        <p><strong>Data Início:</strong> ${carne.data_inicio ? new Date(carne.data_inicio).toLocaleDateString('pt-BR') : 'Não informada'}</p>
                        <p><strong>Observações:</strong> ${carne.observacoes || '-'}</p>
                    </div>
                </div>
            `;

            if (carne.parcelas && carne.parcelas.length > 0) {
                html += `
                    <div class="mt-4">
                        <h5>Parcelas</h5>
                        ${gerarParcelas(carne)}
                    </div>
                `;
            } else {
                html += '<div class="alert alert-warning">Nenhuma parcela encontrada para este carnê.</div>';
            }
            
            document.getElementById('detalhesCarne').innerHTML = html;
            
            // Adiciona eventos aos botões de pagar após inserir o HTML
            document.querySelectorAll('.btn-pagar').forEach(btn => {
                btn.onclick = function() {
                    confirmarPagamentoParcela(this.dataset.parcelaId);
                };
            });
            
            modal.show();
        } else {
            alert(data.message || 'Erro ao carregar detalhes do carnê');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar detalhes do carnê');
    });
}

// Função para imprimir carnê
function imprimirCarne(carneId) {
    // Abrir uma nova janela para impressão
    const printWindow = window.open('', '_blank');
    
    // Carregar os dados do carnê
    fetch('carnes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ver_detalhes=1&compra_id=${carneId}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data) {
            const carne = data.data;
            
            // Criar conteúdo para impressão
            const content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Nota Promissória - ${carne.cliente_nome}</title>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
                    <style>
                        @page {
                            size: A4;
                            margin: 2cm;
                        }
                        body {
                            font-family: 'Times New Roman', Times, serif;
                            line-height: 1.6;
                            color: #000;
                        }
                        .container {
                            max-width: 800px;
                            margin: 0 auto;
                            padding: 20px;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 40px;
                        }
                        .title {
                            font-size: 24px;
                            font-weight: bold;
                            margin-bottom: 20px;
                        }
                        .content {
                            margin-bottom: 40px;
                        }
                        .text-justify {
                            text-align: justify;
                        }
                        .signature {
                            margin-top: 100px;
                            text-align: center;
                        }
                        .signature-line {
                            border-top: 1px solid #000;
                            width: 300px;
                            margin: 0 auto;
                            margin-top: 50px;
                        }
                        .parcelas {
                            margin-top: 30px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        th, td {
                            border: 1px solid #000;
                            padding: 8px;
                            text-align: left;
                        }
                        .no-print {
                            display: none;
                        }
                        .footer {
                            margin-top: 50px;
                            text-align: center;
                            font-size: 12px;
                        }
                        .action-buttons {
                            margin-top: 20px;
                            text-align: center;
                        }
                        .action-buttons button {
                            margin: 0 10px;
                            padding: 10px 20px;
                            font-size: 16px;
                            cursor: pointer;
                        }
                    </style>
                </head>
                <body>
                    <div class="container" id="content-to-print">
                        <div class="header">
                            <div class="title">NOTA PROMISSÓRIA</div>
                        </div>
                        
                        <div class="content">
                            <p class="text-justify">
                                Eu, <strong>${carne.cliente_nome}</strong>, portador do CPF nº <strong>${carne.cpf}</strong>, 
                                residente e domiciliado em <strong>${carne.endereco}</strong>, 
                                <strong>${carne.cidade} - ${carne.estado}</strong>, 
                                comprometo-me a pagar a quantia de <strong>R$ ${formatarValor(carne.valor_total)}</strong> 
                                (${numeroPorExtenso(carne.valor_total)}), em <strong>${carne.num_parcelas}</strong> parcelas mensais, 
                                conforme tabela abaixo:
                            </p>

                            <div class="parcelas">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Parcela</th>
                                            <th>Valor</th>
                                            <th>Vencimento</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${carne.parcelas ? carne.parcelas.map(parcela => `
                                            <tr>
                                                <td>${parcela.numero_parcela}ª</td>
                                                <td>R$ ${formatarValor(parcela.valor)}</td>
                                                <td>${new Date(parcela.data_vencimento).toLocaleDateString()}</td>
                                                <td>${parcela.status || 'Pendente'}</td>
                                            </tr>
                                        `).join('') : ''}
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-justify">
                                O pagamento deverá ser efetuado até o dia do vencimento de cada parcela, 
                                sob pena de incidência de multa e juros conforme legislação vigente.
                            </p>

                            <p class="text-justify">
                                Declaro estar ciente de que o não pagamento no prazo estipulado implicará 
                                na inclusão do meu nome nos órgãos de proteção ao crédito.
                            </p>
                        </div>

                        <div class="signature">
                            <p>${carne.cidade}, ${new Date().toLocaleDateString()}</p>
                            <div class="signature-line"></div>
                            <p>${carne.cliente_nome}</p>
                            <p>CPF: ${carne.cpf}</p>
                        </div>

                        <div class="footer">
                            <p>Documento gerado em: ${new Date().toLocaleString()}</p>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button onclick="window.print()">Imprimir</button>
                        <button onclick="gerarPDF()">Salvar PDF</button>
                    </div>

                    <script>
                        function gerarPDF() {
                            const element = document.getElementById('content-to-print');
                            const opt = {
                                margin: 1,
                                filename: 'nota_promissoria_${carne.cliente_nome.replace(/\s+/g, '_')}.pdf',
                                image: { type: 'jpeg', quality: 0.98 },
                                html2canvas: { scale: 2 },
                                jsPDF: { unit: 'cm', format: 'a4', orientation: 'portrait' }
                            };

                            html2pdf().set(opt).from(element).save();
                        }
                    </script>
                </body>
                </html>
            `;
            
            // Escrever conteúdo na nova janela
            printWindow.document.write(content);
            printWindow.document.close();
        } else {
            throw new Error(data.message || 'Erro ao buscar dados do carnê');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao gerar carnê para impressão: ' + error.message);
        printWindow.close();
    });
}

// Função para confirmar pagamento de parcela
function confirmarPagamentoParcela(parcelaId) {
    console.log('Abrindo modal de confirmação para parcela:', parcelaId);
    
    if (!parcelaId) {
        console.error('ID da parcela não fornecido');
        alert('Erro ao abrir modal de confirmação: ID da parcela não fornecido');
        return;
    }
    
    // Verifica se o elemento existe
    const inputParcelaId = document.getElementById('parcela_id');
    if (!inputParcelaId) {
        console.error('Elemento parcela_id não encontrado');
        alert('Erro ao abrir modal de confirmação');
        return;
    }
    
    // Define o valor do input
    inputParcelaId.value = parcelaId;
    console.log('Valor do input parcela_id definido como:', inputParcelaId.value);
    
    // Abre o modal
    const modalElement = document.getElementById('modalConfirmarPagamento');
    if (!modalElement) {
        console.error('Modal modalConfirmarPagamento não encontrado');
        alert('Erro ao abrir modal de confirmação');
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Adiciona evento para prevenir atualização ao fechar
    modalElement.addEventListener('hidden.bs.modal', function () {
        // Limpa o formulário
        const form = document.getElementById('formPagamento');
        if (form) {
            form.reset();
        }
    }, { once: true }); // Garante que o evento seja executado apenas uma vez
}

// Função para confirmar pagamento
function confirmarPagamento() {
    const inputParcelaId = document.getElementById('parcela_id');
    if (!inputParcelaId) {
        console.error('Elemento parcela_id não encontrado');
        alert('Erro ao processar pagamento: ID da parcela não encontrado');
        return;
    }
    
    const parcelaId = inputParcelaId.value;
    console.log('ID da parcela a ser paga:', parcelaId);
    
    if (!parcelaId) {
        alert('ID da parcela não encontrado');
        return;
    }
    
    const formData = new FormData();
    formData.append('pagar_parcela', '1');
    formData.append('parcela_id', parcelaId);
    
    console.log('Dados enviados:', {
        pagar_parcela: '1',
        parcela_id: parcelaId
    });
    
    fetch('carnes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Status da resposta:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Resposta do servidor:', data);
        if (data.success) {
            alert(data.message);
            // Fecha o modal de confirmação
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarPagamento'));
            if (modal) {
                modal.hide();
            }
            
            // Atualiza a tabela principal
            const termoBusca = document.getElementById('buscaCliente').value.trim();
            console.log('Atualizando tabela com termo de busca:', termoBusca);
            buscarCarnes(termoBusca);
            
            // Força uma atualização da tabela após um pequeno delay
            setTimeout(() => {
                console.log('Atualizando tabela novamente após delay');
                buscarCarnes(termoBusca);
            }, 500);
        } else {
            console.error('Erro do servidor:', data.message);
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        alert('Erro ao processar pagamento: ' + error.message);
    });
}

// Função auxiliar para gerar as parcelas
function gerarParcelas(data) {
    let html = '';
    if (data.parcelas && data.parcelas.length > 0) {
        data.parcelas.forEach(parcela => {
            console.log('Gerando parcela:', parcela);
            html += `
                <div class="row mb-2">
                    <div class="col-md-2">
                        <strong>Parcela ${parcela.numero_parcela}</strong>
                    </div>
                    <div class="col-md-3">
                        <strong>Valor:</strong> R$ ${formatarValor(parcela.valor)}
                    </div>
                    <div class="col-md-3">
                        <strong>Vencimento:</strong> ${new Date(parcela.data_vencimento).toLocaleDateString('pt-BR')}
                    </div>
                    <div class="col-md-4">
                        <strong>Status:</strong> ${parcela.status}
                        ${parcela.status === 'Pendente' ? 
                            `<button class="btn btn-sm btn-success btn-pagar" data-parcela-id="${parcela.id}" onclick="confirmarPagamentoParcela(${parcela.id})">
                                <i class="fas fa-check"></i> Pagar
                            </button>` : 
                            `<span class="badge bg-success">Pago</span>`
                        }
                        <a href="gerar_pdf_promissorias.php?carne_id=${data.id}&parcela_id=${parcela.id}" class="btn btn-sm btn-warning" target="_blank" title="Gerar Nota Promissória">
                            <i class="fas fa-file-signature"></i>
                        </a>
                    </div>
                </div>
            `;
        });
    }
    return html;
}

// Função para converter número em extenso
function numeroPorExtenso(valor) {
    // Garantir que o valor seja um número
    valor = parseFloat(valor) || 0;
    
    const unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    const dezenas = ['', 'dez', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    const centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
    
    let valorStr = valor.toFixed(2);
    let partes = valorStr.split('.');
    let reais = parseInt(partes[0]);
    let centavos = parseInt(partes[1]);
    
    let extenso = '';
    
    if (reais === 0) {
        extenso = 'zero';
    } else {
        if (reais >= 100) {
            extenso += centenas[Math.floor(reais / 100)] + ' ';
            reais = reais % 100;
        }
        
        if (reais >= 20) {
            extenso += dezenas[Math.floor(reais / 10)] + ' ';
            reais = reais % 10;
        }
        
        if (reais > 0) {
            extenso += unidades[reais] + ' ';
        }
    }
    
    extenso += 'reais';
    
    if (centavos > 0) {
        extenso += ' e ' + centavos + ' centavos';
    }
    
    return extenso.trim();
}

// Função para formatar valor monetário
function formatarValor(valor) {
    valor = parseFloat(valor) || 0;
    return valor.toFixed(2);
}

// Função para cadastrar cliente
function cadastrarCliente() {
    const form = document.getElementById('formCliente');
    const formData = new FormData(form);
    
    // Adiciona o campo cadastrar_cliente ao FormData
    formData.append('cadastrar_cliente', '1');
    
    fetch('carnes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Fecha o modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoCliente'));
            modal.hide();
            // Limpa o formulário
            form.reset();
            // Recarrega a página para atualizar a lista de clientes
            window.location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao cadastrar cliente: ' + error.message);
    });
}

// Função para inicializar todos os eventos
function inicializarEventos() {
    // Máscaras para os campos
    $('input[name="cpf"]').mask('000.000.000-00');
    $('input[name="telefone"]').mask('(00) 00000-0000');
    $('input[name="cep"]').mask('00000-000');
    
    // Define a data atual no campo de data
    document.querySelector('input[name="data_inicio"]').value = new Date().toISOString().split('T')[0];

    // Busca endereço pelo CEP
    document.querySelector('input[name="cep"]').addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.querySelector('input[name="endereco"]').value = data.logradouro;
                        document.querySelector('input[name="cidade"]').value = data.localidade;
                        document.querySelector('select[name="estado"]').value = data.uf;
                    }
                });
        }
    });

    // Reatribui eventos aos botões de ação
    document.querySelectorAll('.btn-ver-detalhes').forEach(btn => {
        btn.onclick = function() {
            verDetalhes(this.dataset.carneId);
        };
    });

    document.querySelectorAll('.btn-imprimir').forEach(btn => {
        btn.onclick = function() {
            imprimirCarne(this.dataset.carneId);
        };
    });

    document.querySelectorAll('.btn-pagar').forEach(btn => {
        btn.onclick = function() {
            confirmarPagamentoParcela(this.dataset.parcelaId);
        };
    });
}

// Inicializa os eventos quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', inicializarEventos);

// Inicializa os eventos após cada atualização da tabela
function atualizarTabela() {
    // ... código existente de atualização da tabela ...
    inicializarEventos(); // Reatribui os eventos após a atualização
}

// Função para buscar carnês
function buscarCarnes(termo) {
    fetch('carnes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `buscar_carnes=1&termo=${encodeURIComponent(termo)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.getElementById('tabelaCarnes');
            tbody.innerHTML = '';
            
            data.carnes.forEach(carne => {
                const status = parseInt(carne.atrasadas) > 0 ? 'Atrasado' : 'Em dia';
                const statusClass = parseInt(carne.atrasadas) > 0 ? 'danger' : 'success';
                const totalPendentes = parseInt(carne.total_pendentes) || 0;
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${carne.cliente_nome}</td>
                    <td>${carne.cpf}</td>
                    <td>R$ ${parseFloat(carne.valor_total).toFixed(2).replace('.', ',')}</td>
                    <td>${carne.num_parcelas}</td>
                    <td>${totalPendentes}</td>
                    <td>
                        <span class="badge bg-${statusClass}">${status}</span>
                    </td>
                    <td>${new Date(carne.data_inicio).toLocaleDateString('pt-BR')}</td>
                    <td>
                        <button class="btn btn-sm btn-info btn-ver-detalhes" data-carne-id="${carne.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success btn-imprimir" data-carne-id="${carne.id}">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Reatribui os eventos aos novos botões
            inicializarEventos();
        } else {
            alert('Erro ao buscar carnês');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao buscar carnês');
    });
}

// Adiciona evento de busca
document.getElementById('btnBuscar').addEventListener('click', function() {
    const termo = document.getElementById('buscaCliente').value.trim();
    buscarCarnes(termo);
});

// Adiciona evento de busca ao pressionar Enter
document.getElementById('buscaCliente').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const termo = this.value.trim();
        buscarCarnes(termo);
    }
});

// Adiciona evento para limpar a busca
document.getElementById('buscaCliente').addEventListener('input', function() {
    if (this.value.trim() === '') {
        buscarCarnes('');
    }
}); 