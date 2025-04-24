# Sistema de Geração de Contratos

Este sistema foi desenvolvido para automatizar a geração de contratos em PDF de forma eficiente e personalizada.

## Requisitos

- PHP >= 7.4
- Composer
- Servidor Web (Apache recomendado)
- Extensões PHP necessárias:
  - php-mbstring
  - php-dom
  - php-gd

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/waneesa.git
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

- Geração automatizada de contratos em PDF
- Interface administrativa para gestão de modelos
- Sistema de login seguro
- Logs de atividades
- Personalização de templates

## Uso

1. Acesse o sistema através do navegador
2. Faça login com suas credenciais
3. Navegue até a seção de contratos
4. Selecione ou crie um novo modelo
5. Preencha os dados necessários
6. Gere o PDF

## Segurança

O sistema implementa várias medidas de segurança:
- Autenticação de usuários
- Proteção contra CSRF
- Validação de inputs
- Logs de atividades

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes. # waneesa
