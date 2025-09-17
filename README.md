# Mautic Custom Webhook Plugin

Um plugin avançado para Mautic que permite enviar webhooks personalizados com estrutura JSON flexível e interpolação completa de dados do contato.

## Características Principais

### 1. Campo de Dados Flexível
- Estrutura JSON completamente personalizável
- Suporte a objetos aninhados e arrays
- Interpolação automática de tokens do contato
- Validação de JSON em tempo real

### 2. Interpolação de Dados
O plugin suporta todos os tokens padrão do Mautic:
- `{contactfield=firstname}` - Nome do contato
- `{contactfield=email}` - Email do contato
- `{contactfield=company}` - Empresa do contato
- `{date_now}` - Data/hora atual
- `{timestamp}` - Timestamp Unix atual
- E todos os outros campos personalizados do contato

### 3. Exemplo de Payload JSON

```json
{
  "name": "{contactfield=firstname} {contactfield=lastname}",
  "email": "{contactfield=email}",
  "variables": [
    {
      "key": "document",
      "value": "{contactfield=document}"
    },
    {
      "key": "phone",
      "value": "{contactfield=phone}"
    }
  ],
  "metadata": {
    "campaign_id": "{campaign_id}",
    "sent_at": "{date_now}",
    "timestamp": "{timestamp}"
  },
  "attachments": [
    {
      "fileUrl": "https://example.com/files/{contactfield=document}.pdf",
      "fileName": "document_{contactfield=firstname}.pdf",
      "fileType": "PDF"
    }
  ]
}
```

## Configuração

### 1. URL do Webhook
- Suporte a tokens na URL: `https://api.example.com/webhook/{contactfield=id}`
- Validação de URL
- Suporte a HTTPS e HTTP

### 2. Métodos HTTP Suportados
- GET
- POST
- PUT
- PATCH
- DELETE

### 3. Cabeçalhos Personalizados
Formato: um cabeçalho por linha
```
Authorization: Bearer {contactfield=api_token}
X-Custom-Header: {contactfield=custom_value}
Content-Type: application/json
```

### 4. Timeout
- Configurável de 1 a 300 segundos
- Padrão: 30 segundos

## Recursos Técnicos

### Arquitetura Desacoplada
- Implementação baseada em eventos
- Fácil extensibilidade
- Compatibilidade com futuras versões do Mautic

### Validação Robusta
- Validação de JSON em tempo real
- Verificação de URLs
- Tratamento de erros HTTP
- Logs detalhados

### Performance
- Cache de valores do contato
- Processamento otimizado de tokens
- Requisições HTTP assíncronas quando possível

## Instalação

1. Copie o plugin para `plugins/MauticCustomWebhookBundle/`
2. Limpe o cache do Mautic: `php bin/console cache:clear`
3. Instale/atualize o plugin no painel administrativo

## Uso em Campanhas

1. Crie uma nova campanha ou edite uma existente
2. Adicione uma nova ação
3. Selecione "Enviar Webhook Personalizado"
4. Configure:
   - URL do webhook
   - Método HTTP
   - Payload JSON personalizado
   - Cabeçalhos (opcional)
   - Timeout

## Eventos Personalizados

O plugin dispara eventos que podem ser interceptados:

- `mautic.custom_webhook.on_request` - Antes de enviar a requisição
- `mautic.custom_webhook.on_campaign_trigger_action` - Quando a ação é executada

## Logs e Debugging

Todos os webhooks são logados com:
- ID do contato
- URL de destino
- Método HTTP
- Status da resposta
- Erros (se houver)

Verifique os logs em `var/logs/mautic_prod.log` ou `var/logs/mautic_dev.log`

## Compatibilidade

- Mautic 4.x+
- PHP 8.0+
- Symfony 5.4+

## Suporte

Para suporte e relatórios de bugs, consulte a documentação do Mautic ou entre em contato com o desenvolvedor.