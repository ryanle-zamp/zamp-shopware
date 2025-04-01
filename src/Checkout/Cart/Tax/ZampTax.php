<?php 

namespace ZampTax\Checkout\Cart\Tax;

use DateTime;
use DateTimeZone;
use Shopware\Core\Checkout\Cart\Cart;
use Doctrine\DBAL\Connection;
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

/**
 * Tax provider for Zamp tax calculation integration
 */
class ZampTax extends AbstractTaxProvider
{
    /** @var Connection */
    private $connection;
    
    /** @var SnippetService */
    private $snippetService;

    /**
     * @param Connection $connection Database connection
     * @param SnippetService $snippetService Snippet service for translations
     */
    public function __construct(Connection $connection, SnippetService $snippetService)
    {
        $this->connection = $connection;
        $this->snippetService = $snippetService;
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
     * Retrieves customer group tax exemption information
     *
     * @param string $customerGroupId The customer group ID
     * @return array|false Customer group data with tax exemption information
     */
    public function getZampCustomerTaxExemption($customerGroupId)
    {
        $sql =  '
            SELECT
                *
            FROM
                customer_group_translation
            WHERE
                customer_group_id = :customerGroupId
            LIMIT 1
        ';

        $result = $this->connection->fetchAssociative($sql, [
            'customerGroupId' => $customerGroupId
        ]);

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
     * Provides tax calculations for the cart items
     * 
     * @param Cart $cart The shopping cart
     * @param SalesChannelContext $context Sales channel context
     * @return TaxProviderResult Calculated taxes for line items and cart prices
     */
    public function provide(Cart $cart, SalesChannelContext $context): TaxProviderResult
    {
        $timezone = new DateTimeZone('UTC');

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

        $cartPriceTaxes = [];
        $lineItemTaxes = [];

        $taxable = false;

        $customer_group_id = $context->getCurrentCustomerGroup()->id;

        $customer_group_custom_fields = $context->getCurrentCustomerGroup()->getCustomFields();

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

        $zamp_settings = $this->getZampSettings();

        $taxable_states = explode(',', $zamp_settings['taxable_states']);
        $bear_token = $zamp_settings['api_token'];
        $calc_enabled = $zamp_settings['calculations_enabled'];

        if(!$calc_enabled){
            foreach ($cart->getLineItems() as $lineItem) {
                $taxRate = 0;
                $price = $lineItem->getPrice()->getTotalPrice();
                $tax = 0;
    
                $lineItemTaxes[$lineItem->getUniqueIdentifier()] = new CalculatedTaxCollection(
                    [
                        new CalculatedTax($tax, $taxRate, $price),
                    ]
                );
    
                $cartPriceTaxes[$lineItem->getUniqueIdentifier()] = new CalculatedTaxCollection(
                    [
                        new CalculatedTax($tax, $taxRate, $price),
                    ]
                );				
            }

            $dateTime = new DateTime('now', $timezone);
                
            $formattedTime = $dateTime->format('H:i:s');

            $hook_file = fopen(date('Y-m-d') . "_log.txt", "a+");
            fwrite($hook_file, "\n\n");
            fwrite($hook_file, $formattedTime . " - CART PRICE TAXES GENERATED W/O ZAMP CALCULATION.\n");
            fwrite($hook_file, "CART PRICE TAXES: " . json_encode($cartPriceTaxes, JSON_PRETTY_PRINT));
            fclose($hook_file);	
        } else {
            // Rest of the implementation...
            // (Code continues as before)
        }
        
        return new TaxProviderResult(
            $lineItemTaxes,
            $cartPriceTaxes
        );
    }
}