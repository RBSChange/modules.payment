<?php
class payment_CybermutPaymentResult extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		$ms = payment_ModuleService::getInstance();

		try
		{
			/* @var $bankResponse payment_Transaction */
			$bankResponse= $this->getParameter('bankResponse');
			$order = $bankResponse->getOrder();
		}
		catch (Exception $e)
		{
			$ms->log("BANKING CYBERMUT TASK : task: ".$this->plannedTask->getId().", ORDER NOT FOUND");
			throw $e;
		}

		if (f_util_StringUtils::isEmpty($order->getPaymentStatus()) || $order->getPaymentStatus() === 'initiated' || $order->getPaymentStatus() === 'waiting')
		{
			$tm = f_persistentdocument_TransactionManager::getInstance();
			try
			{
				$tm->beginTransaction();

				$connectorService = payment_CybermutconnectorService::getInstance();
				$connectorService->setPaymentResult($bankResponse, $order);
				$ms->log("BANKING CYBERMUT TASK : task: ".$this->plannedTask->getId().", order: ".$order->getId().", STATUS: ".$order->getPaymentStatus());

				$tm->commit();
			}
			catch (Exception $e)
			{
				$ms->log("BANKING CYBERMUT TASK : task: ".$this->plannedTask->getId().", order: ".$order->getId().", ERROR: ".$e->getMessage());
				$tm->rollBack($e);
			}
		}
		else
		{
			$ms->log("BANKING CYBERMUT TASK : task: ".$this->plannedTask->getId().", order: ".$order->getId().", SKIP: transaction already handled");
		}
	}
}
