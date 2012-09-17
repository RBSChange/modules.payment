<?php
class payment_BankListenerOgoneAction extends change_Action
{
	
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	protected function _execute($context, $request)
	{
		$remoteAddr = $_SERVER['REMOTE_ADDR'];
		$requestUri = $_SERVER['REQUEST_URI'];
		$ms = payment_ModuleService::getInstance();	
		$ms->log("BANKING OGONE LISTENER from [".$remoteAddr." : ".$requestUri."] BEGIN");	
	  
		try
		{
			$this->getTransactionManager()->beginTransaction();		
			$parameters = $request->getParameters();
			$connectorService = payment_OgoneconnectorService::getInstance();
			$bankResponse = $connectorService->getBankResponse($parameters);
			if ($bankResponse)
			{
				$order = $bankResponse->getOrder();
				$connectorService->setListenerPaymentResult($bankResponse, $order);
			}
			$ms->log("BANKING OGONE LISTENER from [".$remoteAddr." : ".$requestUri."] END");
			$this->getTransactionManager()->commit();
	
		}
		catch(Exception $e)
		{
			$ms->log("BANKING OGONE LISTENER from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);
		}
		return null;
	}

	/**
	 * @return integer
	 */
	public function getRequestMethods()
	{
		return change_Request::POST | change_Request::GET;
	}

	/**
	 * @return boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}