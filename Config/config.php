<?php

return [
    'name'        => 'Custom Webhook',
    'description' => 'Plugin para envio de webhooks personalizados com estrutura JSON flexível em campanhas',
    'version'     => '1.0.0',
    'author'      => 'Mautic Community',

    'routes' => [],

    'menu' => [],

    'services' => [
        'others' => [
            'mautic.custom_webhook.campaign.subscriber' => [
                'class'     => \MauticPlugin\MauticCustomWebhookBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.custom_webhook.helper.campaign',
                ],
                'tags' => [
                    'kernel.event_subscriber',
                ],
            ],
            'mautic.custom_webhook.helper.campaign' => [
                'class'     => \MauticPlugin\MauticCustomWebhookBundle\Helper\CampaignHelper::class,
                'arguments' => [
                    'mautic.http.client',
                    'mautic.lead.model.company',
                    'event_dispatcher',
                    'monolog.logger.mautic',
                ],
            ],

        ],
    ],

    'parameters' => [
        'custom_webhook_timeout' => 30,
    ],
];