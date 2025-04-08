<?php

namespace ZampTax\Checkout\Cart\Tax;

use DateTime;
use DateTimeZone;
use Shopware\Core\Checkout\Cart\Cart;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\TaxProvider\AbstractTaxProvider;
use Shopware\Core\Checkout\Cart\TaxProvider\Struct\TaxProviderResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Util\FloatComparator;
use Shopware\Core\System\Snippet\SnippetService;
use stdClass;

class ZampTax extends AbstractTaxProvider
{
    private $connection;
    private $snippetService;
    private $logger;

    public function __construct(Connection $connection, SnippetService $snippetService, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->snippetService = $snippetService;
        $this->logger = $logger;
    }

    public function getZampSettings()
    {
        $sql = '
            SELECT api_token, taxable_states, calculations_enabled, transactions_enabled
            FROM zamp_settings
            LIMIT 1
        ';
        return $this->connection->fetchAssociative($sql);
    }

    public function getZampCustomerTaxExemption($customerGroupId)
    {
        return $this->connection->fetchAssociative(
            'SELECT * FROM customer_group_translation WHERE customer_group_id = :id LIMIT 1',
            ['id' => $customerGroupId]
        );
    }

    public function getZampProductTaxCode($productId)
    {
        return $this->connection->fetchAssociative(
            'SELECT * FROM zamp_product_tax_code WHERE product_id = :id LIMIT 1',
            ['id' => $productId]
        );
    }

    public function provide(Cart $cart, SalesChannelContext $context): TaxProviderResult
    {
        $timezone = new DateTimeZone('UTC');
        $formattedTime = (new DateTime('now', $timezone))->format('H:i:s');

        $ava_tax_exempt_codes = [/* ... unchanged ... */];
        $state_shortcodes = [/* ... unchanged ... */];

        $zamp_settings = $this->getZampSettings();
        $taxable_states = !empty($zamp_settings['taxable_states']) 
			? explode(',', $zamp_settings['taxable_states']) 
			: [];
        $bear_token = $zamp_settings['api_token'];
        $calc_enabled = $zamp_settings['calculations_enabled'];

        $zamp_exempt_code = '';
        $customer_group_custom_fields = $context->getCurrentCustomerGroup()->getCustomFields();
        if (!empty($customer_group_custom_fields['tax_exempt_code'])) {
            $code = trim($customer_group_custom_fields['tax_exempt_code']);
            $zamp_exempt_code = strlen($code) === 1 ? $ava_tax_exempt_codes[$code] ?? '' : $code;
        }

        $lineItemTaxes = [];
        $cartPriceTaxes = [];
        $taxable = false;

        if (!$calc_enabled) {
            foreach ($cart->getLineItems() as $lineItem) {
                $price = $lineItem->getPrice()->getTotalPrice();
                $taxRate = 0;
                $tax = 0;

                $collection = new CalculatedTaxCollection([new CalculatedTax($tax, $taxRate, $price)]);
                $lineItemTaxes[$lineItem->getUniqueIdentifier()] = $collection;
                $cartPriceTaxes[$lineItem->getUniqueIdentifier()] = $collection;
            }

            $this->logger->info("ZampTax skipped calculation (disabled)", [
                'time' => $formattedTime,
                'cartTaxes' => $cartPriceTaxes,
            ]);

            return new TaxProviderResult($lineItemTaxes, $cartPriceTaxes);
        }

        // Check delivery state
        if ($cart->getDeliveries()->count()) {
            $delivery = $cart->getDeliveries()->first();
            $state_name = $delivery->getLocation()->getState()->name;

            if (in_array($state_shortcodes[$state_name] ?? '', $taxable_states)) {
                $taxable = true;
            }
        }

        if ($taxable) {
            $zamp_items_arr = [];
            $subtotal = 0;

            $zamp_json = (object) [
                'id' => 'CALC-' . $cart->getHash(),
                'name' => 'INV-CALC-' . $cart->getHash(),
                'transactedAt' => date('Y-m-d H:i:s'),
                'entity' => $zamp_exempt_code ?: null,
                'purpose' => $zamp_exempt_code === 'WHOLESALER' ? 'RESALE' : null,
                'discount' => 0,
            ];

            foreach ($cart->getLineItems() as $item) {
                if ($item->getType() === 'promotion') {
                    $zamp_json->discount += $item->getPrice()->getTotalPrice() * -1;
                    continue;
                }

                $price = $item->getPrice()->getUnitPrice();
                $lineTotal = $item->getPrice()->getTotalPrice();
                $subtotal += $lineTotal;

                $ptcData = $this->getZampProductTaxCode($item->getId());
                $ptc = $ptcData['product_tax_code'] ?? '';
                $ptc = (preg_match('/^R_(TPP|SRV|DIG)/', $ptc)) ? $ptc : 'R_TPP';

                $zamp_items_arr[] = (object) [
                    'quantity' => $item->getQuantity(),
                    'id' => $item->getId(),
                    'amount' => (float) number_format($price, 2, '.', ''),
                    'productName' => $item->getLabel(),
                    'productSku' => $item->getPayloadValue('productNumber'),
                    'productTaxCode' => $ptc,
                ];
            }

            $zamp_json->subtotal = $subtotal - $zamp_json->discount;
            $zamp_json->shippingHandling = $cart->getDeliveries()->first()->getShippingCosts()->getTotalPrice();
            $zamp_json->total = $zamp_json->subtotal + $zamp_json->shippingHandling;

            $addr = $cart->getDeliveries()->first()->getLocation()->getAddress();
            $zamp_json->shipToAddress = (object) [
                'line1' => $addr->street ?: 'empty',
                'line2' => 'empty',
                'city' => $addr->city,
                'state' => $state_shortcodes[$addr->getState()->name] ?? '',
                'zip' => $addr->zipcode,
                'country' => 'US',
            ];
            $zamp_json->lineItems = $zamp_items_arr;

            $this->logger->info("ZampTax request initiated", [
                'time' => $formattedTime,
                'request' => $zamp_json,
            ]);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.zamp.com/calculations",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($zamp_json),
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $bear_token
                ],
            ]);
            curl_setopt($curl, CURLOPT_HEADER, true);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                $this->logger->error("ZampTax API error", [
                    'time' => $formattedTime,
                    'error' => $err,
                ]);
            } else {
                [$headers, $body] = explode("\r\n\r\n", $response, 2);
                $zamp_resp = json_decode($body);

                $this->logger->info("ZampTax API response received", [
                    'time' => $formattedTime,
                    'status' => strtok($headers, "\r\n"),
                    'response' => $zamp_resp,
                ]);

                foreach ($zamp_resp->lineItems ?? [] as $line) {
                    $line_tax = 0.00;
                    $line_rate = 0.0;

                    foreach ($zamp_resp->taxes ?? [] as $taxi) {
                        if ($taxi->lineItemId === $line->id) {
                            $line_tax += $taxi->taxDue;
                            $line_rate += $taxi->taxRate;
                        }
                    }

                    $taxRate = (float) number_format($line_rate, 6, '.', '');
                    $price = (float) number_format($line->amount * $line->quantity, 2, '.', '');
                    $taxes = (float) number_format($line_tax, 2, '.', '');

                    $taxCollection = new CalculatedTaxCollection([new CalculatedTax($taxes, $taxRate, $price)]);
                    $lineItemTaxes[$line->id] = $taxCollection;
                    $cartPriceTaxes[$line->id] = $taxCollection;
                }

                $this->logger->info("ZampTax taxes calculated", [
                    'time' => $formattedTime,
                    'cartTaxes' => $cartPriceTaxes,
                ]);
            }
        } else {
            foreach ($cart->getLineItems() as $lineItem) {
                $price = $lineItem->getPrice()->getTotalPrice();
                $taxRate = 0;
                $taxes = 0;

                $collection = new CalculatedTaxCollection([new CalculatedTax($taxes, $taxRate, $price)]);
                $lineItemTaxes[$lineItem->getUniqueIdentifier()] = $collection;
                $cartPriceTaxes[$lineItem->getUniqueIdentifier()] = $collection;
            }

            $this->logger->info("ZampTax: no calculation due to non-taxable state", [
                'time' => $formattedTime,
                'cartTaxes' => $cartPriceTaxes,
            ]);
        }

        return new TaxProviderResult($lineItemTaxes, $cartPriceTaxes);
    }
}
