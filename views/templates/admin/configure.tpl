{**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div class="panel">
  <div class="panel-heading"><i class="icon-cogs"></i> {l s='M4P Core' d='Modules.M4pcore.Admin'}</div>

  <p>{l s='The settings live in their own pages, so one page does not hold everything.' d='Modules.M4pcore.Admin'}</p>

  <div class="m4p-core-links">
    {if $m4p_core_dashboard}
      <a href="{$m4p_core_dashboard|escape:'html':'UTF-8'}" class="btn btn-primary">
        <i class="icon-dashboard"></i> {l s='Overview' d='Modules.M4pcore.Admin'}
      </a>
    {/if}
    {if $m4p_core_settings}
      <a href="{$m4p_core_settings|escape:'html':'UTF-8'}" class="btn btn-default">
        <i class="icon-cogs"></i> {l s='Shared settings' d='Modules.M4pcore.Admin'}
      </a>
    {/if}
  </div>

  {if !$m4p_core_dashboard || !$m4p_core_settings}
    <div class="alert alert-warning">
      {l s='The pages are missing from the menu. This happens when a database from before the module was installed is restored.' d='Modules.M4pcore.Admin'}
    </div>
  {/if}

  <table class="table m4p-core-sections">
    <thead>
      <tr>
        <th>{l s='Section' d='Modules.M4pcore.Admin'}</th>
        <th>{l s='Added by' d='Modules.M4pcore.Admin'}</th>
        <th>{l s='Fields' d='Modules.M4pcore.Admin'}</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$m4p_core_sections key=id item=section}
        <tr>
          <td><i class="{$section.icon|escape:'html':'UTF-8'}"></i> {$section.title|escape:'html':'UTF-8'}</td>
          <td>{if $section.owner}{$section.owner|escape:'html':'UTF-8'}{else}–{/if}</td>
          <td>{$section.fields|@count}</td>
        </tr>
      {/foreach}
    </tbody>
  </table>

  <form method="post">
    <button type="submit" name="m4p_core_repair" value="1" class="btn btn-default">
      <i class="icon-wrench"></i> {l s='Add the missing pages' d='Modules.M4pcore.Admin'}
    </button>
  </form>
</div>
