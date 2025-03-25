# ParkFlow - Sistema de Gestão de Estacionamentos

O ParkFlow é uma solução completa para gestão de estacionamentos, permitindo o controle de vagas, mensalistas, pagamentos e muito mais.

## Índice

- [Visão Geral](#visão-geral)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Funcionalidades](#funcionalidades)
  - [Sistema de Estacionamento](#sistema-de-estacionamento)
  - [Sistema de Gerenciamento de Vagas Individuais](#sistema-de-gerenciamento-de-vagas-individuais)
  - [Sistema de Cobrança](#sistema-de-cobrança)
  - [Sistema de Mensalistas](#sistema-de-mensalistas)
  - [Sistema de Valet](#sistema-de-valet)
  - [Sistema de Gerenciamento de Incidentes](#sistema-de-gerenciamento-de-incidentes)
  - [Sistema de Reservas Online](#sistema-de-reservas-online)
  - [Carregamento para Veículos Elétricos](#carregamento-para-veículos-elétricos)
  - [Portal para Empresas Parceiras](#portal-para-empresas-parceiras)
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
- Controle de horário de funcionamento
- Gestão de vagas (total, ocupadas, disponíveis)
- Controle de entrada e saída de veículos
- Cálculo automático de tarifas

### Sistema de Gerenciamento de Vagas Individuais

O ParkFlow possibilita o controle preciso de cada vaga individual do estacionamento:

- **Mapeamento Detalhado**:
  - Identificação única para cada vaga (alfanumérica, numérica ou alfabética)
  - Organização por andares, setores e zonas
  - Sinalização de vagas especiais (PCD, veículos elétricos, etc.)
  - Diferentes tamanhos de vagas (normal, grande, extra grande)

- **Rastreamento em Tempo Real**:
  - Visualização do status atual de cada vaga (disponível, ocupada, reservada, manutenção)
  - Registro do tempo de ocupação de cada vaga
  - Informações sobre o veículo que ocupa a vaga
  - Dashboard visual para monitoramento do estacionamento

- **Alocação Inteligente**:
  - Direcionamento automático para vagas adequadas ao tipo de veículo
  - Priorização baseada em preferências do cliente
  - Reserva antecipada de vagas específicas
  - Identificação da localização exata para facilitar o estacionamento

- **Gestão Operacional**:
  - Marcação de vagas para manutenção ou bloqueio
  - Histórico completo de utilização por vaga
  - Indicadores de desempenho por localização
  - Análise de padrões de uso para otimização do espaço

- **Benefícios para Clientes**:
  - Menor tempo gasto procurando vagas disponíveis
  - Direcionamento preciso para a vaga designada
  - Melhor experiência em estacionamentos grandes
  - Informações detalhadas sobre localização (andar, setor, vaga)

- **Aplicações Especiais**:
  - Integração com sistemas de estacionamento autônomo
  - Suporte a estacionamentos robotizados
  - Compatibilidade com sensores de ocupação
  - Visualização em mapa interativo ou grid

### Sistema de Cobrança

O ParkFlow possui um sistema de cobrança flexível e configurável:

- **Configuração de taxas específicas**:
  - Primeira hora: Valor diferenciado (ex: R$ 30,00)
  - Horas adicionais: Valor diferenciado por hora (ex: R$ 5,00)
  - Diária: Valor máximo para o período configurado (ex: R$ 50,00)
  - Período da diária: Configurável (8h, 12h, 24h, etc.)

- **Lógica de cobrança inteligente**:
  - Aplicação automática da tarifa mais vantajosa para o cliente
  - Quando o valor das horas atinge ou ultrapassa o valor da diária, é cobrado apenas o valor da diária
  - Para permanências que excedem o período da diária, aplica-se múltiplos da diária + horas adicionais
  
- **Exemplo de cobrança**:
  - Configuração: Primeira hora R$ 30,00, horas adicionais R$ 5,00, diária (24h) R$ 50,00
  - Cliente permanece 5 horas: R$ 30,00 + (4 × R$ 5,00) = R$ 50,00
  - Cliente permanece 6 horas: Cobrança limitada à diária = R$ 50,00
  - Cliente permanece 26 horas: Uma diária (R$ 50,00) + 2 horas (R$ 30,00 primeira hora + R$ 5,00 adicional) = R$ 85,00

### Sistema de Mensalistas

O ParkFlow suporta a gestão completa de mensalistas com as seguintes funcionalidades:

- Cadastro de mensalistas com informações de contato e veículo
- Controle de vagas reservadas para mensalistas
- Gestão de contratos e pagamentos
- Definição de valor mensal personalizado por cliente
- Histórico de pagamentos com status (pago, pendente, atrasado)
- Vagas não utilizadas por mensalistas são automaticamente liberadas para o público

### Sistema de Valet

O ParkFlow oferece um sistema completo de gerenciamento de valet:

- **Solicitação de veículos pelo cliente ou empresa**:
  - Clientes podem solicitar seus veículos através da plataforma
  - Empresas (ex: hospitais, restaurantes) podem solicitar veículos em nome dos clientes
  - Identificação por placa do veículo

- **Gestão de fila de solicitações**:
  - Fila organizada por ordem de chegada das solicitações
  - Painel para manobristas visualizarem solicitações pendentes
  - Sistema de atribuição de solicitações a manobristas específicos

- **Rastreamento de status**:
  - Solicitação pendente
  - Em processamento (manobrista buscando o veículo)
  - Concluída (veículo entregue)
  - Cancelada

- **Personalização para setores específicos**:
  - Hospitais: possibilidade de incluir número do quarto/setor
  - Empresas: referência interna como número de cliente
  - Campos personalizados para notas e instruções especiais

- **Gestão de manobristas**:
  - Cadastro completo de manobristas com registro de CNH
  - Controle de status (ativo, inativo, suspenso)
  - Histórico de entregas realizadas por manobrista
  - Gestão de escala e disponibilidade

- **Processo de entrega e recebimento**:
  - Confirmação digital de recebimento pelo cliente
  - Registro da condição do veículo na entrega
  - Sistema para reportar problemas ou danos
  - Upload de fotos como evidência
  - Assinatura digital do cliente
  - Registro completo para fins de auditoria e segurança

- **Notificações**:
  - Alertas para manobristas sobre novas solicitações
  - Notificações para clientes sobre o status de sua solicitação
  - Estimativa de tempo para entrega do veículo

### Sistema de Gerenciamento de Incidentes

O ParkFlow oferece um sistema avançado para registrar e gerenciar incidentes relacionados aos veículos:

- **Registro de incidentes em diferentes momentos**:
  - Pré-estacionamento: Danos já existentes identificados na entrada do veículo
  - Durante o estacionamento: Incidentes ocorridos enquanto o veículo estava sob custódia
  - Pós-estacionamento: Problemas identificados no momento da retirada do veículo

- **Documentação completa de incidentes**:
  - Descrição detalhada do problema
  - Upload de múltiplos tipos de mídia (fotos, vídeos, áudios)
  - Indicação da localização do dano no veículo
  - Classificação por nível de severidade

- **Acompanhamento e resolução**:
  - Ciclo de vida completo do incidente (aberto, em andamento, resolvido, fechado)
  - Notificação automática ao cliente
  - Registro de confirmação do cliente
  - Documentação das ações de resolução

- **Responsabilidade e segurança**:
  - Identificação do responsável pelo registro do incidente
  - Rastreabilidade completa de todas as ações
  - Proteção tanto para clientes quanto para o estacionamento
  - Redução de disputas relacionadas a danos pré-existentes

- **Integração com outros sistemas**:
  - Vinculação ao serviço de valet
  - Associação à sessão de estacionamento específica
  - Histórico completo disponível para consulta

### Sistema de Reservas Online

O ParkFlow oferece um sistema completo de reservas antecipadas de vagas:

- **Reserva Antecipada**:
  - Possibilidade de reservar vagas com dias ou horas de antecedência
  - Definição flexível de período de estacionamento
  - Seleção do tipo de vaga (normal, para deficientes, para idosos, etc.)
  - Visualização de disponibilidade em tempo real

- **Pagamento Antecipado**:
  - Opção de pré-pagamento com desconto
  - Suporte a diversos métodos de pagamento
  - Possibilidade de cancelamento com política flexível
  - Descontos promocionais através de códigos

- **Entrada Facilitada**:
  - Geração de QR Code único para cada reserva
  - Leitura automática do QR Code na entrada
  - Confirmação instantânea sem necessidade de tickets físicos
  - Redução de filas e espera na entrada

- **Sistema de Lembretes**:
  - Notificações automáticas de confirmação
  - Lembretes de reserva próxima do horário
  - Alertas em caso de congestionamento ou eventos
  - Opção de estender a reserva pelo aplicativo

- **Integração com Agenda**:
  - Sincronização com calendários digitais
  - Reservas recorrentes para usuários frequentes
  - Histórico completo de reservas anteriores
  - Sugestões inteligentes com base no histórico

### Carregamento para Veículos Elétricos

O ParkFlow integra um sistema completo para gestão de estações de carregamento para veículos elétricos:

- **Infraestrutura de Carregamento**:
  - Suporte a diversos tipos de conectores (Type1, Type2, CHAdeMO, CCS, etc.)
  - Monitoramento em tempo real do estado de carregamento
  - Visualização de disponibilidade das estações
  - Manutenção preventiva e corretiva das estações

- **Cobrança Personalizada**:
  - Taxas por kWh consumido
  - Taxas adicionais por tempo ocupado
  - Integração com o sistema de pagamento do estacionamento
  - Relatórios detalhados de consumo e faturamento

- **Experiência do Usuário**:
  - Reserva antecipada de estações de carregamento
  - Notificações quando o carregamento estiver completo
  - Visualização do progresso do carregamento pelo aplicativo
  - Histórico de sessões de carregamento

- **Gestão de Energia**:
  - Balanceamento de carga entre estações
  - Integração com sistemas de energia renovável
  - Priorização de carregamento em horários de baixa demanda
  - Estatísticas de consumo e economia de carbono

### Portal para Empresas Parceiras

O ParkFlow oferece um sistema dedicado para empresas parceiras gerenciarem convênios de estacionamento:

- **Validação de Tickets**:
  - Interface intuitiva para empresas validarem tickets de seus clientes
  - Múltiplos métodos de validação (portal web, aplicativo, API)
  - Leitura de QR Code para validação instantânea
  - Confirmação por email para o cliente

- **Gestão de Convênios**:
  - Dashboard personalizado para cada empresa parceira
  - Configuração de descontos e benefícios específicos
  - Definição de limites mensais de validações
  - Renovação automática de contratos

- **Relatórios Analíticos**:
  - Relatórios detalhados de uso por período
  - Análise de padrões de utilização por clientes
  - Exportação de dados em diversos formatos
  - Visualização gráfica de tendências

- **Faturamento Automático**:
  - Geração automática de faturas mensais
  - Detalhamento de cada validação na fatura
  - Integração com sistemas de pagamento
  - Histórico completo de faturas e pagamentos

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
