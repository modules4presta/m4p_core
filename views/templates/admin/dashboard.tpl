{**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div class="panel">
  <div class="panel-heading"><i class="icon-dashboard"></i> {l s='Overview' d='Modules.M4pcore.Admin'}</div>

  <div class="m4p-core-state">
    <div>
      <span class="m4p-core-state__label">{l s='Shop being edited' d='Modules.M4pcore.Admin'}</span>
      <strong>{$m4p_state.shop|escape:'html':'UTF-8'}</strong>
    </div>
    <div>
      <span class="m4p-core-state__label">{l s='Values filled in for this shop' d='Modules.M4pcore.Admin'}</span>
      <strong>{$m4p_state.stored|intval} / {$m4p_state.total|intval}</strong>
    </div>
    <div>
      <span class="m4p-core-state__label">{l s='Settings version' d='Modules.M4pcore.Admin'}</span>
      <strong>{$m4p_state.schema|intval}</strong>
      {if $m4p_state.schema != $m4p_state.expected}
        <span class="badge badge-warning">{l s='expected' d='Modules.M4pcore.Admin'} {$m4p_state.expected|intval}</span>
      {/if}
    </div>
    <div>
      <span class="m4p-core-state__label">{l s='Hook for other modules' d='Modules.M4pcore.Admin'}</span>
      <strong>
        {if $m4p_state.hook}{l s='ready' d='Modules.M4pcore.Admin'}{else}<span class="badge badge-danger">{l s='missing' d='Modules.M4pcore.Admin'}</span>{/if}
      </strong>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-heading"><i class="icon-cogs"></i> {l s='Sections' d='Modules.M4pcore.Admin'}</div>

  <div class="m4p-core-cards">
    {foreach from=$m4p_sections item=section}
      <a class="m4p-core-card" href="{$section.link|escape:'html':'UTF-8'}">
        <span class="m4p-core-card__head">
          <i class="{$section.icon|escape:'html':'UTF-8'}"></i>
          <strong>{$section.title|escape:'html':'UTF-8'}</strong>
        </span>
        {if $section.description}
          <span class="m4p-core-card__desc">{$section.description|escape:'html':'UTF-8'}</span>
        {/if}
        <span class="m4p-core-card__meta">
          {$section.filled|intval} / {$section.total|intval} {l s='filled' d='Modules.M4pcore.Admin'}
          {if $section.owner && $section.owner != 'm4p_core'} · {$section.owner|escape:'html':'UTF-8'}{/if}
        </span>
      </a>
    {/foreach}
  </div>
</div>

<div class="panel">
  <div class="panel-heading"><i class="icon-puzzle-piece"></i> {l s='Modules of the family' d='Modules.M4pcore.Admin'}</div>

  {if $m4p_modules}
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Module' d='Modules.M4pcore.Admin'}</th>
          <th>{l s='Version' d='Modules.M4pcore.Admin'}</th>
          <th>{l s='State' d='Modules.M4pcore.Admin'}</th>
          <th>{l s='Adds a section' d='Modules.M4pcore.Admin'}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$m4p_modules item=module}
          <tr>
            <td><code>{$module.name|escape:'html':'UTF-8'}</code></td>
            <td>{$module.version|escape:'html':'UTF-8'}</td>
            <td>
              {if $module.active}
                <span class="badge badge-success">{l s='enabled' d='Modules.M4pcore.Admin'}</span>
              {else}
                <span class="badge">{l s='disabled' d='Modules.M4pcore.Admin'}</span>
              {/if}
            </td>
            <td>{if $module.listens}{l s='yes' d='Modules.M4pcore.Admin'}{else}{l s='no' d='Modules.M4pcore.Admin'}{/if}</td>
            <td>
              {if $module.link}
                <a href="{$module.link|escape:'html':'UTF-8'}" class="btn btn-default btn-xs">{l s='Configure' d='Modules.M4pcore.Admin'}</a>
              {else}
                <span class="text-muted">{l s='no settings page' d='Modules.M4pcore.Admin'}</span>
              {/if}
            </td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p class="text-muted">{l s='No other module of the family is installed on this shop.' d='Modules.M4pcore.Admin'}</p>
  {/if}
</div>
