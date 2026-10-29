# Bancada

Sistema de ordens de serviço (OS) para assistência técnica. Projeto de estudo
em Laravel, construído em fases, com foco em aprender multi-tenancy, fluxos de
status, filas e boas práticas de testes.

> Nome provisório. O nome vive em `APP_NAME` no `.env`.

## Stack

- PHP 8.5 · Laravel · Blade · Tailwind (via Vite)
- SQLite em desenvolvimento

## Rodando localmente

    composer install
    npm install
    cp .env.example .env
    php artisan key:generate
    php artisan migrate
    npm run dev
    php artisan serve   # ou use o Herd

## Testes

    php artisan test

## Convenções

- Tabelas, colunas e código em **inglês**; textos da interface em **português**.
- Valores monetários sempre em **centavos (inteiro)**.
- Lógica de negócio em classes de serviço, não em controllers.
- Todo model de dados de loja usará isolamento por `tenant_id`.
- Um commit ao final de cada fase.

## Fases

- [x] Fase 0: Fundação
- [x] Fase 1: Autenticação e multi-tenancy
- [ ] Fase 2: Clientes e aparelhos
- [ ] Fase 3: Ordem de serviço
- [ ] Fase 4: Orçamento e aprovação
- [ ] Fase 5: Link público de acompanhamento
- [ ] Fase 6: Impressão e termos
- [ ] Fase 7: Painel