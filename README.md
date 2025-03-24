# ParkFlow - Sistema de Gestão de Estacionamentos

O ParkFlow é uma solução completa para gestão de estacionamentos, permitindo o controle de vagas, mensalistas, pagamentos e muito mais.

## Índice

- [Visão Geral](#visão-geral)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Funcionalidades](#funcionalidades)
  - [Sistema de Estacionamento](#sistema-de-estacionamento)
  - [Sistema de Mensalistas](#sistema-de-mensalistas)
  - [Sistema de Pagamentos](#sistema-de-pagamentos)
  - [Relatórios](#relatórios)
- [API](#api)
- [Tecnologias](#tecnologias)
- [Contribuindo](#contribuindo)
- [Licença](#licença)

## Visão Geral

O ParkFlow é uma aplicação desenvolvida com Laravel e React para gerenciar estacionamentos de maneira eficiente. A aplicação oferece recursos como controle de entrada e saída de veículos, gestão de mensalistas, histórico de pagamentos e relatórios.

## Requisitos

- PHP 8.2 ou superior
- Composer
- Node.js 16 ou superior
- MySQL 8.0 ou superior (ou SQLite para desenvolvimento)

## Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/parkflow.git
   cd parkflow
   ```

2. Instale as dependências do PHP:
   ```bash
   composer install
   ```

3. Instale as dependências do JavaScript:
   ```bash
   npm install
   ```

4. Copie o arquivo de ambiente:
   ```bash
   cp .env.example .env
   ```

5. Gere a chave da aplicação:
   ```bash
   php artisan key:generate
   ```

6. Configure o banco de dados no arquivo `.env`

7. Execute as migrações:
   ```bash
   php artisan migrate
   ```

8. Compile os assets:
   ```bash
   npm run dev
   ```

9. Inicie o servidor de desenvolvimento:
   ```bash
   php artisan serve
   ```

## Configuração

### Configuração de Email

Edite o arquivo `.env` para configurar o serviço de email:

```
MAIL_MAILER=smtp
MAIL_HOST=seu-servidor-smtp
MAIL_PORT=587
MAIL_USERNAME=seu-usuario
MAIL_PASSWORD=sua-senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=email@seudominio.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Configuração de Pagamento

O ParkFlow utiliza o Laravel Cashier para processar pagamentos. Configure as credenciais do Stripe no arquivo `.env`:

```
STRIPE_KEY=sua-chave-publica
STRIPE_SECRET=sua-chave-secreta
STRIPE_WEBHOOK_SECRET=sua-chave-webhook
```

## Funcionalidades

### Sistema de Estacionamento

O ParkFlow oferece um sistema completo para gestão de estacionamentos:

- Múltiplos estacionamentos por empresa
- Configuração flexível de taxas por hora:
  - Primeira hora: Valor diferenciado
  - Horas adicionais: Valor diferenciado
  - Diária: Valor máximo para período de 24 horas
- Controle de horário de funcionamento
- Gestão de vagas (total, ocupadas, disponíveis)
- Controle de entrada e saída de veículos
- Cálculo automático de tarifas

### Sistema de Mensalistas

O ParkFlow suporta a gestão completa de mensalistas com as seguintes funcionalidades:

- Cadastro de mensalistas com informações de contato e veículo
- Controle de vagas reservadas para mensalistas
- Gestão de contratos e pagamentos
- Definição de valor mensal personalizado por cliente
- Histórico de pagamentos com status (pago, pendente, atrasado)
- Vagas não utilizadas por mensalistas são automaticamente liberadas para o público

### Sistema de Pagamentos

- Suporte a múltiplos métodos de pagamento
- Geração de recibos e comprovantes
- Histórico completo de transações
- Integração com Stripe para pagamentos online
- Controle de pagamentos pendentes e atrasados

### Relatórios

- Relatórios de ocupação
- Relatórios financeiros
- Análise de fluxo por horário
- Relatórios de mensalistas
- Exportação em CSV, PDF e Excel

## API

O ParkFlow oferece uma API RESTful completa para integração com outros sistemas:

- Autenticação via Bearer Token
- Endpoints para todas as funcionalidades
- Documentação completa com Swagger

## Tecnologias

- **Backend**: Laravel 12
- **Frontend**: React, TypeScript, Inertia.js
- **Banco de Dados**: MySQL/SQLite
- **Processamento de Pagamentos**: Laravel Cashier (Stripe)
- **Autenticação**: Laravel Sanctum

## Contribuindo

1. Faça um fork do projeto
2. Crie sua branch de feature (`git checkout -b feature/nova-funcionalidade`)
3. Faça commit das suas alterações (`git commit -m 'Adiciona nova funcionalidade'`)
4. Envie para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes. 
