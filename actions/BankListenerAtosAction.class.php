<?php
class payment_BankListenerAtosAction extends payment_Action
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
		$ms = payment_ModuleService::getInstance();	
	    $remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
		$ms->log("BANKING ATOS LISTENER from [".$remoteAddr." : ".$requestUri."] BEGIN");	
        $connectorService = payment_AtosconnectorService::getInstance();       
		try
		{
			$bankResponse = $connectorService->getBankResponse($request->getParameter('DATA'));			
			$order = $bankResponse->getOrder();
			$connectorService->setPaymentResult($bankResponse, $order);		
			$ms->log("BANKING ATOS LISTENER from [".$remoteAddr." : ".$requestUri."] END");	
		}
		catch(Exception $e)
		{
			$ms->log("BANKING ATOS LISTENER from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			Framework::exception($e);
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