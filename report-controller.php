<## Common config #>
<#= include('shared/config-common.php') #>
<#= include('shared/config-table.php') #>
<?php

namespace <#= ProjectNamespace #>;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * <#= tblVar #> controller
 */
class <#= tblClassName #>Controller extends ControllerBase
{
<#
    let id = TABLE.TblReportType;
    if (["crosstab", "dashboard", "summary", "custom"].includes(id)) {
        let isCustomFile = id == "custom",
            isDashboard = id == "dashboard",
            pageName = isCustomFile || isDashboard ? tblClassName : tblClassName + PascalCase(id);
#>
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        return $this->runPage($request, $response, $args, "<#= pageName #>");
    }
<#
    }
#>
}
