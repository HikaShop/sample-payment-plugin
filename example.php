<?php
/*
 * A payment plugin called "example". This is the main file of the plugin.
 */

// You need to extend from the hikashopPaymentPlugin class which already define lots of functions in order to simplify your work
class plgHikashoppaymentExample extends hikashopPaymentPlugin
{
	//List of the plugin's accepted currencies. The plugin won't appear on the checkout if the current currency is not in that list. You can remove that attribute if you want your payment plugin to display for all the currencies
	var $accepted_currencies = array( "EUR", "USD" );
	
	// Multiple plugin configurations. It should usually be set to true
	var $multiple = true;
	
	//Payment plugin name (the name of the PHP file)
	var $name = 'example';

	// This array contains the specific configuration needed (Back end > payment plugin edition), depending of the plugin requirements.
	// They will vary based on your needs for the integration with your payment gateway.
	// The first parameter is the name of the field. In upper case for a translation key.
	// The available types (second parameter) are: input (an input field), html (when you want to display some custom HTML to the shop owner), textarea (when you want the shop owner to write a bit more than in an input field), big-textarea (when you want the shop owner to write a lot more than in an input field), boolean (for a yes/no choice), checkbox (for checkbox selection), list (for dropdown selection) , orderstatus (to be able to select between the available order statuses)
	// The third parameter is the default value.
	var $pluginConfig = array(
		// User's identifier on the payment platform
		'identifier' => array("Identifier",'input'),
		// User's password on the payment platform
		'password' => array("HIKA_PASSWORD",'input'),
		// To allow (or not) notifications from the payment platform. The plugin can only work if notifications are allowed
		'notification' => array('ALLOW_NOTIFICATIONS_FROM_X', 'boolean','0'),
		// Platform payment's url
		'payment_url' => array("Payment URL",'input'),
		// Write some things on the debug file
		'debug' => array('DEBUG', 'boolean','0'),
		// The URL where the user is redirected after a fail during the payment process
		'cancel_url' => array('CANCEL_URL_DEFINE','html',''),
		// The URL where the user is redirected after the payment is done on the payment gateway. It's a pre determined URL that has to be given to the payment gateway
		'return_url_gateway' => array('RETURN_URL_DEFINE', 'html',''),
		// The URL where the user is redirected by HikaShop after the payment is done ; "Thank you for purchase" page
		'return_url' => array('RETURN_URL', 'input'),
		// The URL where the payment platform the user about the payment (fail or success)
		'notify_url' => array('NOTIFY_URL_DEFINE','html',''),
		// Invalid status for order in case of problem during the payment process
		'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
		// Valid status for order if the payment has been done well
		'verified_status' => array('VERIFIED_STATUS', 'orderstatus')
	);

	/**
	 * The constructor is optional if you don't need to initialize some parameters of some fields of the configuration and not that it can also be done in the getPaymentDefaultValues function as you will see later on
	 */
	function __construct(&$subject, $config)
	{
		$this->pluginConfig['notification'][0] =  JText::sprintf('ALLOW_NOTIFICATIONS_FROM_X','Example');
		
		// This is the cancel URL of HikaShop that should be given to the payment gateway so that it can redirect to it when the user cancel the payment on the payment gateway page. That URL will automatically cancel the order of the user and redirect him to the checkout so that he can choose another payment method
		$this->pluginConfig['cancel_url'][2] = HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=order&task=cancel_order";
		
		// This is the "thank you" or "return" URL of HikaShop that should be given to the payment gateway so that it can redirect to it when the payment of the user is valid. That URL will reinit some variables in the session like the cart and will then automatically redirect to the "return_url" parameter
		$this->pluginConfig['return_url'][2] = HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=checkout&task=after_end";
		
		// This is the "notification" URL of HikaShop that should be given to the payment gateway so that it can send a request to that URL in order to tell HikaShop that the payment has been done (sometimes the payment gateway doesn't do that and passes the information to the return URL, in which case you need to use that notification URL as return URL and redirect the user to the HikaShop return URL at the end of the onPaymentNotification function)
		$this->pluginConfig['notify_url'][2] = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&amp;notif_payment='.$this->name.'&tmpl=component';
		
		return parent::__construct($subject, $config);
	}


	/**
	 * This function is called at the end of the checkout. That's the function which should display your payment gateway redirection form with the data from HikaShop
	 */
	function onAfterOrderConfirm(&$order, &$methods, $method_id)
	{
		// This is a mandatory line in order to initialize the attributes of the payment method
		parent::onAfterOrderConfirm($order,$methods,$method_id);

		// Here we can do some checks on the options of the payment method and make sure that every required parameter is set and otherwise display an error message to the user
		// The plugin can only work if those parameters are configured on the website's backend
		if (empty($this->payment_params->identifier))
		{
			// Enqueued messages will appear to the user, as Joomla's error messages
			$this->app->enqueueMessage('You have to configure an identifier for the Example plugin payment first : check your plugin\'s parameters, on your website backend', 'error');
			return false;
		}
		elseif (empty($this->payment_params->password))
		{
			$this->app->enqueueMessage('You have to configure a password for the Example plugin payment first : check your plugin\'s parameters, on your website backend', 'error');
			return false;
		}
		elseif (empty($this->payment_params->payment_url))
		{
			$this->app->enqueueMessage('You have to configure a payment url for the Example plugin payment first : check your plugin\'s parameters, on your website backend', 'error');
			return false;
		}
		else
		{
			// Here, all the required parameters are valid, so we can proceed to the payment platform

			$amout = round($order->cart->full_total->prices[0]->price_value_with_tax, 2) * 100;
			
			// The order's amount, here in cents and rounded with 2 decimals because of the payment platform's requirements
			// There is a lot of information in the $order variable, such as price with/without taxes, customer info, products... you can do a var_dump here if you need to display all the available information

			// This array contains all the required parameters by the payment plateform
			// Not all the payment platforms will need all these parameters and they will probably have a different name.
			// You need to look at the payment gateway integration guide provided by the payment gateway in order to know what is needed here
			$vars = array(
				// User's identifier on the payment platform
				'IDENTIFIER' => $this->payment_params->identifier,
				//The id of the customer
				'CLIENTIDENT' => $order->order_user_id,
				// Order's description
				'DESCRIPTION' => "order number : ".$order->order_number,
				// The id of the order which will be given back by the payment gateway when it will notify your plugin that the payment has been done and which will allow you to know the order corresponding to the payment in order to confirm it
				'ORDERID' => $order->order_id,
				// The platform's API version, needed by the payment platform
				'VERSION' => 2.0,
				 // The amount of the order
				'AMOUNT' => $amout
			);

			// To certifiate the values integrity, payment platform use to ask a hash
			// This hash is generated according to the platform requirements
			$vars['HASH'] = $this->example_signature($this->payment_params->password, $vars);
			
			$this->vars = $vars;
			
			// Ending the checkout, ready to be redirect to the plateform payment final form
			// The showPage function will call the example_end.php file which will display the redirection form containing all the parameters for the payment platform
			return $this->showPage('end'); 
		}
	}

	/**
	 * To set the specific configuration (back end) default values (see $pluginConfig array)
	 */
	function getPaymentDefaultValues(&$element)
	{
		$element->payment_name = 'Example';
		$element->payment_description = 'You can pay by credit card using this payment method';
		$element->payment_images = 'MasterCard,VISA,Credit_card,American_Express';
		$element->payment_params->address_type = 'billing';
		$element->payment_params->notification = 1;
		$element->payment_params->invalid_status = 'cancelled';
		$element->payment_params->verified_status = 'confirmed';
	}

	/**
	 * After submiting the plateform payment form, this is where the website will receive the response information from the payment gateway servers and then validate or not the order
	 */
	function onPaymentNotification(&$statuses)
	{
		// We first create a filtered array from the parameters received
		$vars = array();
		
		$filter = JFilterInput::getInstance();
		
		// A loop to create an array $var with all the parameters sent by the payment gateway with a POST method, and loaded in the $_REQUEST
		foreach($_REQUEST as $key => $value)
		{
			$key = $filter->clean($key);
			$value = JRequest::getString($key);
			$vars[$key] = $value;
		}

		// The load the parameters of the plugin in $this->payment_params and the order data based on the order_id coming from the payment platform
		$order_id = (int)@$vars['ORDERID'];
		$dbOrder = $this->getOrder($order_id);

		// With the order, we can load the payment method, and thus all the payment parameters
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params))
			return false;
		$this->loadOrderData($dbOrder);

		// Here we are configuring the "succes URL" and the "fail URL". After checking all the parameters sent by the payment gateway, we will redirect the customer to one or another of those URL (not necessary for our example platform).
		$return_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$order_id.$this->url_itemid;
		$cancel_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order_id.$this->url_itemid;

		// With its answer, the payment gateway sent a hash. This time, it's our turn to recalculate the hash with all the parameters received ($vars) to check if all the information received are identical to those sent by the payment platform ($decode mode)
		$hash = $this->example_signature($this->payment_params->password, $vars, false, true);

		// Debug mode activated or not
		if($this->payment_params->debug)
		{
			// Here we display debug information which will be catched by HikaShop and stored in the payment log file available in the configuration's Files section.
			echo print_r($vars,true)."\n\n\n";
			echo print_r($dbOrder,true)."\n\n\n";
			echo print_r($hash,true)."\n\n\n";
		}

		// Here is the last step : depending of the information received, we will validate or not the order, and redirect the user
		// We compare the hash generated on our side with the one sent by the payment gateway. If they are different, informations are distorted > process aborted
		if (strcasecmp($hash, $vars['HASH']) != 0)
		{
			// Here we display debug information which will be catched by HikaShop and stored in the payment log file available in the configuration's Files section.
			if($this->payment_params->debug)
				echo 'Hash error '.$vars['HASH'].' - '.$hash."\n\n\n";
			return false;
		}
		// The payment platform returns a code, corresponding to the state of the operation. Here, the "success" code is 0000. It means that any other code correspond to a payment failure > process aborted
		elseif($vars['EXECCODE'] != '0000')
		{
			// Here we display debug information which will be catched by HikaShop and stored in the payment log file available in the configuration's Files section.
			if($this->payment_params->debug)
				echo 'payment '.$vars['MESSAGE']."\n\n\n";
				
			// This function modifies the order with the id $order_id, to attribute it the status invalid_status.
			$this->modifyOrder($order_id, $this->payment_params->invalid_status, true, true);

			//To redirect the user, if needed. Here the redirection is useless : we are on server side (and not user side, so the redirect won't work), and the cancel url has been set on the payment platform merchant account
			// $this->app->redirect($cancel_url);
			return false;
		}
		//If everything's OK, the payment has been done. Order is validated -> success
		else
		{
			$this->modifyOrder($order_id, $this->payment_params->verified_status, true, true);
			
			// $this->app->redirect($return_url);
			return true;
		}
	}

	/**
	 * To generate the Hash, according to the payment platform requirement
	 * $password is the merchant's password, $parameters is an array of all required parameters, $debug is the debug mode, $decode is 'true' when we want to re-generate the hash after the payment platform answer
	 */
	function example_signature($password, $parameters, $debug = false, $decode = false)
	{
		// Here, the payment platform asked to generate a hash with a string with all the $parameters in alphabetic order, separated by the $password
		// The string has to look like : passwordIDENTIFIER=TotopasswordVERSION=2password ...

		// Ordering the parameters in alphabetic order with the ksort() function
		ksort($parameters);
		
		// To initialize our string. $clear_string is the string to be hashed ; $clear_string = password
		$clear_string = $password;

		// When we are on the $decode mode, this is an array of all the parameters that could be sent by the platform
		$expectedKey = array(
			'IDENTIFIER',
			'TRANSACTIONID',
			'CLIENTIDENT',
			'CLIENTEMAIL',
			'ORDERID',
			'VERSION',
			'LANGUAGE',
			'CURRENCY',
			'EXTRADATA',
			'CARDCODE',
			'CARDCOUNTRY',
			'EXECCODE',
			'MESSAGE',
			'DESCRIPTOR',
			'ALIAS',
			'3DSECURE',
			'AMOUNT',
		);

		// Here we construct the hash string according to the payment gateway's requirements
		foreach ($parameters as $key => $value)
		{
			if ($decode)
			{
				if (in_array($key, $expectedKey))
					$clear_string .= $key . '=' . $value . $password;
			}
			else
			{
				// $clear_string = passwordIDENTIFIER=Totopassword
				$clear_string .= $key . '=' . $value . $password;
			}
		}

		// As the payment gateway example wants us to use the hash function which is not available below PHP 5.1.2, we have this check here
		if (PHP_VERSION_ID < 50102)
		{
			$this->app->enqueueMessage('The Example payment plugin requires at least the PHP 5.1.2 version to work, but it seems that it is not available on your server. Please contact your web hosting to set it up.', 'error');
			return false;
		}
		else
		{
			// With the debug mode activated, we return the $clear_string which is not hashed, so we can see if it's constructed as we want
			if ($debug)
				return $clear_string;

			// Else we return the string hashed with a sha256 method, as required by the platform
			return hash('sha256', $clear_string);
		}
	}
}
