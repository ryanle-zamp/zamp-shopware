<?php

namespace ZampTax\Subscriber;

use stdClass;
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
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
    /** @var LoggerInterface */
    private $logger;

    /**
     * Constructor
     * 
     * @param Connection $connection Database connection
     * @param EntityRepository $orderRepository Order repository
     * @param EntityRepository $orderTransactionRepository Order transaction repository
     * @param EntityRepository $zampTransactionsRepository Zamp transactions repository
     * @param EntityRepository $taxProviderRepository Tax provider repository
     * @param LoggerInterface $logger
     */
    public function __construct(
		Connection $connection,
		EntityRepository $orderRepository, 
		EntityRepository $orderTransactionRepository,
        EntityRepository $zampTransactionsRepository, 	
        EntityRepository $taxProviderRepository,
        LoggerInterface $logger	
	)
    {
        $this->connection = $connection;
        $this->orderRepository = $orderRepository;
		$this->orderTransactionRepository = $orderTransactionRepository;
        $this->zampTransactionsRepository = $zampTransactionsRepository;
        $this->taxProviderRepository = $taxProviderRepository;
        $this->logger = $logger;
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
    public function get_trans_info(string $orderId): string
    {
        $timezone = new DateTimeZone('UTC');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->setLimit(1);

        $result = $this->zampTransactionsRepository->search($criteria, Context::createDefaultContext());

        $dateTime = new DateTime('now', $timezone);
        $formattedTime = $dateTime->format('H:i:s');

        $this->logger->info("{$formattedTime} - ORDER RESULT LOCATED.", [
            'orderId' => $orderId,
            'resultCount' => $result->count(),
            'resultData' => $result
        ]);

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
        $timezone = new DateTimeZone('UTC');
        $dateTime = new DateTime('now', $timezone);
        $formattedTime = $dateTime->format('H:i:s');

        foreach ($event->getWriteResults() as $result) {
            $payload = $result->getPayload();

            if (isset($payload['id'])) {
                $this->logger->info("{$formattedTime} - ORDER WRITTEN EVENT OCCURRED.", [
                    'eventPayload' => $payload
                ]);
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
        if (!$this->getTaxProviderActiveStatus()) {
            return;
        }

        $timezone = new DateTimeZone('UTC');
        $zamp_settings = $this->getZampSettings();
        $bear_token = $zamp_settings['api_token'];

        foreach ($event->getWriteResults() as $result) {
            $payload = $result->getPayload();
            $context = $event->getContext();

            if (!isset($payload['id'])) {
                continue;
            }

            $versionId = $payload['versionId'];

            foreach ($event->getIds() as $orderId) {
                $transaction_info = $this->get_trans_info($orderId);
                $transInfo = json_decode($transaction_info);

                if (!$transInfo->found || $transInfo->version !== $versionId) {
                    continue;
                }

                $this->logger->info('ORDER DELETED EVENT OCCURRED.', [
                    'payload' => $payload,
                    'transaction_info' => $transInfo
                ]);

                $suffix = $transInfo->suffix;
                $dataId = $transInfo->id;
                $origin_id = "SW-{$orderId}-{$suffix}";
                $origin_url = "https://api.zamp.com/transactions/{$origin_id}";

                // Retrieve original transaction
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $origin_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => [
                        "Accept: application/json",
                        "Content-Type: application/json",
                        "Authorization: Bearer {$bear_token}"
                    ],
                    CURLOPT_HEADER => true
                ]);
                $response_origin = curl_exec($curl);
                $err_origin = curl_error($curl);
                curl_close($curl);

                if ($err_origin) {
                    $this->logger->error('Error retrieving original transaction from Zamp.', [
                        'error' => $err_origin,
                        'url' => $origin_url
                    ]);
                    continue;
                }

                [$headers_origin, $body_origin] = explode("\r\n\r\n", $response_origin, 2);
                $statusLine_origin = strtok($headers_origin, "\r\n");

                $this->logger->info('Deleted order event response from Zamp original transaction retrieval.', [
                    'http_status' => $statusLine_origin,
                    'response' => json_decode($body_origin, true)
                ]);

                // Delete original transaction
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $origin_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "DELETE",
                    CURLOPT_HTTPHEADER => [
                        "Accept: application/json",
                        "Content-Type: application/json",
                        "Authorization: Bearer {$bear_token}"
                    ],
                    CURLOPT_HEADER => true
                ]);
                $response_del = curl_exec($curl);
                $err_del = curl_error($curl);
                curl_close($curl);

                if ($err_del) {
                    $this->logger->error('Error deleting order from Zamp.', [
                        'error' => $err_del,
                        'url' => $origin_url
                    ]);
                } else {
                    [$headers_del, $body_del] = explode("\r\n\r\n", $response_del, 2);
                    $statusLine_del = strtok($headers_del, "\r\n");

                    $this->logger->info('Order deleted response from Zamp.', [
                        'http_status' => $statusLine_del,
                        'response' => json_decode($body_del, true)
                    ]);
                }

                // If multiple suffixed transactions exist, delete them
                if ($suffix !== "01") {
                    $suffend = (int) $suffix;

                    for ($i = 1; $i < $suffend; $i++) {
                        $suffstring = str_pad((string) $i, 2, "0", STR_PAD_LEFT);
                        $next_id = "SW-{$orderId}-{$suffstring}";
                        $next_url = "https://api.zamp.com/transactions/{$next_id}";

                        $curl = curl_init();
                        curl_setopt_array($curl, [
                            CURLOPT_URL => $next_url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "DELETE",
                            CURLOPT_HTTPHEADER => [
                                "Accept: application/json",
                                "Content-Type: application/json",
                                "Authorization: Bearer {$bear_token}"
                            ],
                            CURLOPT_HEADER => true
                        ]);
                        $response_next = curl_exec($curl);
                        $err_next = curl_error($curl);
                        curl_close($curl);

                        if ($err_next) {
                            $this->logger->error('Error deleting previous transaction from Zamp.', [
                                'error' => $err_next,
                                'url' => $next_url
                            ]);
                        } else {
                            [$headers_next, $body_next] = explode("\r\n\r\n", $response_next, 2);
                            $statusLine_next = strtok($headers_next, "\r\n");

                            $this->logger->info('Deleted previous transaction response from Zamp.', [
                                'http_status' => $statusLine_next,
                                'response' => json_decode($body_next, true),
                                'transaction_id' => $next_id
                            ]);
                        }
                    }
                }

                // Delete local Zamp transaction record
                $this->zampTransactionsRepository->delete([['id' => $dataId]], $context);
            }
        }
    }


    
     /**
     * Event handler for state transitions
     *
     * @param StateMachineTransitionEvent $event The state transition event
     */
    public function onStateTransition(StateMachineTransitionEvent $event): void
    {
        if($this->getTaxProviderActiveStatus()){

            $timezone = new DateTimeZone('UTC');

            $entityName = $event->getEntityName();
            $toPlace = $event->getToPlace()->getName();   

            $dateTime = new DateTime('now', $timezone);

            $formattedTime = $dateTime->format('H:i:s');

            if($entityName == 'order_transaction' && $toPlace == 'Paid'){

                $taxable = false;

                $state_shortcodes = array(
                    "Alabama" => "AL",
                    "Alaska" => "AK",
                    "Arizona" => "AZ",
                    "Arkansas" => "AR",
                    "California" => "CA",
                    "Colorado" => "CO",
                    "Connecticut" => "CT",
                    "Delaware" => "DE",
                    "District of Columbia" => "DC",
                    "Florida" => "FL",
                    "Georgia" => "GA",
                    "Hawaii" => "HI",
                    "Idaho" => "ID",
                    "Illinois"=> "IL",
                    "Indiana" => "IN",
                    "Iowa" => "IA",
                    "Kansas" => "KS",
                    "Kentucky" => "KY",
                    "Louisiana" => "LA",
                    "Maine" => "ME",
                    "Maryland" => "MD",
                    "Massachusetts" => "MA",
                    "Michigan" => "MI",
                    "Minnesota" => "MN",
                    "Mississippi" => "MS",
                    "Missouri" => "MO",
                    "Montana" => "MT",
                    "Nebraska" => "NE",
                    "Nevada" => "NV",
                    "New Hampshire" => "NH",
                    "New Jersey" => "NJ",
                    "New Mexico" => "NM",
                    "New York" => "NY",
                    "North Carolina" => "NC",
                    "North Dakota" => "ND",
                    "Ohio" => "OH",
                    "Oklahoma" => "OK",
                    "Oregon" => "OR",
                    "Pennsylvania" => "PA",
                    "Puerto Rico" => "PR",
                    "Rhode Island" => "RI",
                    "South Carolina" => "SC",
                    "South Dakota" => "SD",
                    "Tennessee" => "TN",
                    "Texas" => "TX",
                    "Utah" => "UT",
                    "Vermont" => "VT",
                    "Virginia" => "VA",
                    "Washington" => "WA",
                    "West Virginia" => "WV",
                    "Wisconsin" => "WI",
                    "Wyoming" => "WY"
                );

                $ava_tax_exempt_codes = array(
                    'A' => 'FEDERAL_GOV', 
                    'B' => 'STATE_GOV', 
                    'C' => 'TRIBAL', 
                    'N' => 'LOCAL_GOV', 
                    'E' => 'NON_PROFIT', 
                    'F' => 'RELIGIOUS', 
                    'G' => 'WHOLESALER', 
                    'H' => 'AGRICULTURAL', 
                    'I' => 'INDUSTRIAL_PROCESSING', 
                    'J' => 'DIRECT_PAY', 
                    'M' => 'EDUCATIONAL', 
                    'D' => 'FEDERAL_GOV', 
                    'K' => 'DIRECT_PAY', 
                    'L' => 'LESSOR'
                );
        
                $zamp_tax_codes = array(
                    'FEDERAL_GOV' => 'FEDERAL_GOV', 
                    'STATE_GOV' => 'STATE_GOV', 
                    'TRIBAL' => 'TRIBAL', 
                    'LOCAL_GOV' => 'LOCAL_GOV', 
                    'NON_PROFIT' => 'NON_PROFIT', 
                    'RELIGIOUS' => 'RELIGIOUS', 
                    'WHOLESALER' => 'WHOLESALER', 
                    'AGRICULTURAL' => 'AGRICULTURAL', 
                    'INDUSTRIAL_PROCESSING' => 'INDUSTRIAL_PROCESSING', 
                    'DIRECT_PAY' => 'DIRECT_PAY', 
                    'EDUCATIONAL' => 'EDUCATIONAL', 
                    'LESSOR' => 'LESSOR',
                    'SNAP' => 'SNAP',
                    'MEDICAL' => 'MEDICAL',
                    'DATA_CENTER' => 'DATA_CENTER',
                    'EDU_PRIVATE' => 'EDU_PRIVATE',
                    'EDU_PUBLIC' => 'EDU_PUBLIC'
                );
        
                $zamp_exempt_code = "";

                $context = $event->getContext();
                $versionId = $context->getVersionId();
                $orderTransId = $event->getEntityId();

                $zamp_settings = $this->getZampSettings();

                $taxable_states = explode(',', $zamp_settings['taxable_states']);
                $bear_token = $zamp_settings['api_token'];
                $trans_enabled = $zamp_settings['transactions_enabled'];
                
                $ot_criteria = new Criteria([$orderTransId]);
                $transaction_order = $this->orderTransactionRepository->search($ot_criteria, $context)->first();

                $orderId = $transaction_order->getOrderId();

                $criteria = new Criteria([$orderId]);

                $criteria->addAssociation('lineItems');
                $criteria->addAssociation('deliveries');
                $criteria->addAssociation('deliveries.shippingOrderAddress');
                $criteria->addAssociation('deliveries.shippingOrderAddress.countryState');
                $criteria->addAssociation('orderCustomer');
                $criteria->addAssociation('orderCustomer.customer');
                $criteria->addAssociation('orderCustomer.customer.group');

                $order = $this->orderRepository->search($criteria, $context)->first();

                $order_number = $order->getOrderNumber();

                $dateTime = new DateTime('now', $timezone);
                        
                $formattedTime = $dateTime->format('H:i:s');

                $formattedDate = $order->getCreatedAt()->format('Y-m-d H:i:s');
                $this->logger->info("[$formattedTime] - PAID EVENT ORDER OBJECT RETRIEVED", [
                    'order' => $order
                    
                ]);

                $customer_group_id = $order->getOrderCustomer()->getCustomer()->getGroup()->id;

                $customer_group_custom_fields = $order->getOrderCustomer()->getCustomer()->getGroup()->getCustomFields();

                if(count($customer_group_custom_fields) && isSet($customer_group_custom_fields['tax_exempt_code'])){
                    $zamp_exempt_code = $customer_group_custom_fields['tax_exempt_code'];
                }

                if(isset($zamp_exempt_code) && trim($zamp_exempt_code) != ""){
                    if(strlen(trim($zamp_exempt_code)) == 1){
                        $zamp_exempt_code = $ava_tax_exempt_codes[trim($zamp_exempt_code)];
                    } else {
                        $zamp_exempt_code = trim($zamp_exempt_code);
                    }
                }


                $street = '';
                $city = '';
                $state = '';
                $zip = '';

                if($order){
                    $delivery = $order->getDeliveries()->first();

                    if ($delivery) {
                        $shippingAddress = $delivery->getShippingOrderAddress();
                        if ($shippingAddress) {
                            $street = $shippingAddress->getStreet();
                            $city = $shippingAddress->getCity();
                            $zip = $shippingAddress->getZipcode();
                            $state = $state_shortcodes[$shippingAddress->getCountryState()->name];
                        }
                    }	

                }

                $formattedDate = $order->createdAt->format('Y-m-d H:i:s');



                if($trans_enabled && in_array($state, $taxable_states)){
                    $zamp_items_arr = array();
                    $suffix = "01";

                    $subtotal = 0;
                    $zamp_json = new stdClass();
                    $zamp_json->id = "SW-" . $orderId . "-" . $suffix;
                    $zamp_json->name = 'SW-' . $order_number . "-" . $suffix;
                    $zamp_json->transactedAt = $formattedDate;
                    $zamp_json->entity = $zamp_exempt_code != "" ? $zamp_exempt_code : null;
                    $zamp_json->purpose = $zamp_exempt_code == "WHOLESALER" ? "RESALE" : null;
                    $zamp_json->discount = 0;				

                    foreach($order->lineItems as $item){
                        if($item->getType() == 'promotion'){
                            $zamp_json->discount += $item->getPrice()->getTotalPrice() * -1;
                        } else {
                            $item_obj = new stdClass();

                            $unit_price = $item->unitPrice;
                            $total_price = $item->totalPrice;

                            $subtotal += (float) number_format($total_price, 2);
                            $item_obj->quantity = $item->quantity;
                            $item_obj->id = $item->id;

                            $item_obj->amount = (float) number_format($unit_price, 2);
                            $item_obj->productName = $item->label;
                            $item_obj->productSku = $item->payload['productNumber'];
                            $ptc = $this->getZampProductTaxCode($item_obj->id) ? $this->getZampProductTaxCode($item_obj->id)['product_tax_code'] : '';
                            $item_obj->productTaxCode = $ptc !== '' && (substr($ptc, 0, 5) == "R_TPP" || substr($ptc, 0, 5) == "R_SRV" || substr($ptc, 0, 5) == "R_DIG") ? $ptc : "R_TPP";
                            array_push($zamp_items_arr, $item_obj);
                        }					
                    }

                    $zamp_json->subtotal = $subtotal - $zamp_json->discount;
                    $zamp_json->shippingHandling = $order->shippingCosts->getTotalPrice();
                    $zamp_json->total = $zamp_json->subtotal + $zamp_json->shippingHandling;

                    $shipToAddress = new stdClass();
                    $shipToAddress->line1 = $street;
                    $shipToAddress->line2 = 'empty';
                    $shipToAddress->city = $city;
                    $shipToAddress->state = $state;
                    $shipToAddress->country = 'US';
                    $shipToAddress->zip = $zip;

                    $zamp_json->shipToAddress = $shipToAddress;
                    $zamp_json->lineItems = $zamp_items_arr;

                    $zamp_obj = json_encode($zamp_json);

                    $dateTime = new DateTime('now', $timezone);
                            
                    $formattedTime = $dateTime->format('H:i:s');

                    $this->logger->info("[$formattedTime] - PAID EVENT REQUEST FOR ZAMP CALCULATION GENERATED", [
                        'request' => $zamp_obj
                    ]);	
        
                    $curl = curl_init();
                
                    $url = "https://api.zamp.com/calculations";
        
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => $zamp_obj,
                        CURLOPT_HTTPHEADER => [
                        "Accept: application/json",
                        "Content-Type: application/json",
                        "Authorization: Bearer " . $bear_token
                        ],
                    ]);
        
                    curl_setopt($curl, CURLOPT_HEADER, true);
                    
                    $response = curl_exec($curl);
            
                    header("Access-Control-Allow-Origin: *");

            
                    $err = curl_error($curl);
            
                    curl_close($curl);

                    $dateTime = new DateTime('now', $timezone);

                    $formattedTime = $dateTime->format('H:i:s');
            
                    if ($err){
                        $this->logger->error("[$formattedTime] - ERROR IN PAID EVENT REQUEST FOR ZAMP CALCULATION", [
                            'error' => $err
                        ]);
                        return;	
                    } else {
                        if($response){

                            $dateTime = new DateTime('now', $timezone);

                            $formattedTime = $dateTime->format('H:i:s');

                            $responseParts = explode("\r\n\r\n", $response, 2);
                            $httpResponseHeaders = isset($responseParts[0]) ? $responseParts[0] : '';
                            $jsonResponseBody = isset($responseParts[1]) ? $responseParts[1] : '';
                            
                            $this->logger->info("PAID EVENT RESPONSE RECEIVED FROM ZAMP CALCULATION PRIOR TO CHANGE", [
                                'http_status' => strtok($httpResponseHeaders, "\r\n"),
                                'response' => json_decode($jsonResponseBody, true),
                                'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('H:i:s')
                            ]);

                            $zamp_json->taxCollected = (float) number_format(json_decode($jsonResponseBody)->taxDue, 2);
                            $zamp_json->total = (float) number_format($zamp_json->subtotal + $zamp_json->shippingHandling + $zamp_json->taxCollected, 2);

                            $dateTime = new DateTime('now', $timezone);

                            $formattedTime = $dateTime->format('H:i:s');	

                            $this->logger->info("PAID EVENT REQUEST FROM ZAMP CALCULATION FOR ZAMP TRANSACTION GENERATED", [
                                'request' => json_decode(json_encode($zamp_json), true),
                                'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('H:i:s')
                            ]);
                            
                            
                            $curl2 = curl_init();

                            $url2 = "https://api.zamp.com/transactions";

                            curl_setopt_array($curl2, [
                                CURLOPT_URL => $url2,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "POST",
                                CURLOPT_POSTFIELDS => json_encode($zamp_json),
                                CURLOPT_HTTPHEADER => [
                                "Accept: application/json",
                                "Content-Type: application/json",
                                "Authorization: Bearer " . $bear_token
                                ],
                            ]);

                            curl_setopt($curl2, CURLOPT_HEADER, true);

                            $response2 = curl_exec($curl2);

                            header("Access-Control-Allow-Origin: *");

                            $err2 = curl_error($curl2);

                            curl_close($curl2);

                            if ($err2){

                                $dateTime = new DateTime('now', $timezone);

                                $formattedTime = $dateTime->format('H:i:s');

                                $this->logger->error("[$formattedTime] - ERROR IN PAID EVENT RESPONSE FROM ZAMP TRANSACTION", [
                                    'error' => $err2
                                ]);
                                return;	
                            } else {
                                if($response2){
                                    $zamp_resp = json_decode($response2);

                                    $dateTime = new DateTime('now', $timezone);

                                    $formattedTime = $dateTime->format('H:i:s');

                                    $responseParts2 = explode("\r\n\r\n", $response2, 2);
                                    $httpResponseHeaders2 = isset($responseParts2[0]) ? $responseParts2[0] : '';
                                    $jsonResponseBody2 = isset($responseParts2[1]) ? $responseParts2[1] : '';

                                    $this->logger->info("PAID EVENT RESPONSE RECEIVED FROM ZAMP TRANSACTION", [
                                        'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('H:i:s'),
                                        'http_status' => strtok($httpResponseHeaders2, "\r\n"),
                                        'response' => json_decode($jsonResponseBody2, true)
                                    ]);
                                    

                                    $zamp_trans = [
                                        'orderId' => $orderId,
                                        'firstVersionId' => $versionId,
                                        'orderNumber' => $order_number,
                                        'currentIdSuffix' => $suffix,
                                        'status' => 'commited'
                                    ];

                                    $this->zampTransactionsRepository->upsert([$zamp_trans], $event->getContext());

                                }
                            }
                        }
                    }
                }                       
            } else if ($entityName == 'order_transaction' && $toPlace == 'Refunded') {

                $this->logger->info("REFUND EVENT ORDER TRIGGER", [
                    'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('H:i:s')
                ]);
                
                
                $taxable = false;

                $state_shortcodes = array(
                    "Alabama" => "AL",
                    "Alaska" => "AK",
                    "Arizona" => "AZ",
                    "Arkansas" => "AR",
                    "California" => "CA",
                    "Colorado" => "CO",
                    "Connecticut" => "CT",
                    "Delaware" => "DE",
                    "District of Columbia" => "DC",
                    "Florida" => "FL",
                    "Georgia" => "GA",
                    "Hawaii" => "HI",
                    "Idaho" => "ID",
                    "Illinois"=> "IL",
                    "Indiana" => "IN",
                    "Iowa" => "IA",
                    "Kansas" => "KS",
                    "Kentucky" => "KY",
                    "Louisiana" => "LA",
                    "Maine" => "ME",
                    "Maryland" => "MD",
                    "Massachusetts" => "MA",
                    "Michigan" => "MI",
                    "Minnesota" => "MN",
                    "Mississippi" => "MS",
                    "Missouri" => "MO",
                    "Montana" => "MT",
                    "Nebraska" => "NE",
                    "Nevada" => "NV",
                    "New Hampshire" => "NH",
                    "New Jersey" => "NJ",
                    "New Mexico" => "NM",
                    "New York" => "NY",
                    "North Carolina" => "NC",
                    "North Dakota" => "ND",
                    "Ohio" => "OH",
                    "Oklahoma" => "OK",
                    "Oregon" => "OR",
                    "Pennsylvania" => "PA",
                    "Puerto Rico" => "PR",
                    "Rhode Island" => "RI",
                    "South Carolina" => "SC",
                    "South Dakota" => "SD",
                    "Tennessee" => "TN",
                    "Texas" => "TX",
                    "Utah" => "UT",
                    "Vermont" => "VT",
                    "Virginia" => "VA",
                    "Washington" => "WA",
                    "West Virginia" => "WV",
                    "Wisconsin" => "WI",
                    "Wyoming" => "WY"
                );

                $ava_tax_exempt_codes = array(
                    'A' => 'FEDERAL_GOV', 
                    'B' => 'STATE_GOV', 
                    'C' => 'TRIBAL', 
                    'N' => 'LOCAL_GOV', 
                    'E' => 'NON_PROFIT', 
                    'F' => 'RELIGIOUS', 
                    'G' => 'WHOLESALER', 
                    'H' => 'AGRICULTURAL', 
                    'I' => 'INDUSTRIAL_PROCESSING', 
                    'J' => 'DIRECT_PAY', 
                    'M' => 'EDUCATIONAL', 
                    'D' => 'FEDERAL_GOV', 
                    'K' => 'DIRECT_PAY', 
                    'L' => 'LESSOR'
                );
        
                $zamp_tax_codes = array(
                    'FEDERAL_GOV' => 'FEDERAL_GOV', 
                    'STATE_GOV' => 'STATE_GOV', 
                    'TRIBAL' => 'TRIBAL', 
                    'LOCAL_GOV' => 'LOCAL_GOV', 
                    'NON_PROFIT' => 'NON_PROFIT', 
                    'RELIGIOUS' => 'RELIGIOUS', 
                    'WHOLESALER' => 'WHOLESALER', 
                    'AGRICULTURAL' => 'AGRICULTURAL', 
                    'INDUSTRIAL_PROCESSING' => 'INDUSTRIAL_PROCESSING', 
                    'DIRECT_PAY' => 'DIRECT_PAY', 
                    'EDUCATIONAL' => 'EDUCATIONAL', 
                    'LESSOR' => 'LESSOR',
                    'SNAP' => 'SNAP',
                    'MEDICAL' => 'MEDICAL',
                    'DATA_CENTER' => 'DATA_CENTER',
                    'EDU_PRIVATE' => 'EDU_PRIVATE',
                    'EDU_PUBLIC' => 'EDU_PUBLIC'
                );
        
                $zamp_exempt_code = "";			

                $context = $event->getContext();
                $orderTransId = $event->getEntityId();

                $zamp_settings = $this->getZampSettings();

                $taxable_states = explode(',', $zamp_settings['taxable_states']);
                $bear_token = $zamp_settings['api_token'];
                $trans_enabled = $zamp_settings['transactions_enabled'];
                
                $ot_criteria = new Criteria([$orderTransId]);
                $transaction_order = $this->orderTransactionRepository->search($ot_criteria, $context)->first();

                $orderId = $transaction_order->getOrderId();

                $criteria = new Criteria([$orderId]);

                $criteria->addAssociation('lineItems');
                $criteria->addAssociation('deliveries');
                $criteria->addAssociation('deliveries.shippingOrderAddress');
                $criteria->addAssociation('deliveries.shippingOrderAddress.countryState');
                $criteria->addAssociation('orderCustomer');
                $criteria->addAssociation('orderCustomer.customer');
                $criteria->addAssociation('orderCustomer.customer.group');

                $order = $this->orderRepository->search($criteria, $context)->first();

                $dateTime = new DateTime('now', $timezone);

                $formattedTime = $dateTime->format('H:i:s');

                $this->logger->info("REFUND EVENT ORDER OBJECT RETRIEVED", [
                    'order' => json_decode(json_encode($order), true),
                    'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('H:i:s')
                ]);
                

                $order_number = $order->getOrderNumber();

                $customer_group_id = $order->getOrderCustomer()->getCustomer()->getGroup()->id;

                $customer_group_custom_fields = $order->getOrderCustomer()->getCustomer()->getGroup()->getCustomFields();

                if(count($customer_group_custom_fields) && isSet($customer_group_custom_fields['tax_exempt_code'])){
                    $zamp_exempt_code = $customer_group_custom_fields['tax_exempt_code'];
                }

                if(isset($zamp_exempt_code) && trim($zamp_exempt_code) != ""){
                    if(strlen(trim($zamp_exempt_code)) == 1){
                        $zamp_exempt_code = $ava_tax_exempt_codes[trim($zamp_exempt_code)];
                    } else {
                        $zamp_exempt_code = trim($zamp_exempt_code);
                    }
                }

                $suffix = "01";
                $new_suffix = "01";

                $transaction_info = $this->get_current_suffix($orderId);

                if(json_decode($transaction_info)->found){
                    $suffix = json_decode($transaction_info)->suffix;
                }

                $origin_id = "SW-" . $orderId . "-" . $suffix;

                $origin_transaction = new stdClass();

                $zamp_resp = new stdClass();

                $curl_origin = curl_init();

                $url_origin = 'https://api.zamp.com/transactions/' . $origin_id;

                curl_setopt_array($curl_origin, [
                    CURLOPT_URL => $url_origin,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $bear_token
                    ],
                ]);

                curl_setopt($curl_origin, CURLOPT_HEADER, true);

                $response_origin = curl_exec($curl_origin);

                header("Access-Control-Allow-Origin: *");

                $err_origin = curl_error($curl_origin);

                curl_close($curl_origin);

            

                if ($err_origin){

                    $dateTime = new DateTime('now', $timezone);

                    $formattedTime = $dateTime->format('H:i:s');

                    $this->logger->error("[$formattedTime] - ERROR IN REFUND EVENT RESPONSE FROM ZAMP ORIGINAL TRANSACTION RETRIEVAL", [
                        'error' => $err_origin
                    ]);
                    return;	

                } else {
                    if($response_origin){

                        $dateTime = new DateTime('now', $timezone);

                        $formattedTime = $dateTime->format('H:i:s');

                        $responseParts_origin = explode("\r\n\r\n", $response_origin, 2);
                        $httpResponseHeaders_origin = isset($responseParts_origin[0]) ? $responseParts_origin[0] : '';
                        $jsonResponseBody_origin = isset($responseParts_origin[1]) ? $responseParts_origin[1] : '';

                        $this->logger->info("[$formattedTime] - REFUND EVENT RESPONSE FROM ZAMP ORIGINAL TRANSACTION RETRIEVAL", [
                            'http_status' => strtok($httpResponseHeaders_origin, "\r\n"),
                            'response' => json_decode($jsonResponseBody_origin, true),
                            'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('H:i:s')
                        ]);

                        $origin_transaction = json_decode($jsonResponseBody_origin);
                        $refund_items_arr = array();         
                        $refund_json = new stdClass();

                        $refund_json->id = "REF-" . $origin_transaction->id;
                        $refund_json->name = "REF-" . $origin_transaction->name;
                        $refund_json->parentId = $origin_transaction->id;
                        $refund_json->transactedAt = date('Y-m-d H:i:s');
                        $refund_json->entity = $origin_transaction->entity;
                        $refund_json->purpose = $origin_transaction->purpose;
                        $refund_json->discount = $origin_transaction->discount;

                        foreach($origin_transaction->lineItems as $lineItem){
                            
                            $item_obj = new stdClass();        

                            $item_obj->quantity = $lineItem->quantity * -1;
                            $item_obj->id = $lineItem->id;
                            $item_obj->amount = (float) number_format($lineItem->amount, 2);
                            $item_obj->productName = $lineItem->productName;
                            $item_obj->productSku = $lineItem->productSku;
                            $item_obj->productTaxCode = $lineItem->productTaxCode;
                            array_push($refund_items_arr, $item_obj);   
                        }
                                                                        
                        $refund_json->subtotal = (float) number_format($origin_transaction->subtotal * -1, 2);
                        $refund_json->shippingHandling = (float) number_format($origin_transaction->shippingHandling * -1, 2);
                        $refund_json->total = (float) number_format($origin_transaction->total * -1, 2);
                        $refund_json->taxCollected = (float) number_format($origin_transaction->taxCollected * -1, 2);

                        $refund_json->shipToAddress = new stdClass();
                        $refund_json->shipToAddress->line1 = $origin_transaction->shipToAddress->line1;
                        $refund_json->shipToAddress->line2 = $origin_transaction->shipToAddress->line2;
                        $refund_json->shipToAddress->city = $origin_transaction->shipToAddress->city;
                        $refund_json->shipToAddress->state = $origin_transaction->shipToAddress->state;
                        $refund_json->shipToAddress->zip = $origin_transaction->shipToAddress->zip;
                        $refund_json->shipToAddress->country = $origin_transaction->shipToAddress->country;
                        $refund_json->lineItems = $refund_items_arr;

                        $dateTime = new DateTime('now', $timezone);

                        $formattedTime = $dateTime->format('H:i:s');

                        $this->logger->info("[$formattedTime] - REFUND EVENT REQUEST GENERATED FOR ZAMP TRANSACTION", [
                            'request' => $refund_json
                        ]);

                        $curl_whole_refund = curl_init();

                        $url_whole_refund = "https://api.zamp.com/transactions";
            
                        curl_setopt_array($curl_whole_refund, [
                            CURLOPT_URL => $url_whole_refund,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => json_encode($refund_json),
                            CURLOPT_HTTPHEADER => [
                            "Accept: application/json",
                            "Content-Type: application/json",
                            "Authorization: Bearer " . $bear_token
                            ],
                        ]);
            
                        curl_setopt($curl_whole_refund, CURLOPT_HEADER, true);
            
                        $response_whole_refund = curl_exec($curl_whole_refund);
            
                        header("Access-Control-Allow-Origin: *");

            
                        $err_whole_refund = curl_error($curl_whole_refund);
            
                        curl_close($curl_whole_refund);

                        $dateTime = new DateTime('now', $timezone);

                        $formattedTime = $dateTime->format('H:i:s');
            
                        if ($err_whole_refund){

                            $this->logger->error("[$formattedTime] - ERROR IN REFUND EVENT RESPONSE FROM ZAMP TRANSACTION", [
                                'error' => $err_whole_refund
                            ]);
                            return;
                        } else {
                            if($response_whole_refund){

                                $dateTime = new DateTime('now', $timezone);

                                $formattedTime = $dateTime->format('H:i:s');

                                $responseParts_whole_refund = explode("\r\n\r\n", $response_whole_refund, 2);
                                $httpResponseHeaders_whole_refund = isset($responseParts_whole_refund[0]) ? $responseParts_whole_refund[0] : '';
                                $jsonResponseBody_whole_refund = isset($responseParts_whole_refund[1]) ? $responseParts_whole_refund[1] : '';

                                $this->logger->info("[$formattedTime] - REFUND EVENT RESPONSE FROM ZAMP TRANSACTION", [
                                    'http_status' => strtok($httpResponseHeaders_whole_refund, "\r\n"),
                                    'response' => json_decode($jsonResponseBody_whole_refund, true),
                                    'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('H:i:s')
                                ]);

                                $zamp_trans = [
                                    'id' => json_decode($transaction_info)->id,
                                    'orderNumber' => $order_number,
                                    'status' => 'refunded'
                                ];

                                $this->zampTransactionsRepository->update([$zamp_trans], $event->getContext());
                            }
                        }
                    }
                }
            }    
        }         
    }

}
