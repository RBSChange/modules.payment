<?php
class payment_BankListenerCybermutAction extends f_action_BaseAction
{

	/**
	 * The response we must give to the bank server should be:
	 *
	 * Pragma: no-cache
	 * Content-type: text/plain
	 * Version: 1
	 * OK
	 *
	 * or:
	 *
	 * Pragma: no-cache
	 * Content-type: text/plain
	 * Version: 1
	 * Document falsifie
	 *
	 * @param Context $context
	 * @param WebRequest $request
	 */
	public function _execute($context, $request)
	{
	    $remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
		$ms = payment_ModuleService::getInstance();	
		$ms->log("BANKING CYBERMUT LISTENER from [".$remoteAddr." : ".$requestUri."] BEGIN");	
		
		try
		{
			$this->getTransactionManager()->beginTransaction();
			
			$connectorService = payment_CybermutconnectorService::getInstance();       
			$bankResponse = $connectorService->getBankResponse($request->getParameters());			
			if ($bankResponse->getTransactionId() == null)
			{
				throw new Exception("CYBERMUT BANKING FAILED : BAD MAC CHECKING");
			}
			$order = $bankResponse->getOrder();
			$connectorService->setPaymentResult($bankResponse, $order);
					
			$ms->log("BANKING CYBERMUT LISTENER from [".$remoteAddr." : ".$requestUri."] END");
			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$ms->log("BANKING CYBERMUT LISTENER from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);
			
			header("Pragma: no-cache");
			header("Content-type: text/plain");
    		$receipt = CMCIC_CGI2_MACNOTOK.$bankResponse->getRawBankResponse();
    		printf (CMCIC_CGI2_RECEIPT, $receipt);
    		exit();
		}
		
		ob_get_clean();		
		header("Pragma: no-cache");
		header("Content-type: text/plain");
    	$receipt = CMCIC_CGI2_MACOK;
    	printf (CMCIC_CGI2_RECEIPT, $receipt);
		exit();
	}
	
	/**
	 * @return Integer
	 */
	public function getRequestMethods()
	{
		return Request::POST | Request::GET;
	}

	/**
	 * @return Boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}