<p class="payment_module">
	<a class="comnpay-logo-link" href="{$linkPayment|escape:'htmlall':'UTF-8'}" title="{l s='Pay by credit card with comNpay' mod='comnpay'}">
		<img id="D" class="comnpay-logo" src="{$path_img|escape:'htmlall':'UTF-8'}/img/comnpay.png" alt="{l s='Pay by credit card with comNpay' mod='comnpay'}" />
		{l s='Pay with comNpay' mod='comnpay'}
	</a>
</p>
{if $p3f =='on'}
<p class="payment_module">
	<a id="P3F" class="comnpay-logo-link" href="{$linkPayment3f|escape:'htmlall':'UTF-8'}" title="{l s='Pay in three time with comNpay' mod='comnpay'}">
		<img class="comnpay-logo" src="{$path_img|escape:'htmlall':'UTF-8'}/img/comnpay_p3f.png" alt="{l s='Pay in three time with comNpay' mod='comnpay'}" />
		{l s='Pay in three time with comNpay' mod='comnpay'}
	</a>
</p>
{/if}
