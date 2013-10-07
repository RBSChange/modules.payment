<?php
/**
 * payment_persistentdocument_paypalconnector
 * @package payment.persistentdocument
 */
class payment_persistentdocument_paypalconnector extends payment_persistentdocument_paypalconnectorbase
{
	public function getTemplateViewName()
	{
		return 'Paypal';
	}
	
	
	/*   
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
	' Inputs:  
	'		paymentAmount:  	Total value of the shopping cart
	'		currencyCodeType: 	Currency code value the PayPal API
	'		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
	'		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
	'		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
	'		shipToName:		the Ship to name entered on the merchant's site
	'		shipToStreet:		the Ship to Street entered on the merchant's site
	'		shipToCity:			the Ship to City entered on the merchant's site
	'		shipToState:		the Ship to State entered on the merchant's site
	'		shipToCountryCode:	the Code for Ship to Country entered on the merchant's site
	'		shipToZip:			the Ship to ZipCode entered on the merchant's site
	'		shipToStreet2:		the Ship to Street2 entered on the merchant's site
	'		phoneNum:			the phoneNum  entered on the merchant's site
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function callMarkExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, 
									  $cancelURL, $shipToName, $shipToStreet, $shipToCity, $shipToState,
									  $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum) 
	{
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
		
		$nvpstr="&Amt=". $paymentAmount;
		$nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&ReturnUrl=" . $returnURL;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
		$nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCodeType;
		$nvpstr = $nvpstr . "&ADDROVERRIDE=1";
		$nvpstr = $nvpstr . "&SHIPTONAME=" . $shipToName;
		$nvpstr = $nvpstr . "&SHIPTOSTREET=" . $shipToStreet;
		$nvpstr = $nvpstr . "&SHIPTOSTREET2=" . $shipToStreet2;
		$nvpstr = $nvpstr . "&SHIPTOCITY=" . $shipToCity;
		$nvpstr = $nvpstr . "&SHIPTOSTATE=" . $shipToState;
		$nvpstr = $nvpstr . "&SHIPTOCOUNTRYCODE=" . $shipToCountryCode;
		$nvpstr = $nvpstr . "&SHIPTOZIP=" . $shipToZip;
		$nvpstr = $nvpstr . "&PHONENUM=" . $phoneNum;
		
		//'--------------------------------------------------------------------------------------------------------------- 
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray= $this->hash_call("SetExpressCheckout", $nvpstr);	   
	    return $resArray;
	}
	
	/*'----------------------------------------------------------------------------------
	 Purpose: Redirects to PayPal.com site.
	 Inputs:  NVP string.
	 Returns: 
	----------------------------------------------------------------------------------
	*/
	public function redirectToPayPal ( $token )
	{
		if ($this->getSandbox() == true) 
		{	
			$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=";
		}
		else
		{
			$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=";
		}
		
		// Redirect to paypal.com here
		return $PAYPAL_URL . $token;
	}
	
	/**
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	  * hash_call: Function to perform the API call to PayPal using API signature
	  * @methodName is name of API  method.
	  * @nvpStr is nvp string.
	  * returns an associtive array containing the response from the server.
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	*/
	private function hash_call($methodName,$nvpStr)
	{
		
		if ($this->getSandbox() == true) 
		{
			$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
			//$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
		}
		else
		{
			$API_Endpoint = "https://api-3t.paypal.com/nvp";
			//$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
		}
	
		//declaring of global variables
		$version="2.3";
		$API_UserName = $this->getUsername();
		$API_Password = $this->getPassword();
		$API_Signature = $this->getSignature();
		
		$USE_PROXY = false;
		$PROXY_HOST = '127.0.0.1';
		$PROXY_PORT = '8080';
		
		$sBNCode = "PP-ECWizard";

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
	    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
	    //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
		if($USE_PROXY)
		{
			curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT); 
		}

		//NVPRequest for submitting to server
		$nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);

		payment_ModuleService::getInstance()->log('PAYPAL SERVER REQUEST :' . $nvpreq);
		
		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray= $this->deformatNVP($response);
		$nvpReqArray= $this->deformatNVP($nvpreq);
		$_SESSION['nvpReqArray']=$nvpReqArray;

		if (curl_errno($ch)) 
		{
			throw new Exception('Paypal server call : ' . curl_errno($ch) . ', ' . curl_error($ch));
		} 
		else 
		{
			 //closing the curl
		  	curl_close($ch);
		}

		return $nvpResArray;
	}
	
	/*'----------------------------------------------------------------------------------
	 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
	  * It is usefull to search for a particular key and displaying arrays.
	  * @nvpstr is NVPString.
	  * @nvpArray is Associative Array.
	   ----------------------------------------------------------------------------------
	  */
	private function deformatNVP($nvpstr)
	{
		$intial=0;
	 	$nvpArray = array();

		while(strlen($nvpstr))
		{
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
	     }
		return $nvpArray;
	}
	
	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	'
	' Inputs:  
	'		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
	' Returns: 
	'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	public function confirmPayment($finalPaymentAmt, $token, $paymentType, $currencyCodeType, $payerID )
	{
		$nvpstr  = '&TOKEN=' . urlencode($token) . '&PAYERID=' . urlencode($payerID) . '&PAYMENTACTION=' . urlencode($paymentType) . '&AMT=' . $finalPaymentAmt;
		$nvpstr .= '&CURRENCYCODE=' . urlencode($currencyCodeType) . '&IPADDRESS=' . urlencode($_SERVER['SERVER_NAME']); 

		$resArray = $this->hash_call("DoExpressCheckoutPayment",$nvpstr);
		$ack = strtoupper($resArray["ACK"]);
		return $resArray;
	}
}