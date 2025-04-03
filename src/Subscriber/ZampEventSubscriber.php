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

        $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
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

				$timezone = new DateTimeZone('UTC');

				$dateTime = new DateTime('now', $timezone);
                                    
				$formattedTime = $dateTime->format('H:i:s');

				$hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
				fwrite($hook_file, "\n\n");
				fwrite($hook_file, $formattedTime . " - ORDER WRITTEN EVENT OCCURRED.\n");
				fwrite($hook_file, "EVENT PAYLOAD: " . json_encode($payload, JSON_PRETTY_PRINT));
				fclose($hook_file);
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
        if($this->getTaxProviderActiveStatus()){

            $timezone = new DateTimeZone('UTC');

            $zamp_settings = $this->getZampSettings();

            $bear_token = $zamp_settings['api_token'];

            foreach ($event->getWriteResults() as $result) {
                $payload = $result->getPayload();

                $context = $event->getContext();

                if (isset($payload['id'])) {

                    $versionId = $payload['versionId'];

                    foreach($event->getIds() as $i){
                        $orderId = $i;	                

                        $transaction_info = $this->get_trans_info($orderId);

                        if(json_decode($transaction_info)->found && json_decode($transaction_info)->version == $versionId){

                            $dateTime = new DateTime('now', $timezone);

                            $formattedTime = $dateTime->format('H:i:s');

                            $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                            fwrite($hook_file, "\n\n");
                            fwrite($hook_file, $formattedTime . " - ORDER DELETED EVENT OCCURRED.\n");
                            fwrite($hook_file, "EVENT PAYLOAD: " . json_encode($payload, JSON_PRETTY_PRINT));
                            fclose($hook_file);
                            
                            $suffix = json_decode($transaction_info)->suffix;

                            $status = json_decode($transaction_info)->status;

                            $dataId = json_decode($transaction_info)->id;

                            $origin_id = "SW-" . $orderId . "-" . $suffix;

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

                            $dateTime = new DateTime('now', $timezone);

                            $formattedTime = $dateTime->format('H:i:s');

                            if ($err_origin){
                                $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                fwrite($hook_file, "\n\n");
                                fwrite($hook_file, $formattedTime . " - ERROR IN DELETED ORDER EVENT RESPONSE FROM ZAMP ORIGINAL TRANSACTION RETRIEVAL.\n");
                                fwrite($hook_file, "ERROR: " . $err_origin);
                                fclose($hook_file);

                            } else {
                                if($response_origin){

                                    $dateTime = new DateTime('now', $timezone);

                                    $formattedTime = $dateTime->format('H:i:s');

                                    $responseParts_origin = explode("\r\n\r\n", $response_origin, 2);
                                    $httpResponseHeaders_origin = isset($responseParts_origin[0]) ? $responseParts_origin[0] : '';
                                    $jsonResponseBody_origin = isset($responseParts_origin[1]) ? $responseParts_origin[1] : '';

                                    $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                    fwrite($hook_file, "\n\n");
                                    fwrite($hook_file, "DELETED ORDER EVENT RESPONSE FROM ZAMP ORIGINAL TRANSACTION RETRIEVAL - " . strtok($httpResponseHeaders_origin, "\r\n") . "\n");
                                    fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody_origin), JSON_PRETTY_PRINT));
                                    fclose($hook_file);

                                    $curl_del = curl_init();

                                    $url_del = 'https://api.zamp.com/transactions/' . $origin_id;

                                    curl_setopt_array($curl_del, [
                                        CURLOPT_URL => $url_del,
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => "",
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 30,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => "DELETE",
                                        CURLOPT_HTTPHEADER => [
                                        "Accept: application/json",
                                        "Content-Type: application/json",
                                        "Authorization: Bearer " . $bear_token
                                        ],
                                    ]);

                                    curl_setopt($curl_del, CURLOPT_HEADER, true);

                                    $response_del = curl_exec($curl_del);

                                    header("Access-Control-Allow-Origin: *");

                                    $err_del = curl_error($curl_del);

                                    curl_close($curl_del);

                                    

                                    if ($err_del){

                                        $dateTime = new DateTime('now', $timezone);

                                        $formattedTime = $dateTime->format('H:i:s');

                                        $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                        fwrite($hook_file, "\n\n");
                                        fwrite($hook_file, $formattedTime . " - ERROR DELETING ORDER FROM ZAMP.\n");
                                        fwrite($hook_file, "ERROR: " . $err_del);
                                        fclose($hook_file);

                                    } else {
                                        if($response_del){

                                            $dateTime = new DateTime('now', $timezone);

                                            $formattedTime = $dateTime->format('H:i:s');

                                            $responseParts_del = explode("\r\n\r\n", $response_del, 2);
                                            $httpResponseHeaders_del = isset($responseParts_del[0]) ? $responseParts_del[0] : '';
                                            $jsonResponseBody_del = isset($responseParts_del[1]) ? $responseParts_del[1] : '';

                                            $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                            fwrite($hook_file, "\n\n");
                                            fwrite($hook_file, "ORDER DELETED RESPONSE FROM ZAMP - " . strtok($httpResponseHeaders_del, "\r\n") . "\n");
                                            fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody_del), JSON_PRETTY_PRINT));
                                            fclose($hook_file);
                                        }

                                        if($suffix !== "01"){
                                            $suffend = (int) $suffix;

                                            for($i = 1; $i < $suffend; $i++){
                                                if($i < 10){
                                                    $suffstring = "0" . (string) $i;
                                                } else {
                                                    $suffstring = (string) $i;
                                                }

                                                $next_id = "SW-" . $orderId . "-" . $suffstring;

                                                $curl_next = curl_init();

                                                $url_next = 'https://api.zamp.com/transactions/' . $next_id;

                                                curl_setopt_array($curl_next, [
                                                    CURLOPT_URL => $url_next,
                                                    CURLOPT_RETURNTRANSFER => true,
                                                    CURLOPT_ENCODING => "",
                                                    CURLOPT_MAXREDIRS => 10,
                                                    CURLOPT_TIMEOUT => 30,
                                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                    CURLOPT_CUSTOMREQUEST => "DELETE",
                                                    CURLOPT_HTTPHEADER => [
                                                    "Accept: application/json",
                                                    "Content-Type: application/json",
                                                    "Authorization: Bearer " . $bear_token
                                                    ],
                                                ]);

                                                curl_setopt($curl_next, CURLOPT_HEADER, true);

                                                $response_next = curl_exec($curl_next);

                                                header("Access-Control-Allow-Origin: *");

                                                $err_next = curl_error($curl_next);

                                                curl_close($curl_next);

                                                $dateTime = new DateTime('now', $timezone);

                                                $formattedTime = $dateTime->format('H:i:s');

                                                if ($err_next){
                                                    $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                                    fwrite($hook_file, "\n\n");
                                                    fwrite($hook_file, $formattedTime . " - ERROR IN DELETING PREVIOUS ORDER FROM ZAMP.\n");
                                                    fwrite($hook_file, "ERROR: " . $err_next);
                                                    fclose($hook_file);

                                                } else {
                                                    if($response_next){

                                                        $dateTime = new DateTime('now', $timezone);

                                                        $formattedTime = $dateTime->format('H:i:s');

                                                        $responseParts_next = explode("\r\n\r\n", $response_next, 2);
                                                        $httpResponseHeaders_next = isset($responseParts_next[0]) ? $responseParts_next[0] : '';
                                                        $jsonResponseBody_next = isset($responseParts_next[1]) ? $responseParts_next[1] : '';

                                                        $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                                        fwrite($hook_file, "\n\n");
                                                        fwrite($hook_file, "DELETED PREVIOUS ORDER RESPONSE FROM ZAMP - " . strtok($httpResponseHeaders_next, "\r\n") . "\n");
                                                        fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody_next), JSON_PRETTY_PRINT));
                                                        fclose($hook_file);                         
                                                    }
                                                }
                                            }
                                        }

                                        $zamp_trans = [
                                            'id' => $dataId
                                        ];

                                        $this->zampTransactionsRepository->delete([$zamp_trans], $context);
                                    }
                                }
                            }
                        }
                    }               
                }
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

                $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                fwrite($hook_file, "\n\n");
                fwrite($hook_file, $formattedTime . " - PAID EVENT ORDER OBJECT RETRIEVED.\n");
                fwrite($hook_file, "ORDER OBJECT: " . json_encode($order, JSON_PRETTY_PRINT));
                fclose($hook_file);	

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

                    $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                    fwrite($hook_file, "\n\n");
                    fwrite($hook_file, $formattedTime . " - PAID EVENT REQUEST FOR ZAMP CALCULATION GENERATED.\n"); 
                    fwrite($hook_file, "REQUEST: " . json_encode($zamp_json, JSON_PRETTY_PRINT));
                    fclose($hook_file);	
        
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
                        $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                        fwrite($hook_file, "\n\n");
                        fwrite($hook_file, $formattedTime . " - ERROR IN PAID EVENT REQUEST FOR ZAMP CALCULATION.\n");
                        fwrite($hook_file, "ERROR: " . $err);
                        fclose($hook_file);	
                    } else {
                        if($response){

                            $dateTime = new DateTime('now', $timezone);

                            $formattedTime = $dateTime->format('H:i:s');

                            $responseParts = explode("\r\n\r\n", $response, 2);
                            $httpResponseHeaders = isset($responseParts[0]) ? $responseParts[0] : '';
                            $jsonResponseBody = isset($responseParts[1]) ? $responseParts[1] : '';

                            $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                            fwrite($hook_file, "\n\n");
                            fwrite($hook_file, $formattedTime . " - PAID EVENT RESPONSE RECEIVED FROM ZAMP CALCULATION PRIOR TO CHANGE - " . strtok($httpResponseHeaders, "\r\n") . "\n");
                            fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody), JSON_PRETTY_PRINT));
                            fclose($hook_file);	

                            $zamp_json->taxCollected = (float) number_format(json_decode($jsonResponseBody)->taxDue, 2);
                            $zamp_json->total = (float) number_format($zamp_json->subtotal + $zamp_json->shippingHandling + $zamp_json->taxCollected, 2);

                            $dateTime = new DateTime('now', $timezone);

                            $formattedTime = $dateTime->format('H:i:s');

                            $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                            fwrite($hook_file, "\n\n");
                            fwrite($hook_file, $formattedTime . " - PAID EVENT REQUEST FROM ZAMP CALCULATION FOR ZAMP TRANSACTION GENERATED.\n");
                            fwrite($hook_file, "REQUEST: " . json_encode($zamp_json, JSON_PRETTY_PRINT));
                            fclose($hook_file);	
                            
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

                                $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                fwrite($hook_file, "\n\n");
                                fwrite($hook_file, $formattedTime . " - ERROR IN PAID EVENT RESPONSE FROM ZAMP TRANSACTION.\n");
                                fwrite($hook_file, "ERROR: " . $err2);
                                fclose($hook_file);
                            } else {
                                if($response2){
                                    $zamp_resp = json_decode($response2);

                                    $dateTime = new DateTime('now', $timezone);

                                    $formattedTime = $dateTime->format('H:i:s');

                                    $responseParts2 = explode("\r\n\r\n", $response2, 2);
                                    $httpResponseHeaders2 = isset($responseParts2[0]) ? $responseParts2[0] : '';
                                    $jsonResponseBody2 = isset($responseParts2[1]) ? $responseParts2[1] : '';

                                    $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                    fwrite($hook_file, "\n\n");
                                    fwrite($hook_file, $formattedTime . " - PAID EVENT RESPONSE RECEIVED FROM ZAMP TRANSACTION - " . strtok($httpResponseHeaders2, "\r\n") . "\n");
                                    fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody2), JSON_PRETTY_PRINT));
                                    fclose($hook_file);

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

                $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                fwrite($hook_file, "\n\n");
                fwrite($hook_file, $formattedTime . " - REFUND EVENT ORDER TRIGGER.\n");
                fclose($hook_file);
                
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

                $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                fwrite($hook_file, "\n\n");
                fwrite($hook_file, $formattedTime . " - REFUND EVENT ORDER OBJECT RETRIEVED.\n");
                fwrite($hook_file, "ORDER OBJECT: " . json_encode($order, JSON_PRETTY_PRINT));
                fclose($hook_file);

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

                    $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                    fwrite($hook_file, "\n\n");
                    fwrite($hook_file, $formattedTime . " - ERROR IN REFUND EVENT RESPONSE FROM ZAMP ORIGINAL TRANSACTION RETRIEVAL.\n");
                    fwrite($hook_file, "ERROR: " . $err_origin);
                    fclose($hook_file);

                } else {
                    if($response_origin){

                        $dateTime = new DateTime('now', $timezone);

                        $formattedTime = $dateTime->format('H:i:s');

                        $responseParts_origin = explode("\r\n\r\n", $response_origin, 2);
                        $httpResponseHeaders_origin = isset($responseParts_origin[0]) ? $responseParts_origin[0] : '';
                        $jsonResponseBody_origin = isset($responseParts_origin[1]) ? $responseParts_origin[1] : '';

                        $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                        fwrite($hook_file, "\n\n");
                        fwrite($hook_file, "REFUND EVENT RESPONSE FROM ZAMP ORIGINAL TRANSACTION RETRIEVAL - " . strtok($httpResponseHeaders_origin, "\r\n") . "\n");
                        fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody_origin), JSON_PRETTY_PRINT));
                        fclose($hook_file);

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

                        $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                        fwrite($hook_file, "\n\n");
                        fwrite($hook_file, $formattedTime . " - REFUND EVENT REQUEST GENERATED FOR ZAMP TRANSACTION.\n");
                        fwrite($hook_file, "REQUEST: " . json_encode($refund_json, JSON_PRETTY_PRINT));
                        fclose($hook_file);

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
                            $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                            fwrite($hook_file, "\n\n");
                            fwrite($hook_file, $formattedTime . " - ERROR IN REFUND EVENT RESPONSE FROM ZAMP TRANSACTION.\n");
                            fwrite($hook_file, "ERROR: " . $err_whole_refund);
                            fclose($hook_file);
                        } else {
                            if($response_whole_refund){

                                $dateTime = new DateTime('now', $timezone);

                                $formattedTime = $dateTime->format('H:i:s');

                                $responseParts_whole_refund = explode("\r\n\r\n", $response_whole_refund, 2);
                                $httpResponseHeaders_whole_refund = isset($responseParts_whole_refund[0]) ? $responseParts_whole_refund[0] : '';
                                $jsonResponseBody_whole_refund = isset($responseParts_whole_refund[1]) ? $responseParts_whole_refund[1] : '';

                                $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                                fwrite($hook_file, "\n\n");
                                fwrite($hook_file, "REFUND EVENT RESPONSE FROM ZAMP TRANSACTION - " . strtok($httpResponseHeaders_whole_refund, "\r\n") . "\n");
                                fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody_whole_refund), JSON_PRETTY_PRINT));
                                fclose($hook_file);

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
