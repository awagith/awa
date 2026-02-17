<?php
declare(strict_types=1);

namespace GrupoAwamotos\SmartSuggestions\Cron;

use GrupoAwamotos\SmartSuggestions\Api\SuggestionEngineInterface;
use GrupoAwamotos\SmartSuggestions\Api\WhatsappSenderInterface;
use GrupoAwamotos\SmartSuggestions\Helper\Config;
use GrupoAwamotos\SmartSuggestions\Model\SuggestionHistoryFactory;
use GrupoAwamotos\SmartSuggestions\Model\ResourceModel\SuggestionHistory as SuggestionHistoryResource;
use Psr\Log\LoggerInterface;

/**
 * Cron job to generate suggestions for at-risk customers
 */
class GenerateSuggestions
{
    private SuggestionEngineInterface $suggestionEngine;
    private WhatsappSenderInterface $whatsappSender;
    private Config $config;
    private LoggerInterface $logger;
    private SuggestionHistoryFactory $historyFactory;
    private SuggestionHistoryResource $historyResource;

    public function __construct(
        SuggestionEngineInterface $suggestionEngine,
        WhatsappSenderInterface $whatsappSender,
        Config $config,
        LoggerInterface $logger,
        SuggestionHistoryFactory $historyFactory,
        SuggestionHistoryResource $historyResource
    ) {
        $this->suggestionEngine = $suggestionEngine;
        $this->whatsappSender = $whatsappSender;
        $this->config = $config;
        $this->logger = $logger;
        $this->historyFactory = $historyFactory;
        $this->historyResource = $historyResource;
    }

    /**
     * Execute suggestion generation cron
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        if (!$this->config->isSuggestionsCronEnabled()) {
            return;
        }

        $this->logger->info('SmartSuggestions: Starting suggestion generation cron');

        try {
            $startTime = microtime(true);

            // Get top opportunities (at-risk customers)
            $opportunities = $this->suggestionEngine->getTopOpportunities(50);

            $generated = 0;
            $sent = 0;
            $errors = 0;

            foreach ($opportunities as $opportunity) {
                try {
                    // Generate suggestion
                    $suggestion = $this->suggestionEngine->generateCartSuggestion(
                        $opportunity['customer_id']
                    );

                    if (isset($suggestion['error'])) {
                        $this->logger->warning('SmartSuggestions: Failed to generate suggestion', [
                            'customer_id' => $opportunity['customer_id'],
                            'error' => $suggestion['error']
                        ]);
                        $errors++;
                        continue;
                    }

                    $generated++;

                    // Save to history
                    $history = $this->historyFactory->create();
                    $history->setData([
                        'customer_id' => $opportunity['customer_id'],
                        'customer_name' => $opportunity['customer_name'],
                        'suggestion_data' => json_encode($suggestion),
                        'total_value' => $suggestion['cart_summary']['total_value'] ?? 0,
                        'products_count' => $suggestion['cart_summary']['total_products'] ?? 0,
                        'status' => 'generated',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $this->historyResource->save($history);

                    // Auto-send via WhatsApp if enabled
                    if ($this->config->isAutoSendWhatsappEnabled() && $this->config->isWhatsappEnabled()) {
                        $phone = $suggestion['customer']['phone'] ?? null;

                        if ($phone) {
                            $result = $this->whatsappSender->sendSuggestion($phone, $suggestion);

                            if ($result['success']) {
                                $sent++;
                                $history->setData('status', 'sent');
                                $history->setData('sent_at', date('Y-m-d H:i:s'));
                                $history->setData('whatsapp_message_id', $result['message_id'] ?? null);
                            } else {
                                $history->setData('status', 'send_failed');
                                $history->setData('error_message', $result['message']);
                            }

                            $this->historyResource->save($history);
                        }
                    }

                } catch (\Exception $e) {
                    $this->logger->error('SmartSuggestions: Error processing customer', [
                        'customer_id' => $opportunity['customer_id'],
                        'error' => $e->getMessage()
                    ]);
                    $errors++;
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            $this->logger->info(sprintf(
                'SmartSuggestions: Suggestion generation completed. Generated: %d, Sent: %d, Errors: %d in %s seconds',
                $generated,
                $sent,
                $errors,
                $duration
            ));

        } catch (\Exception $e) {
            $this->logger->error('SmartSuggestions: Suggestion generation cron failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
