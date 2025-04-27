# Sistema de Geração de Contratos

Este sistema foi desenvolvido para automatizar a geração de contratos em PDF e gerenciar diversas operações comerciais de forma eficiente e personalizada.

## Requisitos

- PHP >= 7.4
- Composer
- Servidor Web (Apache recomendado)
- Extensões PHP necessárias:
  - php-mbstring
  - php-dom
  - php-gd
  - php-pdo
  - php-mysql

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/alvesdmt/waneesa.git
```

2. Instale as dependências via Composer:
```bash
composer install
```

3. Configure as permissões necessárias:
```bash
chmod 755 -R logs/
```

## Estrutura do Projeto

```
├── admin/          # Área administrativa
├── assets/         # Arquivos estáticos (CSS, JS, imagens)
├── config/         # Arquivos de configuração
├── includes/       # Arquivos de inclusão PHP
├── logs/           # Logs do sistema
├── vendor/         # Dependências do Composer
├── .htaccess      # Configurações do Apache
├── index.php      # Ponto de entrada da aplicação
└── composer.json   # Gerenciamento de dependências
```

## Funcionalidades

### Gestão de Contratos
- Geração automatizada de contratos em PDF
- Interface administrativa para gestão de modelos
- Personalização de templates

### Gestão de Produtos
- Cadastro e gerenciamento de produtos
- Controle de estoque
- Geração de etiquetas de produtos
- Upload de imagens dos produtos
- Geração automática de código de barras

### Gestão de Vendas
- Registro de vendas
- Controle de caixa
- Abertura e fechamento de caixa
- Registro de movimentações financeiras
- Relatórios de vendas

### Gestão de Carnês
- Cadastro de carnês
- Controle de parcelas
- Registro de pagamentos
- Acompanhamento de parcelas em atraso
- Geração de carnês em PDF

### Gestão de Funcionários
- Cadastro de funcionários
- Controle de permissões (Admin/Funcionário)
- Gestão de acessos
- Registro de atividades

### Segurança
- Sistema de login seguro
- Autenticação de usuários
- Proteção contra CSRF
- Validação de inputs
- Logs de atividades
- Controle de permissões por tipo de usuário

### Outras Funcionalidades
- Dashboard com indicadores principais
- Catálogo de produtos
- Sistema de busca avançada
- Integração com API de CEP
- Formatação automática de dados (CPF, telefone, etc.)
- Geração de relatórios financeiros

## Uso

1. Acesse o sistema através do navegador
2. Faça login com suas credenciais
3. Navegue pelo menu lateral para acessar as diferentes funcionalidades:
   - Dashboard: Visão geral do sistema
   - Caixa: Gestão de caixa
   - Produtos: Gestão de produtos
   - Vendas: Registro de vendas
   - Carnês: Gestão de carnês
   - Funcionários: Gestão de funcionários (apenas admin)
   - Compras: Gestão de compras (apenas admin)
   - Catálogo: Visualização de produtos

## Segurança

O sistema implementa várias medidas de segurança:
- Autenticação de usuários com senhas criptografadas
- Proteção contra CSRF
- Validação de inputs
- Logs de atividades
- Controle de permissões por tipo de usuário
- Proteção contra SQL Injection
- Validação de sessões
- Sanitização de dados

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.
