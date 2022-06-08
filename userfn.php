<?php

namespace <#= ProjectNamespace #>;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;

// Filter for 'Last Month' (example)
function GetLastMonthFilter($FldExpression, $dbid = 0)
{
    $today = getdate();
    $lastmonth = mktime(0, 0, 0, $today['mon'] - 1, 1, $today['year']);
    $val = date("Y|m", $lastmonth);
    $wrk = $FldExpression . " BETWEEN " .
        QuotedValue(DateValue("month", $val, 1, $dbid), DATATYPE_DATE, $dbid) .
        " AND " .
        QuotedValue(DateValue("month", $val, 2, $dbid), DATATYPE_DATE, $dbid);
    return $wrk;
}

// Filter for 'Starts With A' (example)
function GetStartsWithAFilter($FldExpression, $dbid = 0)
{
    return $FldExpression . Like("'A%'", $dbid);
}

// Global user functions
<#= GetServerScript("Global", "Database_Connecting") #>
<#= GetServerScript("Global", "Database_Connected") #>
<#= GetServerScript("Global", "MenuItem_Adding") #>
<#= GetServerScript("Global", "Menu_Rendering") #>
<#= GetServerScript("Global", "Menu_Rendered") #>
<#= GetServerScript("Global", "Page_Loading") #>
<#= GetServerScript("Global", "Page_Rendering") #>
<#= GetServerScript("Global", "Page_Unloaded") #>
<#= GetServerScript("Global", "AuditTrail_Inserting") #>
<#= GetServerScript("Global", "PersonalData_Downloading") #>
<#= GetServerScript("Global", "PersonalData_Deleted") #>
<#= GetServerScript("Global", "Route_Action") #>
<#= GetServerScript("Global", "Api_Action") #>
<#= GetServerScript("Global", "Container_Build") #>
<#= GetServerScript("Global", "Global Code") #>
