<?php


require_once(dirname(__FILE__).'./../../../../config/config.inc.php');
include_once(dirname(__FILE__).'./../../../../init.php');
require_once(dirname(__FILE__).'./../../comnpay.php');

// Initialisation
$comnpay = new Comnpay();
$context = Context::getContext();

if (!$comnpay->isAvailable())
		return;


// Récupération des donnés nécessaires
$result = Tools::getValue('result');

// Vérification du retour client
if ($result == "OK")
{
	$transactionId = Tools::getValue('transactionId');
	$explodeTemp = explode("-",$transactionId);
	$cartId = $explodeTemp[1];
	$typeTrIpn = $context->cookie->typeTr;
	$cartIdRef = Tools::getValue('id_cart');

	if($typeTrIpn == 'P3F')
	{
	    $typeTrAccepted = Configuration::get('COMNPAY_OS_ACCEPTED_P3F');
	    $typeTrPending = Configuration::get('COMNPAY_OS_PENDING_P3F');
	} elseif($typeTrIpn == 'D')	
	{
	    $typeTrAccepted = Configuration::get('COMNPAY_OS_ACCEPTED');
	    $typeTrPending = Configuration::get('COMNPAY_OS_PENDING');
	} else 
	{
		Logger::addLog("comnpay module: échec lors de la vérification des données POST du type de transaction ! transactionId ".$transactionId, 4);
		header("Status: 400 Bad Request", false, 400);
		exit();
	}

	if ($cartId!=$cartIdRef) {
		// La référence a été modifiée
		Logger::addLog("comnpay module: échec lors de la vérification du numéro de transaction ! transactionId=".$transactionId.", cartId=".$cartIdRef, 4);
		Tools::redirect('index.php?controller=order&step=1');
	}

	$cart = new Cart($cartId);
	$customer = new Customer((int)$cart->id_customer);
	$order = new Order();
	$orderId = $order->getOrderByCartId($cartId);
	if(!$orderId){
		$message = "En attente de la confirmation du paiement comNpay. Identifiant de transaction: ".$transactionId;
	    $comnpay->validateOrder($cart->id, $typeTrPending, (float)$cart->getOrderTotal(true, Cart::BOTH), $comnpay->displayName, $message, array(), (int)$cart->id_currency, false, $customer->secure_key);
	    $order = new Order($comnpay->currentOrder);
	}
	else{
	    // Ipn has been received
	    $order = new Order($orderId);
	}

	Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$comnpay->id.'&id_order='.$order->id.'&key='.$customer->secure_key);
}
else
{
	Tools::redirect('index.php?controller=order&step=3');
}


?>