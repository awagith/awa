<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

/**
 * Circuit Breaker Pattern Implementation
 *
 * Prevents cascading failures by stopping requests to a failing service.
 *
 * States:
 * - CLOSED: Normal operation, requests flow through
 * - OPEN: Circuit tripped after failures, requests blocked
 * - HALF_OPEN: Testing if service recovered
 *
 * Configuration:
 * - Failure threshold: 5 consecutive failures → OPEN
 * - Open timeout: 60s before trying HALF_OPEN
 * - Success threshold: 3 consecutive successes → CLOSED
 */
class CircuitBreaker
{
    private const CACHE_PREFIX = 'erp_circuit_breaker_';
    private const CACHE_LIFETIME = 3600; // 1 hour

    /**
     * Number of failures before opening circuit
     */
    private const FAILURE_THRESHOLD = 5;

    /**
     * Seconds to wait before trying half-open
     */
    private const OPEN_TIMEOUT = 60;

    /**
     * Number of successes in half-open before closing
     */
    private const SUCCESS_THRESHOLD = 3;

    private CacheInterface $cache;
    private LoggerInterface $logger;
    private string $serviceName;

    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger,
        string $serviceName = 'erp_connection'
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->serviceName = $serviceName;
    }

    /**
     * Check if request is allowed
     */
    public function isAvailable(): bool
    {
        $state = $this->getState();

        switch ($state) {
            case CircuitBreakerState::CLOSED:
                return true;

            case CircuitBreakerState::OPEN:
                // Check if timeout has passed to transition to half-open
                if ($this->hasOpenTimeoutPassed()) {
                    $this->transitionToHalfOpen();
                    return true;
                }
                return false;

            case CircuitBreakerState::HALF_OPEN:
                return true;

            default:
                return true;
        }
    }

    /**
     * Record a successful operation
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === CircuitBreakerState::HALF_OPEN) {
            $successCount = $this->incrementSuccessCount();

            if ($successCount >= self::SUCCESS_THRESHOLD) {
                $this->transitionToClosed();
                $this->logger->info('[ERP CircuitBreaker] Circuit closed after successful recovery', [
                    'service' => $this->serviceName,
                    'success_count' => $successCount,
                ]);
            }
        } elseif ($state === CircuitBreakerState::CLOSED) {
            // Reset failure count on success
            $this->resetFailureCount();
        }
    }

    /**
     * Record a failed operation
     */
    public function recordFailure(?\Exception $exception = null): void
    {
        $state = $this->getState();

        if ($state === CircuitBreakerState::HALF_OPEN) {
            // Any failure in half-open immediately opens circuit
            $this->transitionToOpen();
            $this->logger->warning('[ERP CircuitBreaker] Circuit re-opened after half-open failure', [
                'service' => $this->serviceName,
                'error' => $exception ? $exception->getMessage() : 'Unknown error',
            ]);
            return;
        }

        if ($state === CircuitBreakerState::CLOSED) {
            $failureCount = $this->incrementFailureCount();

            if ($failureCount >= self::FAILURE_THRESHOLD) {
                $this->transitionToOpen();
                $this->logger->error('[ERP CircuitBreaker] Circuit opened due to repeated failures', [
                    'service' => $this->serviceName,
                    'failure_count' => $failureCount,
                    'threshold' => self::FAILURE_THRESHOLD,
                    'last_error' => $exception ? $exception->getMessage() : 'Unknown error',
                ]);
            } else {
                $this->logger->warning('[ERP CircuitBreaker] Failure recorded', [
                    'service' => $this->serviceName,
                    'failure_count' => $failureCount,
                    'threshold' => self::FAILURE_THRESHOLD,
                    'error' => $exception ? $exception->getMessage() : 'Unknown error',
                ]);
            }
        }
    }

    /**
     * Get current circuit state
     */
    public function getState(): string
    {
        $data = $this->getStateData();
        return $data['state'] ?? CircuitBreakerState::CLOSED;
    }

    /**
     * Get circuit breaker statistics
     */
    public function getStats(): array
    {
        $data = $this->getStateData();

        return [
            'state' => $data['state'] ?? CircuitBreakerState::CLOSED,
            'failure_count' => $data['failure_count'] ?? 0,
            'success_count' => $data['success_count'] ?? 0,
            'last_failure_time' => $data['last_failure_time'] ?? null,
            'opened_at' => $data['opened_at'] ?? null,
            'failure_threshold' => self::FAILURE_THRESHOLD,
            'success_threshold' => self::SUCCESS_THRESHOLD,
            'open_timeout' => self::OPEN_TIMEOUT,
            'time_until_half_open' => $this->getTimeUntilHalfOpen(),
        ];
    }

    /**
     * Force reset circuit breaker to closed state
     */
    public function reset(): void
    {
        $this->saveStateData([
            'state' => CircuitBreakerState::CLOSED,
            'failure_count' => 0,
            'success_count' => 0,
            'last_failure_time' => null,
            'opened_at' => null,
        ]);

        $this->logger->info('[ERP CircuitBreaker] Circuit manually reset to closed', [
            'service' => $this->serviceName,
        ]);
    }

    /**
     * Execute operation with circuit breaker protection
     *
     * @param callable $operation The operation to execute
     * @param callable|null $fallback Optional fallback when circuit is open
     * @return mixed
     * @throws CircuitBreakerOpenException When circuit is open and no fallback
     */
    public function execute(callable $operation, ?callable $fallback = null)
    {
        if (!$this->isAvailable()) {
            $this->logger->info('[ERP CircuitBreaker] Request blocked - circuit is open', [
                'service' => $this->serviceName,
                'time_until_half_open' => $this->getTimeUntilHalfOpen(),
            ]);

            if ($fallback !== null) {
                return $fallback();
            }

            throw new CircuitBreakerOpenException(
                new Phrase(
                    'Circuit breaker is open for service "%1". Retry in %2 seconds.',
                    [$this->serviceName, $this->getTimeUntilHalfOpen()]
                )
            );
        }

        try {
            $result = $operation();
            $this->recordSuccess();
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    // ==================== Private Methods ====================

    private function getStateData(): array
    {
        $cacheKey = $this->getCacheKey();
        $data = $this->cache->load($cacheKey);

        if ($data === false) {
            return [
                'state' => CircuitBreakerState::CLOSED,
                'failure_count' => 0,
                'success_count' => 0,
                'last_failure_time' => null,
                'opened_at' => null,
            ];
        }

        try {
            $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException $e) {
            $this->logger->warning('[ERP CircuitBreaker] Corrupted state data, resetting', [
                'service' => $this->serviceName,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function saveStateData(array $data): void
    {
        $cacheKey = $this->getCacheKey();
        $this->cache->save(
            json_encode($data, JSON_THROW_ON_ERROR),
            $cacheKey,
            ['erp_circuit_breaker'],
            self::CACHE_LIFETIME
        );
    }

    private function getCacheKey(): string
    {
        return self::CACHE_PREFIX . $this->serviceName;
    }

    private function transitionToOpen(): void
    {
        $data = $this->getStateData();
        $data['state'] = CircuitBreakerState::OPEN;
        $data['opened_at'] = time();
        $data['success_count'] = 0;
        $this->saveStateData($data);
    }

    private function transitionToHalfOpen(): void
    {
        $data = $this->getStateData();
        $data['state'] = CircuitBreakerState::HALF_OPEN;
        $data['success_count'] = 0;
        $this->saveStateData($data);

        $this->logger->info('[ERP CircuitBreaker] Circuit transitioned to half-open', [
            'service' => $this->serviceName,
        ]);
    }

    private function transitionToClosed(): void
    {
        $this->saveStateData([
            'state' => CircuitBreakerState::CLOSED,
            'failure_count' => 0,
            'success_count' => 0,
            'last_failure_time' => null,
            'opened_at' => null,
        ]);
    }

    private function hasOpenTimeoutPassed(): bool
    {
        $data = $this->getStateData();
        $openedAt = $data['opened_at'] ?? 0;

        if ($openedAt === 0) {
            return true;
        }

        return (time() - $openedAt) >= self::OPEN_TIMEOUT;
    }

    private function getTimeUntilHalfOpen(): int
    {
        $data = $this->getStateData();

        if ($data['state'] !== CircuitBreakerState::OPEN) {
            return 0;
        }

        $openedAt = $data['opened_at'] ?? 0;
        $elapsed = time() - $openedAt;
        $remaining = self::OPEN_TIMEOUT - $elapsed;

        return max(0, $remaining);
    }

    /**
     * @note Race condition: read-modify-write on Redis cache is not atomic.
     * Concurrent cron jobs may overwrite each other's increments. Acceptable
     * because circuit breaker thresholds are soft limits (5 failures, 3 successes)
     * and the worst case is a slightly delayed state transition.
     */
    private function incrementFailureCount(): int
    {
        $data = $this->getStateData();
        $data['failure_count'] = ($data['failure_count'] ?? 0) + 1;
        $data['last_failure_time'] = time();
        $this->saveStateData($data);

        return $data['failure_count'];
    }

    /** @see incrementFailureCount() for race condition note */
    private function incrementSuccessCount(): int
    {
        $data = $this->getStateData();
        $data['success_count'] = ($data['success_count'] ?? 0) + 1;
        $this->saveStateData($data);

        return $data['success_count'];
    }

    private function resetFailureCount(): void
    {
        $data = $this->getStateData();

        if (($data['failure_count'] ?? 0) > 0) {
            $data['failure_count'] = 0;
            $this->saveStateData($data);
        }
    }
}
