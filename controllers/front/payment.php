<?php

class ComnpayPaymentModuleFrontController extends ModuleFrontController
{
	
	/**
	 *
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->display_column_left = false;
		parent::initContent();
		
		// Récupération des données nécessaires
		$context = $this->context;
		$cart = $context->cart;
		$id_lang = Language::getIdByIso('fr');
		$customer = new Customer((int)$cart->id_customer);
		$address = $customer->getAddresses($id_lang);
		$addressUser = $address[0];

		$extension = new Comnpay();
		if (!$extension->isAvailable())
			Tools::redirect('index.php?controller=order');

		$urlRetour = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/comnpay/controllers/front/retour.php';

		// Comparaison entre la valeur client et la valeur administrateur du paiement en 3 fois
		if(Tools::getvalue('typeTr')==1 && Configuration::get('COMNPAY_GATEWAY_P3F')=='on') {
			$P3F = "P3F";
			// Type de paiement enregistrer en cookie
			$context->cookie->__set("typeTr",$P3F);
		}
		else {
			$P3F = "D";
			$context->cookie->__set("typeTr",$P3F);
		}

		if(!empty($addressUser[phone])){
			$phoneUser = $addressUser[phone];
		} else {
			$phoneUser = $addressUser[phone_mobile];
		}

		// On prépare le formulaire qui enverra l'utilisateur sur la passerelle de paiement		
		$comnpay['montant'] = number_format( $cart->getOrderTotal(), 2 , '.', '');
		$comnpay['idTPE'] = Configuration::get('COMNPAY_GATEWAY_TPE_NUMBER');
		$comnpay['idTransaction'] = time()."-".(int)$cart->id;
		$comnpay['devise'] = $context->currency->iso_code;
		$comnpay['lang'] = Language::getIsoById($this->context->language->id);
		$comnpay['nom_produit'] = "";
		$comnpay['source'] = $_SERVER['SERVER_NAME'];
		$comnpay['urlRetourOK'] = $urlRetour.'?customer='.$customer->secure_key.'&id_cart='.(int)$cart->id;
		$comnpay['urlRetourNOK'] = $urlRetour.'?customer='.$customer->secure_key.'&id_cart='.(int)$cart->id;
		$comnpay['urlIPN'] = (Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/'.$this->module->name.'/ipn.php';
		$comnpay['extension'] = "prestashop-".$this->module->name."-".$this->module->version;
		$comnpay['data'] = $P3F;
		$comnpay['typeTr'] = $P3F;
		//Si c'est un paiement en 3 fois, on ajoute d'autre informations pour le préremplissage des champs
		if($P3F == "P3F"){
		$comnpay['user_prenom'] = $customer->firstname;
		$comnpay['user_nom'] = $customer->lastname;
		$comnpay['user_email'] = $customer->email;
		$comnpay['user_telephone'] = $phoneUser;
		$comnpay['user_adresse'] = $addressUser[address1];
		$comnpay['user_codePostal'] = $addressUser[postcode];
		$comnpay['user_ville'] = $addressUser[city];
		$comnpay['user_pays'] = $addressUser[country];
		}
		$comnpay['key'] = Configuration::get('COMNPAY_GATEWAY_SECRET_KEY');
		
		
		// Encodage
		$comnpayWithKey = base64_encode(implode("|", $comnpay));
		unset($comnpay['key']);
		$comnpay['sec'] = hash("sha512",$comnpayWithKey);

		// Vérification de la plateforme de paiement
		if (Configuration::get('COMNPAY_GATEWAY_CONFIG')=="HOMOLOGATION")
			$form_action = Configuration::get('COMNPAY_GATEWAY_HOMOLOGATION');
		else
			$form_action = Configuration::get('COMNPAY_GATEWAY_PRODUCTION');

		// Generate Form
		$form = "<form name=\"comnpay_form\" id=\"comnpay_form\" method=\"post\" action=\"".$form_action."\">";
        foreach ($comnpay as $key => $value) {
			$form .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
			$form .= "<input type=\"submit\" style=\"visibility:hidden; display:none\"/>";
        }
        $form .= "</form>";
        
		$this->context->smarty->assign(array (
									'comnpayForm' => $form
								));
		
		$this->setTemplate('comnpay.tpl');
	}
}