# De Casa em Casa - Sistema de Curadoria e Hospitalidade

Sistema de inscrição e curadoria para a turnê "De Casa em Casa". Os participantes se candidatam, a equipe avalia e, após aprovação e confirmação de pagamento, recebem o endereço do encontro.

## Fluxo do Sistema

1. **Inscrição** - O participante preenche o formulário público (status: `pendente`)
2. **Curadoria** - A equipe avalia e aprova ou move para fila de espera
3. **Pagamento** - Se aprovado, o participante recebe link para enviar comprovante
4. **Confirmação** - Admin confirma o pagamento (status: `confirmado`)
5. **Entrega** - Somente confirmados têm acesso ao endereço e horário

## Requisitos

- Docker e Docker Compose
- Git

## Instalação

```bash
# Clone o repositório
git clone <seu-repositorio>
cd decasaemcasa

# Configure o ambiente
cp .env.example .env

# Inicie os containers
docker compose up -d --build

# Instale as dependências
docker compose exec app composer install
docker compose exec app npm install

# Gere a chave da aplicação
docker compose exec app php artisan key:generate

# Execute as migrations
docker compose exec app php artisan migrate --seed

# Compile os assets
docker compose exec app npm run build
```

## Acesse a aplicação

- **Aplicação**: http://localhost:8585
- **Mailhog (Emails)**: http://localhost:8025
- **Admin**: http://localhost:8585/admin
  - Email: admin@decasaemcasa.com.br
  - Senha: admin123 (altere no .env)

## Estrutura do Projeto

```
decasaemcasa/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          # Painel administrativo
│   │   ├── Client/         # (legado)
│   │   └── InscriptionController.php  # Formulário público
│   ├── Models/
│   │   ├── Inscription.php # Inscrições de participantes
│   │   ├── Event.php       # Encontros (cidades/datas)
│   │   └── ...
│   └── Services/
├── database/migrations/
├── resources/views/
│   ├── inscriptions/       # Formulário público e status
│   ├── admin/inscriptions/ # Painel de curadoria
│   └── layouts/
├── routes/
├── docker/
└── tests/
```

## Áreas do Sistema

### Formulário Público (sem login)
- Escolher cidade/data do encontro
- Preencher dados pessoais e motivação
- Aceitar termos de responsabilidade
- Acompanhar status via link único

### Painel Administrativo
- Gerenciar encontros (cidades/datas)
- Curadoria de inscrições (ler motivação, aprovar/fila de espera)
- Confirmar pagamentos (visualizar comprovantes)

## Licença

Este projeto é de código aberto e está disponível sob a licença MIT.
