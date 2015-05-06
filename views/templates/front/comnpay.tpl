<div id="comnpay_redirect">
	<h2>{l s='Redirect to the payment page' mod='comnpay'}</h2>

	<p>
		<a href="javascript:$('#comnpay_form').submit();">{l s='You will now be redirected to comNpay. If this does not happen automatically, please press here.' mod='comnpay'}</a>
	</p>

	{$comnpayForm}

	<script type="text/javascript">
		$('#comnpay_form').submit();
	</script>
</div>