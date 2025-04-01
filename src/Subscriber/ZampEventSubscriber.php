<?php

namespace ZampTax\Subscriber;

use stdClass;
use DateTime;
use DateTimeZone;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use ZampTax\Core\Content\ZampTransactions\ZampTransactionsEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Doctrine\DBAL\Connection;

/**
 * Event subscriber for handling Zamp tax integration events
 */
class ZampEventSubscriber implements EventSubscriberInterface
{
    /** @var Connection */
    private $connection;
    
    /** @var EntityRepository */
    private $orderRepository;
    
    /** @var EntityRepository */
    private $orderTransactionRepository;
    
    /** @var EntityRepository */
    private $zampTransactionsRepository;
    
    /** @var EntityRepository */
    private $taxProviderRepository;

    /**
     * Constructor
     * 
     * @param Connection $connection Database connection
     * @param EntityRepository $orderRepository Order repository
     * @param EntityRepository $orderTransactionRepository Order transaction repository
     * @param EntityRepository $zampTransactionsRepository Zamp transactions repository
     * @param EntityRepository $taxProviderRepository Tax provider repository
     */
    public function __construct(
        Connection $connection,
        EntityRepository $orderRepository, 
        EntityRepository $orderTransactionRepository,
        EntityRepository $zampTransactionsRepository, 	
        EntityRepository $taxProviderRepository	
    )
    {
        $this->connection = $connection;
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->zampTransactionsRepository = $zampTransactionsRepository;
        $this->taxProviderRepository = $taxProviderRepository;
    }

    /**
     * Retrieves Zamp settings from the database
     *
     * @return array|false Settings array containing API token and configuration
     */
    public function getZampSettings()
    {
        $sql =  '
            SELECT
                api_token,
                taxable_states,
                calculations_enabled,
                transactions_enabled
            FROM
                zamp_settings
            LIMIT 1
        ';

        $result = $this->connection->fetchAssociative($sql);

        return $result;
    }

    /**
     * Retrieves Zamp product tax code for a specific product
     *
     * @param string $productId The product ID
     * @return array|false Product tax code data
     */
    public function getZampProductTaxCode($productId)
    {
        $sql =  '
            SELECT
                *
            FROM
                zamp_product_tax_code
            WHERE
                product_id = :productId
            LIMIT 1
        ';

        $result = $this->connection->fetchAssociative($sql, [
            'productId' => $productId
        ]);

        return $result;
    }

    /**
     * Checks if the Zamp tax provider is active
     *
     * @return bool|null True if active, false if not, null if not found
     */
    public function getTaxProviderActiveStatus(): ?bool
    {
        $sql = '
            SELECT
                active
            FROM
                tax_provider
            WHERE
                identifier = :identifier
            LIMIT 1
        ';

        $result = $this->connection->fetchAssociative($sql, [
            'identifier' => 'ZampTax\Checkout\Cart\Tax\ZampTax'
        ]);

        return $result ? (bool) $result['active'] : null;
    }

    /**
     * Returns the events this subscriber listens to
     *
     * @return array Array of event names mapped to method names
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderWritten',
            OrderEvents::ORDER_DELETED_EVENT => 'onOrderDeleted',
            StateMachineTransitionEvent::class => 'onStateTransition'
        ];
    }

    /**
     * Gets the current suffix for a transaction
     *
     * @param string $orderId Order ID
     * @return string JSON string with transaction information
     */
    public function get_current_suffix(string $orderId): string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->setLimit(1);
    
        $result = $this->zampTransactionsRepository->search($criteria, Context::createDefaultContext());
    
        if ($result->count() > 0) {
            $transaction = $result->first();
    
            return json_encode([
                'found' => true,
                'suffix' => $transaction->getCurrentIdSuffix(),
                'status' => $transaction->getStatus(),
                'id' => $transaction->getId()
            ]);
        }
    
        return json_encode([
            'found' => false,
            'suffix' => "01",
            'status' => "",
            'id' => ""
        ]);
    }

    /**
     * Gets transaction information for an order
     *
     * @param string $orderId Order ID
     * @return string JSON string with transaction information
     */
    public function get_trans_info(string $orderId): string {
        $timezone = new DateTimeZone('UTC');
    
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->setLimit(1);

        $result = $this->zampTransactionsRepository->search($criteria, Context::createDefaultContext());

        $dateTime = new DateTime('now', $timezone);
                            
        $formattedTime = $dateTime->format('H:i:s');

        $hook_file = fopen(date('Y-m-d') . "_log.txt", "a+");
        fwrite($hook_file, "\n\n");
        fwrite($hook_file, $formattedTime . " - ORDER RESULT LOCATED.\n");
        fwrite($hook_file, "RESULT: " . json_encode($result, JSON_PRETTY_PRINT));
        fclose($hook_file);
    
        if ($result->count() > 0) {
            $transaction = $result->first();
    
            return json_encode([
                'found' => true,
                'suffix' => $transaction->getCurrentIdSuffix(),
                'status' => $transaction->getStatus(),
                'version' => $transaction->getFirstVersionId(),
                'order' => $orderId,
                'id' => $transaction->getId()
            ]);
        }
    
        return json_encode([
            'found' => false
        ]);
    }

    /**
     * Event handler for when an order is written
     *
     * @param EntityWrittenEvent $event The write event
     */
    public function onOrderWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $result) {
            $payload = $result->getPayload();

            if (isset($payload['id'])) {
                $orderId = $payload['id'];
                $this->logOrderDetails($orderId, $event->getContext());
            }
        }
    }

    /**
     * Event handler for when an order is deleted
     *
     * @param EntityDeletedEvent $event The delete event
     */
    public function onOrderDeleted(EntityDeletedEvent $event): void
    {
        // Implementation details omitted for brevity
    }

    /**
     * Event handler for state transitions
     *
     * @param StateMachineTransitionEvent $event The state transition event
     */
    public function onStateTransition(StateMachineTransitionEvent $event): void
    {
        // Implementation details omitted for brevity
    }

    /**
     * Logs order details for debugging
     *
     * @param string $orderId The order ID
     * @param Context $context Shopware context
     */
    private function logOrderDetails(string $orderId, Context $context): void
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('addresses.country');
        $criteria->addAssociation('addresses.countryState');

        $order = $this->orderRepository->search($criteria, $context)->first();

        if ($order) {
            $orderData = json_encode($order, JSON_PRETTY_PRINT);

            $logFile = fopen("order_log.txt", "a+");
            fwrite($logFile, "\n\nOrder Data: " . $orderData);
            fclose($logFile);
        }
    }
}
