{*
 *  Joonte Billing System
 *  Copyright © 2015 Alex Keda, for www.host-food.ru
 *}

Ваш заказ #{$OrderID|string_format:"%05u"} на регистрацию домена {$DomainName|default:'$DomainName'}.{$Name|default:'$Name'} был заблокирован {$StatusDate|date_format:"%d.%m.%Y"}

{if !$MethodSettings.CutSign}
--
{$From.Sign|default:'$From.Sign'}

{/if}

