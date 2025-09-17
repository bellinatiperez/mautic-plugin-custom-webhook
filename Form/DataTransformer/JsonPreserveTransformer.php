<?php

namespace MauticPlugin\MauticCustomWebhookBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transformer para preservar JSON sem escape HTML
 */
class JsonPreserveTransformer implements DataTransformerInterface
{
    /**
     * Transforma dados do modelo para o formulário
     */
    public function transform($value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        // Decodifica entidades HTML específicas manualmente
        $value = $this->decodeHtmlEntities($value);
        
        // Se for um array ou objeto, converte para JSON
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return (string) $value;
    }

    /**
     * Transforma dados do formulário para o modelo
     */
    public function reverseTransform($value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        // Remove possíveis caracteres invisíveis
        $cleanValue = trim($value);
        $cleanValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanValue);
        
        // Decodifica entidades HTML específicas manualmente
        $cleanValue = $this->decodeHtmlEntities($cleanValue);
        
        return $cleanValue;
    }

    /**
     * Decodifica entidades HTML específicas que causam problemas
     */
    private function decodeHtmlEntities(string $value): string
    {
        // Mapeamento específico das entidades HTML problemáticas
        $entities = [
            '&#13;&#10;' => "\n",
            '&#13;' => "\r",
            '&#10;' => "\n",
            '&#34;' => '"',
            '&#39;' => "'",
            '&#60;' => '<',
            '&#62;' => '>',
            '&#38;' => '&',
        ];
        
        // Aplica as substituições
        $decoded = str_replace(array_keys($entities), array_values($entities), $value);
        
        // Aplica html_entity_decode como fallback
        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $decoded;
    }
}