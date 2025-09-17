<?php

namespace MauticPlugin\MauticCustomWebhookBundle\Helper;

use Doctrine\Common\Collections\Collection;
use GuzzleHttp\Client;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\MauticCustomWebhookBundle\CustomWebhookEvents;
use MauticPlugin\MauticCustomWebhookBundle\Event\CustomWebhookRequestEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CampaignHelper
{
    /**
     * Cached contact values in format [contact_id => [key1 => val1, key2 => val1]].
     */
    private array $contactsValues = [];

    public function __construct(
        protected Client $client,
        protected CompanyModel $companyModel,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Prepara os dados necessários e faz a requisição HTTP personalizada.
     */
    public function fireCustomWebhook(array $config, Lead $contact): bool
    {
        try {
            // Processa o payload JSON
            $payload = $this->processJsonPayload($config['json_payload'] ?? '{}', $contact);
            
            // Processa os headers
            $headersInput = $config['headers'] ?? '';
            if (is_array($headersInput)) {
                // Se é um array com estrutura ['list' => []], extrai a lista
                if (isset($headersInput['list']) && is_array($headersInput['list'])) {
                    $headerLines = [];
                    foreach ($headersInput['list'] as $header) {
                        if (is_array($header) && isset($header['label']) && isset($header['value'])) {
                            $headerLines[] = $header['label'] . ': ' . $header['value'];
                        }
                    }
                    $headersInput = implode("\n", $headerLines);
                } else {
                    // Se é um array simples, junta com quebras de linha
                    $headersInput = implode("\n", $headersInput);
                }
            }
            $headers = $this->processHeaders($headersInput, $contact);
            
            // Processa a URL com tokens
            $url = $this->processUrl($config['url'] ?? '', $contact);
            
            // Método HTTP
            $method = strtoupper($config['method'] ?? 'POST');
            
            // Timeout
            $timeout = (int) ($config['timeout'] ?? 30);

            // Dispara evento antes da requisição
            $webhookRequestEvent = new CustomWebhookRequestEvent($contact, $url, $headers, $payload, $method);
            $this->dispatcher->dispatch($webhookRequestEvent, CustomWebhookEvents::ON_REQUEST);

            // Faz a requisição HTTP
            $this->makeRequest(
                $webhookRequestEvent->getUrl(),
                $webhookRequestEvent->getMethod(),
                $timeout,
                $webhookRequestEvent->getHeaders(),
                $webhookRequestEvent->getPayload()
            );

            $this->logger->info('Custom webhook sent successfully', [
                'contact_id' => $contact->getId(),
                'url' => $url,
                'method' => $method,
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Custom webhook failed', [
                'contact_id' => $contact->getId(),
                'error' => $e->getMessage(),
                'config' => $config,
            ]);
            return false;
        }
    }

    /**
     * Processa o payload JSON substituindo tokens pelos valores do contato.
     */
    private function processJsonPayload(string $jsonPayload, Lead $contact): array
    {
        if (empty($jsonPayload)) {
            return [];
        }

        // Substitui tokens no JSON
        $processedJson = $this->replaceTokens($jsonPayload, $contact);
        
        // Decodifica o JSON
        $payload = json_decode($processedJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload: ' . json_last_error_msg());
        }

        return $payload ?? [];
    }

    /**
     * Processa os headers substituindo tokens pelos valores do contato.
     */
    private function processHeaders(string $headersString, Lead $contact): array
    {
        if (empty($headersString)) {
            return ['Content-Type' => 'application/json'];
        }

        $headers = [];
        $lines = explode("\n", $headersString);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Substitui tokens no valor do header
                $value = $this->replaceTokens($value, $contact);
                
                $headers[$key] = $value;
            }
        }

        // Garante que Content-Type seja application/json se não especificado
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    /**
     * Processa a URL substituindo tokens pelos valores do contato.
     */
    private function processUrl(string $url, Lead $contact): string
    {
        return $this->replaceTokens($url, $contact);
    }

    /**
     * Substitui tokens pelos valores do contato.
     */
    private function replaceTokens(string $content, Lead $contact): string
    {
        $contactValues = $this->getContactValues($contact);
        
        // Adiciona tokens especiais
        $contactValues['date_now'] = date('Y-m-d H:i:s');
        $contactValues['timestamp'] = time();
        $contactValues['campaign_id'] = $contact->getId(); // Pode ser melhorado para pegar o ID real da campanha
        
        return rawurldecode(TokenHelper::findLeadTokens($content, $contactValues, true));
    }

    /**
     * Faz a requisição HTTP.
     */
    private function makeRequest(string $url, string $method, int $timeout, array $headers, array $payload): void
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => $headers,
            \GuzzleHttp\RequestOptions::TIMEOUT => $timeout,
        ];

        switch (strtolower($method)) {
            case 'get':
                if (!empty($payload)) {
                    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query($payload);
                }
                $response = $this->client->get($url, $options);
                break;
                
            case 'post':
            case 'put':
            case 'patch':
                $headers = array_change_key_case($headers);
                if (isset($headers['content-type']) && strpos(strtolower($headers['content-type']), 'application/json') !== false) {
                    $options[\GuzzleHttp\RequestOptions::BODY] = json_encode($payload);
                } else {
                    $options[\GuzzleHttp\RequestOptions::FORM_PARAMS] = $payload;
                }
                $response = $this->client->request($method, $url, $options);
                break;
                
            case 'delete':
                if (!empty($payload)) {
                    $options[\GuzzleHttp\RequestOptions::BODY] = json_encode($payload);
                }
                $response = $this->client->delete($url, $options);
                break;
                
            default:
                throw new \InvalidArgumentException('HTTP method "' . $method . '" is not supported.');
        }

        $statusCode = $response->getStatusCode();
        if (!in_array($statusCode, [200, 201, 202, 204])) {
            throw new \RuntimeException('Custom webhook response returned error code: ' . $statusCode);
        }
    }

    /**
     * Obtém os valores do contato para substituição de tokens.
     */
    private function getContactValues(Lead $contact): array
    {
        if (empty($this->contactsValues[$contact->getId()])) {
            $this->contactsValues[$contact->getId()] = $contact->getProfileFields();
            $this->contactsValues[$contact->getId()]['ipAddress'] = $this->ipAddressesToCsv($contact->getIpAddresses());
            $this->contactsValues[$contact->getId()]['companies'] = $this->companyModel->getRepository()->getCompaniesByLeadId($contact->getId());
        }

        return $this->contactsValues[$contact->getId()];
    }

    /**
     * Converte endereços IP em string CSV.
     */
    private function ipAddressesToCsv(Collection $ipAddresses): string
    {
        $addresses = [];
        foreach ($ipAddresses as $ipAddress) {
            $addresses[] = $ipAddress->getIpAddress();
        }

        return implode(',', $addresses);
    }
}