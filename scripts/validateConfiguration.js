$(function() {
	$("#submitComnpay").on("click", function()	{
		// Values configuration
		var plateformSelect = $("input[name=COMNPAY_GATEWAY_CONFIG]:checked").val();
		var numberTPE = $("#tpe_number").val();
		var secretKEY = $("#secret_key").val();
		if(plateformSelect == "HOMOLOGATION")	{
			if((numberTPE == "DEMO") && (secretKEY == "DEMO"))	{
				return true;
			}else{
				var strTPE = numberTPE.substring(0, 3);
				if(strTPE == "HOM")	{
					return true;
				}else{
					var messageAlert = 'Pour tester votre site en Homologation, veuillez saisir le numéro de TPE qui vous a été communiqué (exemple : HOM-XXX-XXX). Si vous ne le possédez pas, vous pouvez utiliser le compte de démonstration qui a pour numéro TPE "DEMO" et pour clé secrète "DEMO"';
					alert(messageAlert);
					return false;
				}
			}
		}else{
			if((numberTPE !== "") && (secretKEY !== ""))	{
				var strTPE = numberTPE.substring(0, 3);
				if(strTPE == "VAD")	{
					return true;
				}else{
					if(strTPE == "HOM")	{
						var messageAlert = 'Vous utilisez actuellement le numéro de TPE provisoire, veuillez saisir le numéro de TPE valide qui vous a été communiqué.';
					}else{
						var messageAlert = 'Veuillez saisir le numéro de TPE valide qui vous a été communiqué (exemple : VAD-XXX-XXX)';
					}
					alert(messageAlert);
					return false;
				}
			}else{
				var messageAlert = 'Pour mettre votre site en Production, veuillez saisir le numéro de TPE ainsi que la clé secrète qui vous ont été communiqués';
				alert(messageAlert);
				return false;
			}
		}
	});
});