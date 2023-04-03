<?php
include_once 'autoload.php';
$instance = $params['instance'] ?? 'products';
$class = $class ?? 'active';
$tpl = $tpl ?? '@CODE:<form data-promocodes class="[+class+]">
  <div class="form-row align-items-center">
    <div class="col-auto">
      <label class="sr-only" for="promocodeInput">Введите промокод</label>
      <input type="text" class="form-control mb-2" id="promocodeInput" data-promocodes-instance="[+instance+]" placeholder="промокод" value="[+promocode+]">
    </div>
    <div class="col-auto">
      <button class="btn btn-primary mb-2" data-promocodes-action="register">Применить</button>
    </div>
    <div class="col-auto">
      <button class="btn btn-secondary mb-2" data-promocodes-action="remove">Отменить</button>
    </div>
  </div>
</forms>';
$DLTemplate = DLTemplate::getInstance($modx);
$_templatePath = $DLTemplate->getTemplatePath();
$_templateExtension = $DLTemplate->getTemplateExtension();
$out = '';
if ($tpl) {
    $promocode = ci()->promocodes->get('instance');
    $out = $DLTemplate->parseChunk($tpl, [
        'instance'  => $instance,
        'promocode' => $promocode ?: '',
        'class'     => $promocode ? $class : ''
    ]);
}
$DLTemplate->setTemplatePath($_templatePath)->setTemplateExtension($_templateExtension);

return $out;
