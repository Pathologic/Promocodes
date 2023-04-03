<?php
include_once 'autoload.php';
$instance = $params['instance'] ?? 'products';
$tpl = $tpl ?? '@CODE:<form data-promocodes>
  <div class="form-row align-items-center">
    <div class="col-auto">
      <label class="sr-only" for="promocodeInput">Name</label>
      <input type="text" class="form-control mb-2" id="promocodeInput" data-promocodes-instance="[+instance+]" placeholder="Введите промокод" value="[+promocode+]">
    </div>
    <div class="col-auto">
      <button class="btn btn-primary mb-2" data-promocodes-action="register"></button>
    </div>
    <div class="col-auto">
      <button class="btn btn-secondary mb-2" data-promocodes-action="remove"></button>
    </div>
  </div>
</forms>';
$DLTemplate = DLTemplate::getInstance($modx);
$out = '';
if ($tpl) {
    $out = $DLTemplate->parseChunk($tpl, [
        'instance' => $instance,
        'promocode' => ci()->promocodes->get('instance')
    ]);
}

return $out;
