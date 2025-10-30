# ARXVIEW Custom Reporting

O ARXVIEW Custom Reporting é uma aplicação web interna desenvolvida para gerar relatórios personalizados a partir da plataforma ARXVIEW, consolidando informações de storages (IBM, NetApp, entre outros) numa interface centralizada. A aplicação permite visualizar, filtrar, exportar e agendar o envio automático de relatórios por email, adaptando-se às necessidades específicas de cada cliente ou ambiente

A aplicação fornece:

- Visualização unificada de relatórios de capacity, volumes e firmware.
- Exportação dos dados em CSV.
- Envio dos relatórios por email.
- Agendamento de envio automático mensal para clientes.

---

## 📁 Estrutura dos Ficheiros

```
├── index.php                   # Página inicial do projeto
├── api_client.php              # Cliente de API (login, chamadas, logout)
├── capacity-report.php         # Relatório de capacidade por storage e pool
├── volume-report.php           # Relatório de volumes com WWN, pool e capacidade
├── disk-firmware-report.php    # Relatório de firmware de discos (modelo, fabricante, etc.)
├── schedule.php                # Gestão e agendamento dos envios mensais por cliente
├── script.php                  # Script executado via cron para enviar relatórios agendados
├── clients.csv                 # Lista de clientes configurados para envio automático
├── config.php                  # Configurações de conexão e email
├── login.php / logout.php      # Autenticação baseada em sessão
├── validate_login.php          # Validação do login com API
└── theme.php / menu.php        # Layout e navegação
```

---

## 📊 Relatórios Disponíveis

### 1. Capacity Report (`capacity-report.php`)
Relatório de capacidade por equipamento e grupo de discos.

- **Campos incluídos**:
  - Storage System
  - Pool
  - Capacidade Total (GB)
  - Capacidade Provisionada (GB)
  - Capacidade Livre (GB)
  - Tipo de Equipamento

- **Equipamentos suportados**:
  - IBM V7000 / SVC / FlashSystem / XIV / DS8000
  - NetApp

---

### 2. Volume Report (`volume-report.php`)
Relatório de volumes lógicos presentes nos storages.

- **Campos incluídos**:
  - Storage System
  - Nome do Volume
  - Pool
  - Capacidade
  - WWN

- **Equipamentos suportados**:
  - IBM V7000 / SVC / FlashSystem / XIV / DS8000
  - NetApp

---

### 3. Disk Firmware Report (`disk-firmware-report.php`)
Relatório técnico dos discos físicos, incluindo modelo e firmware.

- **Campos incluídos**:
  - Storage
  - Modelo
  - FRU / Seria Number
  - Firmware
  - Capacidade
  - Fabricante
  - Tipo de Equipamento

- **Equipamentos suportados**:
  - IBM V7000 / SVC / FlashSystem / DS8000
  - NetApp

---

### 4. Firmware Report (firmware-report.php)
Relatório geral dos equipamentos cadastrados na infraestrutura, com informações técnicas relevantes como firmware, modelo e datacenter.

- **Campos incluídos:**:
  - Storage
  - Fabricante
  - Modelo
  - Firmware
  - Serial
  - Datacenter
  - Tipo de Equipamento

- **Equipamentos suportados:**:

  - IBM V7000 / SVC / FlashSystem / DS8000
  - NetApp

## 📧 Agendamento (`schedule.php`)

- Interface web para gerir agendamentos por cliente.
- Definir se o cliente recebe Capacity Report e/ou Volume Report.
- Definir nome do volume e emails de destino.
- Envio imediato para testes diretamente na interface.
- Emails são definidos em clients.csv, com múltiplos destinos separados por ;.

## ⏱ Execução Automática

- O envio automático dos relatórios é feito via cron job.
- O cron chama o ficheiro script.php, que lê os dados agendados no clients.csv e envia os relatórios conforme definido em schedule.php.
- A execução é feita tipicamente no dia 1 de cada mês, mas pode ser ajustada conforme necessidade.

---

## Exportações e Envio por Email

- Todos os relatórios permitem:
  - Exportação em formato CSV
  - Envio por email com HTML + anexo CSV
- Utiliza PHPMailer via SMTP (configurado em `config.php`)

---

## 🔐 Autenticação

A aplicação utiliza **sessão baseada em API do ARXVIEW**. O login é realizado via `validate_login.php`, que autentica e armazena a sessão numa variável de sessão.

---

## 🚀 Requisitos

- PHP 8+
- Extensões: `curl`, `mbstring`
- Composer (para instalar PHPMailer)
- Acesso ao endpoint da API ARXVIEW

---
## 📌 Observações

- O projeto é **interno** e não deve ser exposto diretamente à internet.
- Os relatórios utilizam filtros avançados e paginação para suportar grandes volumes de dados.

---
