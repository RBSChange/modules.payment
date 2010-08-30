<?php
class payment_BankListenerAtosAction extends f_action_BaseAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{	
	    $remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
		$ms = payment_ModuleService::getInstance();
		$ms->log("BANKING ATOS LISTENER from [".$remoteAddr." : ".$requestUri."] BEGIN");	
        
		try
		{
			$this->getTransactionManager()->beginTransaction();
			
        	$connectorService = payment_AtosconnectorService::getInstance();       
			$bankResponse = $connectorService->getBankResponse($request->getParameter('DATA'));			
			$order = $bankResponse->getOrder();
			$connectorService->setPaymentResult($bankResponse, $order);		
			$ms->log("BANKING ATOS LISTENER from [".$remoteAddr." : ".$requestUri."] END");	
			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$ms->log("BANKING ATOS LISTENER from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);
		}
		return VIEW::NONE;
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