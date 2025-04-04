<?php

namespace ZampTax\Core\Api;

// Add these use statements at the top of your PHP file
use DateTime;
use DateTimeZone;
use stdClass;
use Psr\Log\LoggerInterface;
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
	 /** @var Connection Database connection service */
	private $connection;

	/** @var EntityRepository Order repository for accessing order data */
    private $orderRepository;

	/** @var EntityRepository Zamp settings repository for accessing configuration */
	private $zampSettingsRepository;

	 /** @var EntityRepository Zamp transactions repository for tracking sync history */
	private $zampTransactionsRepository;

	/** @var LoggerInterface Zamp Logging functionality */

	/**
     * Controller constructor
     * 
     * @param Connection $connection Database connection
     * @param EntityRepository $orderRepository Order entity repository
     * @param EntityRepository $zampSettingsRepository Zamp settings repository
     * @param EntityRepository $zampTransactionsRepository Zamp transactions repository
	 * @param LoggerInterface $logger Logger Interface for Zamp Tax
     */
	public function __construct(Connection $connection, EntityRepository $orderRepository, EntityRepository $zampSettingsRepository, EntityRepository $zampTransactionsRepository, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->orderRepository = $orderRepository;
        $this->zampSettingsRepository = $zampSettingsRepository;
        $this->zampTransactionsRepository = $zampTransactionsRepository;
		$this->logger = $logger;
    }

	/**
     * Tests if the provided Zamp API token is valid
     * 
     * @return JsonResponse Response indicating if the token is valid
     */
	#[Route('/api/v1/_action/zamp-tax/test-api', name: 'api.zamp_tax.test_api', methods: ["POST", "GET"])]
	public function testApiToken(): JsonResponse
	{
        $timezone = new DateTimeZone('UTC');

		$date = date('Y-m-d');

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
    
        $response = curl_exec($curl);

        $err = curl_error($curl);

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($httpcode == 200){
            $valid = true;

            $dateTime = new DateTime('now', $timezone);

            $formattedTime = $dateTime->format('H:i:s');

            $responseParts = explode("\r\n\r\n", $response, 2);
            $httpResponseHeaders = isset($responseParts[0]) ? $responseParts[0] : '';
            $jsonResponseBody = isset($responseParts[1]) ? $responseParts[1] : '';

			$this->logger->info("{$formattedTime} - TEST ZAMP API TOKEN RESPONSE - " . strtok($httpResponseHeaders, "\r\n"), [
                'response' => json_decode($jsonResponseBody, true)
            ]);

        } else {

            $valid = false;

            $dateTime = new DateTime('now', $timezone);

            $formattedTime = $dateTime->format('H:i:s');

            $responseParts = explode("\r\n\r\n", $response, 2);
            $httpResponseHeaders = isset($responseParts[0]) ? $responseParts[0] : '';
            $jsonResponseBody = isset($responseParts[1]) ? $responseParts[1] : '';
			$decodedResponse = json_decode($jsonResponseBody, true);

			$this->logger->warning("{$formattedTime} - TEST ZAMP API TOKEN RESPONSE - " . strtok($httpResponseHeaders, "\r\n"), [
                'response' => $decodedResponse
            ]);
        }

		$data = array(
			'valid' => $valid
		);

        return new JsonResponse($data);
	}

	/**
     * Retrieves Zamp plugin settings from the database
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


	#[Route('/api/v1/_action/zamp-tax/sync-order', name: 'api.zamp_tax.sync_order', methods: ["POST", "GET"])]
	public function syncHistoricalOrder(): JsonResponse
	{
		$timezone = new DateTimeZone('UTC');
		$formattedTime = (new DateTime('now', $timezone))->format('H:i:s');

		$state_shortcodes = [
			"Alabama" => "AL", "Alaska" => "AK", "Arizona" => "AZ", "Arkansas" => "AR",
			"California" => "CA", "Colorado" => "CO", "Connecticut" => "CT", "Delaware" => "DE",
			"District of Columbia" => "DC", "Florida" => "FL", "Georgia" => "GA", "Hawaii" => "HI",
			"Idaho" => "ID", "Illinois"=> "IL", "Indiana" => "IN", "Iowa" => "IA", "Kansas" => "KS",
			"Kentucky" => "KY", "Louisiana" => "LA", "Maine" => "ME", "Maryland" => "MD",
			"Massachusetts" => "MA", "Michigan" => "MI", "Minnesota" => "MN", "Mississippi" => "MS",
			"Missouri" => "MO", "Montana" => "MT", "Nebraska" => "NE", "Nevada" => "NV",
			"New Hampshire" => "NH", "New Jersey" => "NJ", "New Mexico" => "NM", "New York" => "NY",
			"North Carolina" => "NC", "North Dakota" => "ND", "Ohio" => "OH", "Oklahoma" => "OK",
			"Oregon" => "OR", "Pennsylvania" => "PA", "Puerto Rico" => "PR", "Rhode Island" => "RI",
			"South Carolina" => "SC", "South Dakota" => "SD", "Tennessee" => "TN", "Texas" => "TX",
			"Utah" => "UT", "Vermont" => "VT", "Virginia" => "VA", "Washington" => "WA",
			"West Virginia" => "WV", "Wisconsin" => "WI", "Wyoming" => "WY"
		];

		$ava_tax_exempt_codes = [
			'A' => 'FEDERAL_GOV', 'B' => 'STATE_GOV', 'C' => 'TRIBAL', 'N' => 'LOCAL_GOV',
			'E' => 'NON_PROFIT', 'F' => 'RELIGIOUS', 'G' => 'WHOLESALER', 'H' => 'AGRICULTURAL',
			'I' => 'INDUSTRIAL_PROCESSING', 'J' => 'DIRECT_PAY', 'M' => 'EDUCATIONAL',
			'D' => 'FEDERAL_GOV', 'K' => 'DIRECT_PAY', 'L' => 'LESSOR'
		];

		$order_id = $_POST['order_id'] ?? null;
		$zamp_settings = $this->getZampSettings();
		$taxable_states = $zamp_settings['taxable_states'] ? explode(',', $zamp_settings['taxable_states']) : [];
		$token = $zamp_settings['api_token'];
		$trans_enabled = $zamp_settings['transactions_enabled'];

		$context = Context::createDefaultContext();
		$criteria = new Criteria([$order_id]);
		$criteria->addAssociation('lineItems')
				->addAssociation('deliveries')
				->addAssociation('deliveries.shippingOrderAddress')
				->addAssociation('deliveries.shippingOrderAddress.countryState')
				->addAssociation('orderCustomer')
				->addAssociation('orderCustomer.customer')
				->addAssociation('orderCustomer.customer.group');

		$order = $this->orderRepository->search($criteria, $context)->first();

		$this->logger->info("{$formattedTime} - HISTORY SYNC ORDER REPOSITORY FETCHED", [
			'order_id' => $order_id,
			'order' => $order
		]);

		$zamp_exempt_code = '';
		$groupFields = $order->getOrderCustomer()->getCustomer()->getGroup()->getCustomFields();
		if (!empty($groupFields['tax_exempt_code'])) {
			$code = trim($groupFields['tax_exempt_code']);
			$zamp_exempt_code = strlen($code) === 1 ? $ava_tax_exempt_codes[$code] ?? $code : $code;
		}

		$delivery = $order->getDeliveries()->first();
		$shippingAddress = $delivery?->getShippingOrderAddress();
		$street = $shippingAddress?->getStreet() ?? '';
		$city = $shippingAddress?->getCity() ?? '';
		$zip = $shippingAddress?->getZipcode() ?? '';
		$state = isset($shippingAddress) ? $state_shortcodes[$shippingAddress->getCountryState()->getName()] : '';

		$formattedDate = $order->createdAt->format('Y-m-d H:i:s');

		if ($trans_enabled && in_array($state, $taxable_states)) {
			$zamp_items_arr = [];
			$subtotal = 0;
			$zamp_json = new \stdClass();
			$zamp_json->id = "SW-{$order_id}-01";
			$zamp_json->name = 'INV-' . $zamp_json->id;
			$zamp_json->transactedAt = $formattedDate;
			$zamp_json->entity = $zamp_exempt_code ?: null;
			$zamp_json->purpose = $zamp_exempt_code === "WHOLESALER" ? "RESALE" : null;
			$zamp_json->discount = 0;

			foreach ($order->getLineItems() as $item) {
				if ($item->getType() === 'promotion') {
					$zamp_json->discount += $item->getPrice()->getTotalPrice() * -1;
					continue;
				}

				$item_obj = new \stdClass();
				$unit_price = $item->unitPrice;
				$total_price = $item->totalPrice;
				$subtotal += (float) number_format($total_price, 2);

				$item_obj->quantity = $item->quantity;
				$item_obj->id = $item->id;
				$item_obj->amount = (float) number_format($unit_price, 2);
				$item_obj->productName = $item->label;
				$item_obj->productSku = $item->payload['productNumber'];
				$ptc = $this->getZampProductTaxCode($item_obj->id);
				$item_obj->productTaxCode = ($ptc && $ptc['product_tax_code'] && preg_match('/^R_(TPP|SRV|DIG)/', $ptc['product_tax_code']))
					? $ptc['product_tax_code']
					: 'R_TPP';

				$zamp_items_arr[] = $item_obj;
			}

			$zamp_json->subtotal = $subtotal - $zamp_json->discount;
			$zamp_json->shippingHandling = $order->shippingCosts->getTotalPrice();
			$zamp_json->total = $zamp_json->subtotal + $zamp_json->shippingHandling;

			$shipToAddress = new \stdClass();
			$shipToAddress->line1 = $street;
			$shipToAddress->line2 = 'empty';
			$shipToAddress->city = $city;
			$shipToAddress->state = $state;
			$shipToAddress->country = 'US';
			$shipToAddress->zip = $zip;
			$zamp_json->shipToAddress = $shipToAddress;
			$zamp_json->lineItems = $zamp_items_arr;

			$this->logger->info("{$formattedTime} - HISTORICAL SYNC REQUEST FOR ZAMP GENERATED", [
				'request' => $zamp_json
			]);

			// API Call
			$zamp_obj = json_encode($zamp_json);
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => "https://api.zamp.com/calculations",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $zamp_obj,
				CURLOPT_HTTPHEADER => [
					"Accept: application/json",
					"Content-Type: application/json",
					"Authorization: Bearer $token"
				],
				CURLOPT_HEADER => true
			]);
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);

			if ($err) {
				$this->logger->error("{$formattedTime} - ERROR CALCULATING HISTORICAL SYNC TRANSACTION", [
					'error' => $err
				]);
			} else {
				$responseParts = explode("\r\n\r\n", $response, 2);
				$jsonResponseBody = $responseParts[1] ?? '';
				$httpHeader = strtok($responseParts[0] ?? '', "\r\n");

				$this->logger->info("{$formattedTime} - HISTORICAL SYNC CALCULATION RESPONSE FROM ZAMP RECEIVED - $httpHeader", [
					'response' => json_decode($jsonResponseBody, true)
				]);

				$zamp_json->taxCollected = (float) number_format(json_decode($jsonResponseBody)->taxDue ?? 0, 2);
				$zamp_json->total = (float) number_format($zamp_json->subtotal + $zamp_json->shippingHandling + $zamp_json->taxCollected, 2);

				// Transaction Push
				$curl2 = curl_init();
				curl_setopt_array($curl2, [
					CURLOPT_URL => "https://api.zamp.com/transactions",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => json_encode($zamp_json),
					CURLOPT_HTTPHEADER => [
						"Accept: application/json",
						"Content-Type: application/json",
						"Authorization: Bearer $token"
					],
					CURLOPT_HEADER => true
				]);
				$response2 = curl_exec($curl2);
				$err2 = curl_error($curl2);
				curl_close($curl2);

				$new_resp = new \stdClass();
				if ($err2) {
					$this->logger->error("{$formattedTime} - ERROR REPORTING HISTORICAL SYNC TRANSACTION", [
						'error' => $err2
					]);
					$new_resp->completed = false;
				} else {
					$parts2 = explode("\r\n\r\n", $response2, 2);
					$body2 = $parts2[1] ?? '';
					$header2 = strtok($parts2[0] ?? '', "\r\n");

					$zamp_resp = json_decode($body2);

					$this->logger->info("{$formattedTime} - HISTORICAL SYNC TRANSACTION RESPONSE FROM ZAMP RECEIVED - $header2", [
						'response' => $zamp_resp
					]);

					$new_resp->status = match (true) {
						$zamp_resp->code === "CONFLICT" && $zamp_resp->message === "Transaction already exists" => "exists",
						$zamp_resp->id === $zamp_json->id => "completed",
						default => "failed"
					};
				}

				return new JsonResponse($new_resp);
			}
		}

		return new JsonResponse(['success' => false, 'message' => 'Operation could not be completed']);
	}

}