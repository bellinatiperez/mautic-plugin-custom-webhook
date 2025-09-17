<?php

namespace MauticPlugin\MauticCustomWebhookBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use MauticPlugin\MauticCustomWebhookBundle\Form\Type\CampaignEventCustomWebhookType;
use MauticPlugin\MauticCustomWebhookBundle\Helper\CampaignHelper;
use MauticPlugin\MauticCustomWebhookBundle\CustomWebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignHelper $campaignHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            CustomWebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignTriggerAction(CampaignExecutionEvent $event): void
    {
        if ($event->checkContext('campaign.send_custom_webhook')) {
            try {
                $this->campaignHelper->fireCustomWebhook($event->getConfig(), $event->getLead());
                $event->setResult(true);
            } catch (\Exception $e) {
                $event->setFailed($e->getMessage());
            }
        }
    }

    /**
     * Add event triggers and actions.
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event): void
    {
        $sendCustomWebhookAction = [
            'label'              => 'mautic.custom_webhook.event.send_custom_webhook',
            'description'        => 'mautic.custom_webhook.event.send_custom_webhook_desc',
            'formType'           => CampaignEventCustomWebhookType::class,
            'formTypeCleanMasks' => 'clean',
            'eventName'          => CustomWebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        
        $event->addAction('campaign.send_custom_webhook', $sendCustomWebhookAction);
    }
}