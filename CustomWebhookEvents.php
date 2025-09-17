<?php

namespace MauticPlugin\MauticCustomWebhookBundle;

/**
 * Class CustomWebhookEvents
 * Define os eventos personalizados para o plugin de webhook
 */
final class CustomWebhookEvents
{
    /**
     * O evento mautic.custom_webhook.on_campaign_trigger_action é disparado quando uma ação de webhook personalizado é executada em uma campanha.
     *
     * O listener recebe um Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.custom_webhook.on_campaign_trigger_action';

    /**
     * O evento mautic.custom_webhook.on_request é disparado antes de fazer a requisição HTTP.
     *
     * O listener recebe um MauticPlugin\MauticCustomWebhookBundle\Event\CustomWebhookRequestEvent
     *
     * @var string
     */
    public const ON_REQUEST = 'mautic.custom_webhook.on_request';
}