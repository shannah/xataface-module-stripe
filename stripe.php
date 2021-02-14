<?php
/*
 * Xataface Depselect Module
 * Copyright (C) 2011  Steve Hannah <steve@weblite.ca>
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Library General Public License for more details.
 * 
 * You should have received a copy of the GNU Library General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301, USA.
 *
 */
 
/**
 * @brief The main depselect module class.  This loads all of the dependencies for the
 * 	module.
 *
 * Of note, this module depends on the XataJax module for the loading
 * of its javascripts.
 *
 */

 class modules_stripe {
	/**
	 * @brief The base URL to the depselect module.  This will be correct whether it is in the 
	 * application modules directory or the xataface modules directory.
	 *
	 * @see getBaseURL()
	 */
	private $baseURL = null;
    private $customer = -1;
    private $config;
    private $exchangeRates = null;
    private $client = null;
    private $products = null;

	
	
	
	/**
	 * @brief Initializes the depselect module and registers all of the event listener.
	 *
	 */
	function __construct(){
		$app = Dataface_Application::getInstance();
		
		
		// Now work on our dependencies
		$mt = Dataface_ModuleTool::getInstance();
		
        $app = Dataface_Application::getInstance();

        
		
		$isSandbox = false;
		$del = $app->getDelegate();
		if ($del and method_exists($del, 'stripe_isSandbox')) {
			$isSandbox = $del->stripe_isSandbox();
		} else {
			$httpHost = @$_SERVER['HTTP_HOST'];
			if ($httpHost == 'localhost' or strpos($httpHost, 'localhost:') === 0) {
				$isSandbox = true;
			}
		}
		
		
		
		if ($isSandbox) {
			if (!@$app->_conf['stripe__test']) {
				die("App is in sandbox mode.  You must add stripe__test and stripe_plans__test sections to your conf.ini");
			}
			$this->config = $app->_conf['stripe__test'];
	        if (isset($app->_conf['stripe_plans__test'])) {
	            $this->config['plans'] = $app->_conf['stripe_plans__test'];
	        }
		} else {
		    if (!@$app->_conf['stripe']) {
		    
		    
			    die("Stripe support requires that you add both a stripe and a stripe_plans section to your conf.ini file");
			}
			$this->config = $app->_conf['stripe'];
	        if (isset($app->_conf['stripe_plans'])) {
	            $this->config['plans'] = $app->_conf['stripe_plans'];
	        }
	        //print_r($this->config);exit;
		}
        if (@$app->_conf['stripe__common']) {
            $this->config = array_merge($this->config, $app->_conf['stripe__common']);
        }
        
        $this->addPaths();
        xf_script('stripe/stripe_global.js');
		
	}
    
    
    public function getConfig() {
    	return $this->config;
    }
	
	
	/**
	 * @brief Returns the base URL to this module's directory.  Useful for including
	 * Javascripts and CSS.
	 *
	 */
	public function getBaseURL(){
		if ( !isset($this->baseURL) ){
			$this->baseURL = Dataface_ModuleTool::getInstance()->getModuleURL(__FILE__);
		}
		return $this->baseURL;
	}
    
    public function addPaths() {
		$mod = $this;

		

		$jt = Dataface_JavascriptTool::getInstance();
		$jt->addPath(dirname(__FILE__).'/js', $mod->getBaseURL().'/js');

		$ct = Dataface_CSSTool::getInstance();
		$ct->addPath(dirname(__FILE__).'/css', $mod->getBaseURL().'/css');

		$context = array();

		df_register_skin('modules_stripe', dirname(__FILE__).'/templates');
    }
    
    public function getCustomer() {
        if (is_int($this->customer) and $this->customer == -1) {
            $auth = Dataface_AuthenticationTool::getInstance();
            $username = $auth->getLoggedInUserName();
            if ($username) {
                $this->customer = df_get_record('stripe_customers', ['username' => '=' . $username]);
                
            } else {
                $this->customer = null;
            }
        }
        
        return $this->customer;
    }
    
    public function getCustomerId() {
        $customer = $this->getCustomer();
        if ($customer) {
            return $customer->val('customer_id');
        }
        return null;
    }
    
    
    public function getCurrency() {
        $customer = $this->getCustomer();
        if ($customer) {
            return $customer->val('currency');
        }
        return null;
    }
    
    public function setStripeKey() {
        $app = Dataface_Application::getInstance();
        $config = $this->config;

        // Make sure the configuration file is good.
        if (!$config) {
        	die("No stripe key");
        	exit;
        }

        \Stripe\Stripe::setApiKey($config['stripe_secret_key']);
        
    }
    
    public function getUser() {
        $user = null;
        if (class_exists('Dataface_AuthenticationTool')) {
            $auth = Dataface_AuthenticationTool::getInstance();
        
            $user = $auth->getLoggedInUser();
        }
        return $user;
    }
    
    public function getPreferredCurrency() {
        $currency = $this->getCurrency();
        if ($currency) {
            return $currency;
        }
        $user = $this->getUser();
        
        $app = Dataface_Application::getInstance();
        
        $del = $app->getDelegate();
        if ($del and method_exists($del, 'getPreferredCurrency')) {
            return $del->getPreferredCurrency($user);
        }
        if ($user) {
            $currencyField = null;
            foreach ($user->table()->fields(false, true, true) as $field) {
                if (@$field['currency']) {
                    $currencyField = $field['name'];
                    break;
                }
                if (stripos($field['name'], 'currency') !== false) {
                    $currencyField = $field['name'];
                }
            }
            
            $out = $user->val($currencyField);
            if ($out) {
                return $out;
            }
        }
        
        
        
        if (@$app->_conf['default_currency']) {
            return $app->_conf['default_currency'];
        }
        return 'USD';
        
    }
    
    public function getUserRegion() {
        $user = $this->getUser();
        
        $app = Dataface_Application::getInstance();
        
        $del = $app->getDelegate();
        if ($del and method_exists($del, 'getUserRegion')) {
            return $del->getUserRegion($user);
        }
        if ($user) {
            $regionField = null;
            foreach ($user->table()->fields(false, true, true) as $field) {
                if (@$field['region']) {
                    $regionField = $field['name'];
                    break;
                }
                if (stripos($field['name'], 'region') !== false) {
                    $regionField = $field['name'];
                }
            }
            
            $out = $user->val($regionField);
            if ($out) {
                return $out;
            }
        }
        
        
        
        if (@$app->_conf['default_region']) {
            return $app->_conf['default_region'];
        }
        return 0;
    }
    
    private function loadProducts() {
        if ($this->products === null) {
            $this->products = [];
            $this->prices = [];
            import('xf/db/Database.php');
            $db = new xf\db\Database(df_db());
            $res = $db->query("select pr.*, p.data as product_data from stripe_prices pr inner join stripe_products p on pr.product=p.id");
            while ($o = xf_db_fetch_object($res)) {
                $this->products[$o->product] = json_decode($o->product_data, true);
                $this->prices[$o->id] = json_decode($o->data, true);
            }
            xf_db_free_result($res);
        }
    }
    
    
    public function getPlans() {
        $out = [];
        
        if (@$this->config['plans']) {

            $currency = $this->getPreferredCurrency();
            $this->loadProducts();
            $plans = [];

            foreach ($this->config['plans'] as $priceId => $priceLabel) {
                $price = @$this->prices[$priceId];

                if (!$price) {
                    continue;
                }
                if (strcasecmp($price['currency'], $currency) !== 0) {
                    continue;
                }

                
                $productId = $price['product'];
                $product = $this->products[$productId];
                if (!$product) {
                    continue;
                }
                if (!isset($plans[$productId])) {
                    $plan = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'description' => $product['description'],
                        'images' => $product['images'],
                        'prices' => []
                    ];
                    $plans[$productId] =& $plan;
                    
                } else {
                    $plan =& $plans[$productId];
                }
                $price['label'] = $priceLabel;
                $plan['prices'][] = $price;
                unset($plan);
            }
            //print_r($plans);
            
            return $plans;
        }
        
        if (@$this->config['plans_table']) {
            $plansTable = Dataface_Table::loadTable($this->config['plans_table']);
            
            $priceIdField = null;
            $priceLabelField = null;
            $this->loadProducts();


            foreach ($plansTable->fields(false, true, true) as $field) {
                if (@$field['stripe.priceId']) {
                    $priceIdField = $field['name'];

                }
                if (@$field['stripe.priceLabel']) {
                    $priceLabelField = $field['name'];
                }

            }
            
            if (!$priceIdField or !$priceLabelField) {
                throw new Exception("No priceId field found.  Ensure that the table ".$plansTable->tablename." table includes a field that has stripe.priceId=1 in in the fields.ini file.");
            }
            $q = [];
            
            $currency = $this->getPreferredCurrency();
            $_plans = df_get_records_array($plansTable->tablename, $q);
            $plans = [];
            foreach ($_plans as $_plan) {
                if (!$_plan->checkPermission('purchase')) {
                    continue;
                }
                $priceId = $_plan->val($priceIdField);
                if (!$priceId) {
                    continue;
                }
                $price = @$this->prices[$priceId];
                if (!$price) {
                    continue;
                }
                if ($price['currency'] != $currency) {
                    continue;
                }

                
                $productId = $price['product'];
                $product = $this->products[$productId];
                if (!$product) {
                    continue;
                }
                if (!isset($plans[$productId])) {
                    $plan = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'description' => $product['description'],
                        'images' => $product['images'],
                        'prices' => []
                    ];
                    $plans[$productId] = $plan;
                    
                } else {
                    $plan = $plans[$productId];
                }
                $price['label'] = $_plan->val($priceLabelField);
                $plan['prices'][] = $price;

                
            }
            
            return $plans;
            
        } else {
            throw new Exception("Configuration error.  Ensure that there is a plans_table in the stripe_config section of the conf.ini file");
        }
    }
    
    /**
     * Loads exchange rates from the https://api.exchangeratesapi.io 
     * 
     * @param string $baseCurrency The base currency
     * @param string $url The URL to the API to use, including query parameters.
     * @return array reference to the $exchangeRates array after filling it with new data.
     */
    private function loadExchangeRatesFromAPI($baseCurrency, $url) {
        $res = file_get_contents($url);
        if ($res) {
            $data = json_decode($res, true);
            if (is_array($data) and @$data['rates']) {
                import('xf/db/Database.php');
                $db = new xf\db\Database(df_db());
                foreach ($data['rates'] as $target => $conversion) {
                    $db->query("replace into stripe_exchange_rates (base_currency, target_currency, conversion, last_updated) VALUES (:from, :to, :conversion, NOW())", (object)[
                        'from' => $baseCurrency,
                        'to' => $target,
                        'conversion' => $conversion
                    ]);
                        
                    $this->exchangeRates[$baseCurrency.':'.$target] = floatval($conversion);
                }
            }
        }
        return $this->exchangeRates;
    }
    
    /**
     * Gets the exchange rate from one currency to another.
     * @param string $from The base currency.
     * @param string $to The target currency
     * @return float The Exchange rate.
     */
    public function getExchangeRate($from, $to) {
        if ($this->exchangeRates === null) {
            // First try to load exchange reates from the table
            $res = xf_db_query("select * from stripe_exchange_rates where last_updated > (CURDATE() - INTERVAL 1 DAY)", df_db());
            $rates = [];
            while ($o = xf_db_fetch_object($res)) {
                $rates[$o->base_currency.':'.$o->target_currency] = floatval($o->conversion);
            }
            xf_db_free_result($res);
            if (count($rates) === 0) {
                // If the table was empty, use this API to fetch exchange rates.
                $rates = $this->loadExchangeRatesFromAPI($from, 'https://api.exchangeratesapi.io/latest?base='.urlencode(strtoupper($from)));
            }
            
            $this->exchangeRates = $rates;
        }
        
        if (!@$this->exchangeRates[$from.':'.$to]) {
            // We don't have this specific exchange rate.
            // Fetch this specific pair from the API
            $this->loadExchangeRatesFromAPI($from, 'https://api.exchangeratesapi.io/latest?base='.urlencode(strtoupper($from)).'&symbols='.urlencode(strtoupper($to)));
        }
        
        if (!@$this->exchangeRates or !$this->exchangeRates[$from.':'.$to]) {
            
            throw new Exception("Failed to get exchange rate from ".$from." to ".$to);
        }
        return $this->exchangeRates[$from.':'.$to];
    }
    
    /**
     * Converts an amount from one currency to another.  Always rounds up to .99 of the current
     * base amount.  E.g. If the actual exchange rate would result in a converted amount like $4.32,
     * this will return $4.99
     * 
     * @return float A converted amount
     */
    public function convertCurrency($amount, $amountCurrency, $targetCurrency) {
        $rate = $this->getExchangeRate($amountCurrency, $targetCurrency);
        $targetAmount = $amount * $rate;
        return floatval(intval($targetAmount).'.99');
    }
    
    public function refreshProducts($refreshPricesAlso = false) {
        $products = $this->client()->products->all([]);
        if (!$products) {
            error_log("Failed to refresh stripe products");
            return false;
        }
        import('xf/db/Database.php');
        $db = new xf\db\Database(df_db());
        $db->query("delete from stripe_products where 1");
        foreach ($products['data'] as $product) {
            
            $db->query("replace into stripe_products (id, active, name, data, updated) values (:id, :active, :name, :data, :updated)", (object)[
                'id' => $product['id'],
                'active' => $product['active'],
                'name' => $product['name'],
                'data' => json_encode($product),
                'updated' => $product['updated']
            ]);
            if ($refreshPricesAlso) {
                if (!$this->refreshPrices($product['id'])) {
                    return false;
                }
            }
            
        }
        return true;
        
    }
    
    
    public function refreshPrices($productId) {
        $prices = $this->client()->prices->all(['product' => $productId]);
        if (!$prices) {
            error_log("Failed to refresh stripe prices");
            return false;
        }
        import('xf/db/Database.php');
        $db = new xf\db\Database(df_db());
        $db->query("delete from stripe_prices where product=:product", ['product' => $productId]);
        foreach ($prices['data'] as $price) {
            
            $db->query("replace into stripe_prices (id, product, currency, active, unit_amount, unit_amount_decimal, data) values (:id, :product, :currency, :active, :unit_amount, :unit_amount_decimal, :data)", (object)[
                'id' => $price['id'],
                'product' => $price['product'],
                'currency' => $price['currency'],
                'active' => $price['active'],
                'unit_amount' => $price['unit_amount'],
                'unit_amount_decimal' => $price['unit_amount_decimal'],
                'data' => json_encode($price)
            ]);
        }
        return true;
        
    }
    
    public function client() {
        if ($this->client === null) {
            $app = Dataface_Application::getInstance();
            $config = $this->config;

            // Make sure the configuration file is good.
            if (!$config) {
            	die("No stripe key");
            	exit;
            }

            \Stripe\Stripe::setApiKey($config['stripe_secret_key']);
            $stripe = new \Stripe\StripeClient(
              $config['stripe_secret_key']
            );
            $this->client = $stripe;
        }
        return $this->client;
        
    }
    
    
    
}

function xf_stripe() {
    return Dataface_ModuleTool::getInstance()->loadModule('modules_stripe');
}
