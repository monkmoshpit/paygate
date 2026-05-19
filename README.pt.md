# Paygate

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square\&logo=php\&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square\&logo=laravel\&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)
![Status](https://img.shields.io/badge/status-study%20project-blue?style=flat-square)

> Um gateway de pagamentos API-first projetado para simular fluxos reais com idempotência, orquestração via saga e processamento tolerante a falhas.

---

## 📋 Visão Geral

**Paygate** é um projeto focado em backend que modela um sistema de pagamentos sob condições realistas.

O fluxo é baseado em **operações em múltiplas etapas**, onde cada fase é executada de forma independente e acompanhada ao longo de seu ciclo de vida. O sistema lida com falhas parciais, execução assíncrona e consistência de estado durante todo o processamento.

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/monkmoshpit/paygate)

---

## 🚀 Principais Features

* **Requisições Idempotentes**
  Requisições duplicadas retornam o mesmo resultado sem reprocessamento.

* **Fluxo baseado em Saga**
  Execução em múltiplas etapas com orquestração explícita e caminhos de recuperação.

* **Transações Compensatórias**
  Reversão automática de efeitos externos em caso de falha durante o processo.

* **Processamento Assíncrono**
  Execução via filas com workers em background.

* **Logging Estruturado**
  Rastreabilidade clara de todas as etapas do processamento.

* **Simulação de Falhas**
  Gateway e ledger com comportamento não determinístico para cobrir cenários reais.

---

## 🛠️ Destaques Técnicos

* **Backend**: Laravel 11, PHP 8.2+
* **Arquitetura**: Service Layer + Job Pipeline
* **Filas**: Redis
* **Banco de Dados**: MySQL / SQLite
* **Testes**: PHPUnit + Pest

---

## ⚙️ Fluxo de Processamento

```
POST /api/payments
        │
        ▼
Validação da Requisição
        │
        ▼
Verificação de Idempotência
        │
        ▼
Criação do Pagamento (pending)
        │
        ▼
Dispatch de Job Assíncrono
        │
        ▼
Processamento (Saga)
   ├─ Cobrança no Gateway
   ├─ Registro no Ledger
   ├─ Finalização (success)
   │
   └─ Em caso de falha:
        ├─ Compensação (refund)
        └─ Finalização (failed)
```

---

## 🔄 Ciclo de Vida do Pagamento

```
pending → processing → success
                    ↘ failed
```

| Status       | Descrição                                  |
| ------------ | ------------------------------------------ |
| `pending`    | Pagamento criado e enfileirado             |
| `processing` | Worker executando etapas                   |
| `success`    | Processamento concluído                    |
| `failed`     | Falha na execução (com ou sem compensação) |

---

## 🔁 Fluxo da Saga

O processamento é dividido em etapas independentes:

```
ProcessPaymentJob
   ↓
ChargeGatewayJob
   ↓
RegisterLedgerJob
```

Cada etapa atualiza o estado do pagamento conforme a execução.

Se ocorrer falha após a cobrança no gateway:

```
Charge Gateway ──► sucesso
Register Ledger ──► falha
        │
        ▼
Compensação:
  → Refund no Gateway
  → Pagamento marcado como failed
```

Se o gateway falhar, nenhuma compensação é executada.

---

## 📡 API

### Criar Pagamento

```
POST /api/payments
```

| Campo             | Tipo    | Obrigatório | Descrição                  |
| ----------------- | ------- | ----------- | -------------------------- |
| `user_id`         | integer | ✅           | Usuário do pagamento       |
| `amount`          | numeric | ✅           | Valor da cobrança (mín: 1) |
| `idempotency_key` | string  | ✅           | Chave única da requisição  |

**201 — Criado**

```json
{
  "id": 1,
  "user_id": 42,
  "amount": "150.00",
  "status": "pending",
  "idempotency_key": "order-abc-123"
}
```

**200 — Requisição repetida**

```json
{
  "id": 1,
  "user_id": 42,
  "amount": "150.00",
  "status": "success",
  "idempotency_key": "order-abc-123"
}
```

---

## ⚙️ Rodando Localmente

**Pré-requisitos:** PHP 8.2+, Composer, Redis

```bash
git clone https://github.com/monkmoshpit/paygate.git
cd paygate

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

php artisan serve
php artisan queue:work
```

---

## 🧪 Testes

```bash
php artisan test
```

| Tipo    | Cobertura                           |
| ------- | ----------------------------------- |
| Feature | API, idempotência, status           |
| Unit    | Fluxo de processamento, compensação |

---

## 👨‍💻 Foco Técnico

Este projeto demonstra:

* Processamento orientado a filas
* Controle de fluxo distribuído
* Design de APIs idempotentes
* Tratamento de falhas e recuperação

---

## 📄 Licença

MIT
