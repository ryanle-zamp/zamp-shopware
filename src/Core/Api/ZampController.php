<?php

namespace ZampTax\Core\Api;

// Add these use statements at the top of your PHP file
use DateTime;
use DateTimeZone;
use stdClass;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Doctrine\DBAL\Connection;

#[Route(defaults: ['_routeScope' => ['api']])]
class ZampController extends AbstractController
{
	/**
	 *
	 * @var Connection
	 */
	private $connection;
	/**
	 *
	 * @var EntityRepository
	 */
    private $orderRepository;
	/**
	 *
	 * @var EntityRepository
	 */
	private $zampSettingsRepository;
	/**
	 *
	 * @var EntityRepository
	 */
	private $zampTransactionsRepository;

	public function __construct(Connection $connection, EntityRepository $orderRepository, EntityRepository $zampSettingsRepository, EntityRepository $zampTransactionsRepository)
	{
		$this->connection = $connection;
        $this->orderRepository = $orderRepository;
		$this->zampSettingsRepository = $zampSettingsRepository;
		$this->zampTransactionsRepository = $zampTransactionsRepository;
	}


	#[Route('/api/v1/_action/zamp-tax/test-api', name: 'api.zamp_tax.test_api', methods: ["POST", "GET"])]
	public function testApiToken(): JsonResponse
	{
		// Set the timezone to Central Standard Time
        $timezone = new DateTimeZone('UTC');

		$date = date('Y-m-d');

        // Set the timezone to Central Standard Time

		$token = $_POST['token'];
		$valid = "";

		$curl = curl_init();

		$test_data = array(
			"id" => "123",
			"name" => "INV-123",
			"transactedAt" => "2023-07-01T00:00:00.000Z",
			"isResale" => false,
			"discount" => 2,
			"subtotal" => 18,
			"shippingHandling" => 5,
			"total" => 23,
			"shipToAddress" => array(
				"line1" => "120 SW 10TH AVE",
				"line2" => null,
				"state" => "KS",
				"city" => "TOPEKA",
				"zip" => "66612"
			),
			"lineItems" => array(
				array(
					"id" => "LI-123",
					"amount" => 10,
					"quantity" => 2,
					"discount" => 0,
					"shippingHandling" => 0,
					"productName" => "The Ultimate Sampler",
					"productSku" => "SAMPLER-100",
					"productTaxCode" => "R_TPP_FOOD-BEVERAGE_HOME-CONSUMPTION"
				)				
			)				
		);

		$url = "https://api.zamp.com/calculations";

		curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($test_data),
            CURLOPT_HTTPHEADER => [
              "Accept: application/json",
              "Content-Type: application/json",
              "Authorization: Bearer " . $token
            ],
        ]);

        curl_setopt($curl, CURLOPT_HEADER, true);
        // curl_setopt($curl, CURLOPT_NOBODY, true);
    
        $response = curl_exec($curl);

        $err = curl_error($curl);

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // die(print_r($httpcode));

		// if($err){
		// 	die(print_r($err));
		// }

        curl_close($curl);

        if($httpcode == 200){
			// die(print("True!"));
            $valid = true;

            // Create a new DateTime object
            $dateTime = new DateTime('now', $timezone);
                    
            // Format the date and time as needed
            $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

            // Split the response into headers and body
            $responseParts = explode("\r\n\r\n", $response, 2);
            $httpResponseHeaders = isset($responseParts[0]) ? $responseParts[0] : '';
            $jsonResponseBody = isset($responseParts[1]) ? $responseParts[1] : '';

			$hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
			fwrite($hook_file, "\n\n");
			fwrite($hook_file, $formattedTime . " - TEST ZAMP API TOKEN RESPONSE - " . strtok($httpResponseHeaders, "\r\n") . "\n");
            fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody), JSON_PRETTY_PRINT));
			fclose($hook_file);
        } else {
			// die(print("False!"));
            $valid = false;

            // Create a new DateTime object
            $dateTime = new DateTime('now', $timezone);
                    
            // Format the date and time as needed
            $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

			// Split the response into headers and body
            $responseParts = explode("\r\n\r\n", $response, 2);
            $httpResponseHeaders = isset($responseParts[0]) ? $responseParts[0] : '';
            $jsonResponseBody = isset($responseParts[1]) ? $responseParts[1] : '';

			$hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
			fwrite($hook_file, "\n\n");
			fwrite($hook_file, $formattedTime . " - TEST ZAMP API TOKEN RESPONSE - " . strtok($httpResponseHeaders, "\r\n") . "\n");
            fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody), JSON_PRETTY_PRINT));
			fclose($hook_file);
        }

		$data = array(
			'valid' => $valid
		);

        return new JsonResponse($data);
	}

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


	#[Route('/api/v1/_action/zamp-tax/sync-order', name: 'api.zamp_tax.sync_order', methods: ["POST", "GET"])]
	public function syncHistoricalOrder(): JsonResponse
	{
        $timezone = new DateTimeZone('UTC');

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
		$order_id = $_POST['order_id'];
		$zamp_settings = $this->getZampSettings();

        if($zamp_settings['taxable_states']){
            $taxable_states = explode(',', $zamp_settings['taxable_states']);
        } else {
            $taxable_states = array();
        }

		$token = $zamp_settings['api_token'];
        $trans_enabled = $zamp_settings['transactions_enabled'];
		$context = Context::createDefaultContext();
		$criteria = new Criteria([$order_id]);

		$criteria->addAssociation('lineItems');
		$criteria->addAssociation('deliveries');
		$criteria->addAssociation('deliveries.shippingOrderAddress');
		$criteria->addAssociation('deliveries.shippingOrderAddress.countryState');
		$criteria->addAssociation('orderCustomer');
		$criteria->addAssociation('orderCustomer.customer');
		$criteria->addAssociation('orderCustomer.customer.group');

		$order = $this->orderRepository->search($criteria, $context)->first();

        // Create a new DateTime object
        $dateTime = new DateTime('now', $timezone);
                    
        // Format the date and time as needed
        $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

		$hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
		fwrite($hook_file, "\n\n");
		fwrite($hook_file, $formattedTime . " - HISTORY SYNC ORDER REPOSITORY FETCHED. \n");
		fwrite($hook_file, "ORDER: " . json_encode($order, JSON_PRETTY_PRINT));
        fclose($hook_file);

		$customer_group_id = $order->getOrderCustomer()->getCustomer()->getGroup()->id;

		$customer_group_custom_fields = $order->getOrderCustomer()->getCustomer()->getGroup()->getCustomFields();

		if(count($customer_group_custom_fields) && isSet($customer_group_custom_fields['tax_exempt_code'])){
			$zamp_exempt_code = $customer_group_custom_fields['tax_exempt_code'];
		} else {
            $zamp_exempt_code = "";
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

		// Format the date as "Y-m-d h:i:s"
		$formattedDate = $order->createdAt->format('Y-m-d H:i:s');

		if($trans_enabled && in_array($state, $taxable_states)){
			$zamp_items_arr = array();
			$suffix = "01";

			$subtotal = 0;
			$zamp_json = new stdClass();
			$zamp_json->id = "SW-" . $order_id . "-" . $suffix;
			$zamp_json->name = 'INV-' . $zamp_json->id;
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
					$item_obj->productTaxCode = $ptc && $ptc !== '' && (substr($ptc, 0, 5) == "R_TPP" || substr($ptc, 0, 5) == "R_SRV" || substr($ptc, 0, 5) == "R_DIG") ? $ptc : "R_TPP";
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

            // Create a new DateTime object
            $dateTime = new DateTime('now', $timezone);
                    
            // Format the date and time as needed
            $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

			$hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
			fwrite($hook_file, "\n\n");
			fwrite($hook_file, $formattedTime . " - HISTORICAL SYNC REQUEST FOR ZAMP GENERATED.\n");
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
				"Authorization: Bearer " . $token
				],
			]);

			curl_setopt($curl, CURLOPT_HEADER, true);
			
			$response = curl_exec($curl);
	
			header("Access-Control-Allow-Origin: *");
	
			// die(print_r(json_decode($response3)));
	
			$err = curl_error($curl);
	
			curl_close($curl);
	
			if ($err){
                // Create a new DateTime object
                $dateTime = new DateTime('now', $timezone);
                                                    
                // Format the date and time as needed
                $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

                $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                fwrite($hook_file, "\n\n");
                fwrite($hook_file, $formattedTime . " - ERROR CALCULATING HISTORICAL SYNC TRANSACTION.\n");
                fwrite($hook_file, "ERROR: " . $err);
                fclose($hook_file);
			} else {
				if($response){

                    // Split the response into headers and body
                    $responseParts = explode("\r\n\r\n", $response, 2);
                    $httpResponseHeaders = isset($responseParts[0]) ? $responseParts[0] : '';
                    $jsonResponseBody = isset($responseParts[1]) ? $responseParts[1] : '';

                    // Create a new DateTime object
                    $dateTime = new DateTime('now', $timezone);
                            
                    // Format the date and time as needed
                    $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

					$hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
					fwrite($hook_file, "\n\n");
					fwrite($hook_file, $formattedTime . " - HISTORICAL SYNC CALCULATION RESPONSE FROM ZAMP RECEIVED - " . strtok($httpResponseHeaders, "\r\n") . "\n");
                    fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody), JSON_PRETTY_PRINT));
					fclose($hook_file);

					$zamp_json->taxCollected = (float) number_format(json_decode($jsonResponseBody)->taxDue, 2);
					$zamp_json->total = (float) number_format($zamp_json->subtotal + $zamp_json->shippingHandling + $zamp_json->taxCollected, 2);

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
						"Authorization: Bearer " . $token
						],
					]);

					curl_setopt($curl2, CURLOPT_HEADER, true);

					$response2 = curl_exec($curl2);

					header("Access-Control-Allow-Origin: *");

					// die(print_r(json_decode($response3)));

					$err2 = curl_error($curl2);

					curl_close($curl2);

					$new_resp = new stdClass();

					if ($err2){

                        // Create a new DateTime object
                        $dateTime = new DateTime('now', $timezone);
                                    
                        // Format the date and time as needed
                        $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

						$hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
						fwrite($hook_file, "\n\n");
						fwrite($hook_file, $formattedTime . " - ERROR REPORTING HISTORICAL SYNC TRANSACTION.\n");
                        fwrite($hook_file, "ERROR: " . $err2);
						fclose($hook_file);

						$new_resp->completed = false;
					} else {
						if($response2){
							

                            // Split the response into headers and body
                            $responseParts2 = explode("\r\n\r\n", $response2, 2);
                            $httpResponseHeaders2 = isset($responseParts2[0]) ? $responseParts2[0] : '';
                            $jsonResponseBody2 = isset($responseParts2[1]) ? $responseParts2[1] : '';

                            $zamp_resp = json_decode($jsonResponseBody2);

                            // Create a new DateTime object
                            $dateTime = new DateTime('now', $timezone);
                                    
                            // Format the date and time as needed
                            $formattedTime = $dateTime->format('H:i:s'); // e.g., "10:00:00"

                            $hook_file = fopen("ZampTax-" . date('Y-m-d'). ".log", "a+");
                            fwrite($hook_file, "\n\n");
                            fwrite($hook_file, $formattedTime . " - HISTORICAL SYNC TRANSACTION RESPONSE FROM ZAMP RECEIVED - " . strtok($httpResponseHeaders2, "\r\n") . "\n");
                            fwrite($hook_file, "RESPONSE: " . json_encode(json_decode($jsonResponseBody2), JSON_PRETTY_PRINT));
                            fclose($hook_file);					

							if($zamp_resp->code == "CONFLICT" && $zamp_resp->message == "Transaction already exists"){
								$new_resp->status = "exists";
							} else if ($zamp_resp->id == $zamp_json->id){
								$new_resp->status = "completed";
							} else {
								$new_resp->status = "failed";
							}							
						}
					}

					return new JsonResponse($new_resp);
				}
			}
		}
	}
}