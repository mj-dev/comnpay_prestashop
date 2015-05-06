<?php


require_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/comnpay.php');

// Init
$comnpay = new Comnpay();

// Get the data
$transactionId = Tools::getValue("idTransaction");
$result = Tools::getValue("result");
$explodeTemp = explode("-",$transactionId);
$cart = new Cart($explodeTemp[1]);
$typeTrIpn = Tools::getValue("data");

$order = new Order();
$orderId = $order->getOrderByCartId($cart->id);


// Check post data
if (!$comnpay->validSec($_REQUEST, Configuration::get('COMNPAY_GATEWAY_SECRET_KEY')))
{
	Logger::addLog("comnpay module: échec lors de la vérification des données POST ! transactionId ".$transactionId, 4);
	header("Status: 400 Bad Request", false, 400);
	exit();
}

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

if($orderId){
    // Existing order
    if($result == "OK")
    {
        $order = new Order($orderId);
        if($order->getCurrentState() == $typeTrAccepted){
            // Paiement déjà confirmé (ipn reçu en double ?)
        }
        elseif ($order->getCurrentState() == $typeTrPending){
            // Change order state to payment paid
            Logger::addLog('comnpay module: payment is validated for transactionId '.$transactionId);
            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $orderId;
            $orderHistory->changeIdOrderState((int)$typeTrAccepted, $orderId);
            $orderHistory->addWithemail();
            $orderHistory->save();
            if(_PS_VERSION_ > '1.5' && _PS_VERSION_ < '1.5.2'){
                $order->current_state = $orderHistory->id_order_state;
                $order->update();
            }
        }
        else {
            Logger::addLog('comnpay module: incorrect order status...'.$transactionId);
        }
    }
    else 
    {
        Logger::addLog('comnpay module: payment is refused or canceled for transactionId '.$transactionId);
    }
}
else
{
    if ($result == "OK") {
        // Order creation
        $customer = new Customer((int)$cart->id_customer);
        $message = "Confirmation du paiement comNpay. Identifiant de transaction: ".$transactionId;
        $comnpay->validateOrder($cart->id, $typeTrAccepted, (float)$cart->getOrderTotal(true, Cart::BOTH), $comnpay->displayName, $message, array(), (int)$cart->id_currency, false, $customer->secure_key);
        $order = new Order($comnpay->currentOrder);
    }
    else {
        Logger::addLog('comnpay module: payment is refused or canceled for transactionId '.$transactionId);
    }
}


?>