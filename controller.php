<## Common config #>
<#= include('shared/config-common.php') #>
<#= include('shared/config-table.php') #>
<?php

namespace <#= ProjectNamespace #>;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class <#= tblClassName #>Controller extends ControllerBase
{
<#
    let actions = ["list", "add", "addopt", "view", "edit", "update", "delete", "search", "preview"],
        previewExt = GetExtensionObject("Preview");
    for (let id of actions) {
        let generate = TABLE.TblGen && ((id == "list" || // always generate for list
            id == "preview" && previewExt && previewExt.Enabled && TABLE.TblIsDetail ||
            id == "add" && TABLE.TblAdd ||
            id == "view" && TABLE.TblView ||
            id == "edit" && TABLE.TblEdit ||
            id == "update" && TABLE.TblMultiUpdate ||
            id == "delete" && TABLE.TblDelete ||
            id == "search" && TABLE.TblSearch) ||
            id == "addopt" && TABLE.TblAddOpt);
        if (generate) {
            let pageName = tblClassName + PascalCase(id),
                keyFields = TABLE.Fields.filter(f => f.FldIsPrimaryKey),
                useLayout = ["addopt", "preview"].includes(id) ? ", false" : "";
#>
    // <#= id #>
    public function <#= id #>(Request $request, Response $response, array $args): Response
    {
        return $this->runPage($request, $response, $args, "<#= pageName #>"<#= useLayout #>);
    }
<#
        }
    }
#>

}
