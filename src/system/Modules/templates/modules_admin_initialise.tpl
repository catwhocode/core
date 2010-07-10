{include file="modules_admin_menu.tpl"}
<div class="z-admincontainer">
    <div class="z-adminpageicon">{img modname=core src=editdelete.gif set=icons/large __alt="Install"}</div>
    <h2>{gt text="Install"} - {modgetinfo modid=$id info=displayname}</h2>
    <form class="z-form" action="{modurl modname="Modules" type="admin" func="initialise"}" method="post" enctype="application/x-www-form-urlencoded">
        <div>
            <input type="hidden" name="authid" value="{insert name="generateauthkey" module="Modules"}" />
            <input type="hidden" name="confirmation" value="1" />
            <input type="hidden" name="id" value="{$id|safetext}" />
            <div class="z-formrow">
                <p class="z-informationmsg">{gt text="Notice! This module either requires other modules recommends modules be installed.  The report below details these requirements and/or recommendations."}</p>
            </div>
            {if $dependencies}
            <div class="z-formrow">
                <table class="z-admintable">
                    <thead>
                        <tr>
                            <th>{gt text="Module name"}</th>
                            <th>{gt text="Level"}</th>
                            <th>{gt text="Install"}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$dependencies item=dependency}
                        <tr class="{cycle values="z-odd,z-even"}">
                            <td>{$dependency.modname}</td>
                            <td>
                                {if $dependency.insystem neq true and $dependency.status eq 1}
                                {gt text="Not found in file system"}
                                {elseif $dependency.status eq 1}
                                {gt text="Required"}
                                {elseif $dependency.status eq 2}
                                {gt text="Recommended"}
                                {/if}
                            </td>
                            <td>
                                {if $dependency.insystem neq true and ($dependency.status eq 1 or $dependency.status eq 2)}
                                {* gt text="If you wish to install this module pleas." *}
                                {elseif $dependency.status eq 1}
                                <input type="hidden" name="dependencies[]" value="{$dependency.id}" />
                                <input type="checkbox" name="dummy[]" value="{$dependency.id}" disabled="disabled" />
                                {elseif $dependency.status eq 2}
                                <input type="checkbox" name="dependencies[]" value="{$dependency.id}" />
                                {/if}
                            </td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {/if}
            {if !$fataldependency}
                <p class="z-informationmsg">{gt text="Do you really want to initialise this module?"}</p>
            {else}
                <p class="z-errormsg">{gt text="Error! Required dependencies are not present.  This module cannot be installed with them."}</p>
            {/if}
            
            <div class="z-buttons z-formbuttons">
                {if !$fataldependency}
                {button src=button_ok.gif set=icons/extrasmall __alt="Accept" __title="Accept" __text="Accept"}
                {/if}
                <a href="{modurl modname=Modules type=admin func=view}" title="{gt text="Cancel"}">{img modname=core src=button_cancel.gif set=icons/extrasmall __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
            </div>
        </div>
    </form>
</div>
