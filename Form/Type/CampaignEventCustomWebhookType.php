<?php

namespace MauticPlugin\MauticCustomWebhookBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\SortableListType;
use MauticPlugin\MauticCustomWebhookBundle\Form\DataTransformer\JsonPreserveTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<mixed>>
 */
class CampaignEventCustomWebhookType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'url',
            UrlType::class,
            [
                'label'       => 'mautic.custom_webhook.event.url',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class' => 'form-control',
                    'tooltip' => 'mautic.custom_webhook.event.url.tooltip',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                ],
            ]
        );

        $builder->add(
            'method',
            ChoiceType::class,
            [
                'choices' => [
                    'GET'    => 'get',
                    'POST'   => 'post',
                    'PUT'    => 'put',
                    'PATCH'  => 'patch',
                    'DELETE' => 'delete',
                ],
                'multiple'   => false,
                'label_attr' => ['class' => 'control-label'],
                'label'      => 'mautic.custom_webhook.event.method',
                'attr'       => [
                    'class' => 'form-control',
                ],
                'placeholder' => false,
                'required'    => false,
                'data'        => 'post',
            ]
        );

        $jsonField = $builder->create(
            'json_payload',
            TextareaType::class,
            [
                'label'       => 'mautic.custom_webhook.event.json_payload',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class' => 'form-control',
                    'rows'  => 15,
                    'tooltip' => 'mautic.custom_webhook.event.json_payload.tooltip',
                    'placeholder' => $this->getJsonPlaceholder(),
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                    new \Symfony\Component\Validator\Constraints\Callback([
                        'callback' => function ($value, $context) {
                            if (empty($value)) {
                                return;
                            }
                            
                            // Remove possíveis caracteres invisíveis
                            $cleanValue = trim($value);
                            $cleanValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanValue);
                            
                            // Decodifica entidades HTML se existirem
                            $cleanValue = html_entity_decode($cleanValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            
                            // Tenta decodificar o JSON
                            json_decode($cleanValue);
                            
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $context->buildViolation('mautic.custom_webhook.event.json_payload.invalid')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
            ]
        );
        
        $jsonField->addModelTransformer(new JsonPreserveTransformer());
        $builder->add($jsonField);

        $builder->add(
            'timeout',
            NumberType::class,
            [
                'label'      => 'mautic.custom_webhook.event.timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'          => 'form-control',
                    'postaddon_text' => 'seconds',
                ],
                'data' => !empty($options['data']['timeout']) ? $options['data']['timeout'] : 30,
            ]
        );

        $builder->add(
            'headers',
            SortableListType::class,
            [
                'required'        => false,
                'label'           => 'mautic.webhook.event.sendwebhook.headers',
                'option_required' => false,
                'with_labels'     => true,
            ]
        );
    }

    private function getJsonPlaceholder(): string
    {
        return json_encode([
            'name' => '{contactfield=firstname} {contactfield=lastname}',
            'email' => '{contactfield=email}',
            'variables' => [
                [
                    'key' => 'document',
                    'value' => '{contactfield=document}'
                ],
                [
                    'key' => 'phone',
                    'value' => '{contactfield=phone}'
                ]
            ],
            'attachments' => [
                [
                    'fileUrl' => 'https://example.com/file.pdf',
                    'fileName' => 'document.pdf',
                    'fileType' => 'PDF'
                ]
            ],
            'metadata' => [
                'campaign_id' => '{campaign_id}',
                'contact_id' => '{contactfield=id}',
                'timestamp' => '{date_now}'
            ]
        ], JSON_PRETTY_PRINT);
    }

    public function getBlockPrefix(): string
    {
        return 'campaignevent_custom_webhook';
    }
}