{**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div class="m4p-core-settings">
  <div class="panel m4p-core-settings__nav">
    <div class="panel-heading"><i class="icon-list"></i> {l s='Sections' d='Modules.M4pcore.Admin'}</div>
    <ul>
      {foreach from=$m4p_sections key=id item=section}
        <li{if $id == $m4p_current} class="is-current"{/if}>
          <a href="{$m4p_base_link|escape:'html':'UTF-8'}&amp;section={$id|escape:'url'}">
            <i class="{$section.icon|escape:'html':'UTF-8'}"></i> {$section.title|escape:'html':'UTF-8'}
          </a>
        </li>
      {/foreach}
    </ul>
  </div>

  <div class="panel m4p-core-settings__form">
    <div class="panel-heading">
      <i class="{$m4p_section.icon|escape:'html':'UTF-8'}"></i> {$m4p_section.title|escape:'html':'UTF-8'}
      <span class="badge">{$m4p_shop|escape:'html':'UTF-8'}</span>
    </div>

    {if $m4p_section.description}
      <p class="text-muted">{$m4p_section.description|escape:'html':'UTF-8'}</p>
    {/if}

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="section" value="{$m4p_current|escape:'html':'UTF-8'}">

      {foreach from=$m4p_section.fields item=field}
        {assign var=value value=$m4p_values[$field.key]}
        <div class="form-group{if $field.key|in_array:$m4p_refused} has-error{/if}">
          {if $field.type == 'switch'}
            <span class="control-label">{$field.label|escape:'html':'UTF-8'}</span>
          {else}
            <label for="f-{$field.key|escape:'html':'UTF-8'}">{$field.label|escape:'html':'UTF-8'}</label>
          {/if}

          {if $field.type == 'switch'}
            <label class="m4p-core-check" for="f-{$field.key|escape:'html':'UTF-8'}">
              <input type="checkbox" id="f-{$field.key|escape:'html':'UTF-8'}" name="{$field.key|escape:'html':'UTF-8'}"
                value="1"{if $value} checked{/if}{if !$m4p_can_edit} disabled{/if}>
              <span>{l s='On' d='Modules.M4pcore.Admin'}</span>
            </label>
          {elseif $field.type == 'textarea'}
            <textarea class="form-control" id="f-{$field.key|escape:'html':'UTF-8'}"
              name="{$field.key|escape:'html':'UTF-8'}" rows="3"{if !$m4p_can_edit} readonly{/if}>{$value|escape:'html':'UTF-8'}</textarea>
          {elseif $field.type == 'color'}
            <div class="m4p-core-color">
              <input type="color" class="m4p-core-color__picker" aria-hidden="true" tabindex="-1"
                value="{if isset($m4p_swatches[$field.key])}{$m4p_swatches[$field.key]|escape:'html':'UTF-8'}{else}#000000{/if}"
                data-m4p-color-for="f-{$field.key|escape:'html':'UTF-8'}"{if !$m4p_can_edit} disabled{/if}>
              <input type="text" class="form-control" id="f-{$field.key|escape:'html':'UTF-8'}"
                name="{$field.key|escape:'html':'UTF-8'}" value="{$value|escape:'html':'UTF-8'}"
                {if isset($field.placeholder)} placeholder="{$field.placeholder|escape:'html':'UTF-8'}"{/if}{if !$m4p_can_edit} readonly{/if}>
            </div>
          {elseif $field.type == 'file'}
            <input type="file" class="form-control" id="f-{$field.key|escape:'html':'UTF-8'}" name="{$field.key|escape:'html':'UTF-8'}"
              {if isset($field.accept)} accept="{$field.accept|escape:'html':'UTF-8'}"{/if}{if !$m4p_can_edit} disabled{/if}>
            {if $value}<p class="help-block">{l s='Current file:' d='Modules.M4pcore.Admin'} <code>{$value|escape:'html':'UTF-8'}</code></p>{/if}
          {elseif $field.type == 'select'}
            <select class="form-control fixed-width-xxl" id="f-{$field.key|escape:'html':'UTF-8'}"
              name="{$field.key|escape:'html':'UTF-8'}"{if !$m4p_can_edit} disabled{/if}>
              {foreach from=$field.options key=optionValue item=optionLabel}
                <option value="{$optionValue|escape:'html':'UTF-8'}"{if $value == $optionValue} selected{/if}>
                  {$optionLabel|escape:'html':'UTF-8'}
                </option>
              {/foreach}
            </select>
          {else}
            <input type="{if $field.type == 'number'}number{elseif $field.type == 'email'}email{else}text{/if}"
              class="form-control" id="f-{$field.key|escape:'html':'UTF-8'}"
              name="{$field.key|escape:'html':'UTF-8'}" value="{$value|escape:'html':'UTF-8'}"
              {if isset($field.placeholder)} placeholder="{$field.placeholder|escape:'html':'UTF-8'}"{/if}
              {if isset($field.min)} min="{$field.min|intval}"{/if}
              {if isset($field.max)} max="{$field.max|intval}"{/if}{if !$m4p_can_edit} readonly{/if}>
          {/if}

          {if $field.key|in_array:$m4p_refused}
            <p class="help-block">{l s='This value does not fit the field, so the previous one was kept.' d='Modules.M4pcore.Admin'}</p>
          {/if}
          {if isset($field.hint)}<p class="help-block">{$field.hint|escape:'html':'UTF-8'}</p>{/if}
          <p class="help-block"><code>{if isset($field.token)}--{$field.token|escape:'html':'UTF-8'} · {/if}{'M4P_CORE_'|cat:$field.key|escape:'html':'UTF-8'}</code></p>
        </div>
      {/foreach}

      {if $m4p_can_edit}
        <div class="panel-footer">
          <button type="submit" name="m4p_core_save" value="1" class="btn btn-default pull-right">
            <i class="process-icon-save"></i> {l s='Save' d='Modules.M4pcore.Admin'}
          </button>
        </div>
      {else}
        <p class="alert alert-info">{l s='This account may read these settings but not change them.' d='Modules.M4pcore.Admin'}</p>
      {/if}
    </form>
  </div>
</div>
