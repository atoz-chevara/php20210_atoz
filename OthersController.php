<## Common config #>
<#= include('shared/config-common.php') #>
<#

    // Get default start page
    let startPage = PROJ.StartPage || ""; // P13
    if (SameText(startPage, "index.php") || SameText(startPage, "index"))
        startPage = ""; // Make sure not index.php
    let startFile = startPage.trim();
    startFile = startFile.substr(startFile.lastIndexOf("/") + 1); // Remove path
    if (startFile.includes("?"))
        startFile = startFile.substr(0, startFile.indexOf("?")); // Remove querystring
    let isCustomUrl = !IsEmpty(startPage);

    // Get Default Table List Page
    let url = "",
        listUrl = "",
        defaultUrl = "",
        defaultTable = null;
    for (let t of TABLES) {
        if (t.TblGen) {
            url = GetRouteUrl(t.TblType == "REPORT" ? t.TblReportType : "list", t); // Get Report/List Page

            if (url == startFile) { // Default start page
                defaultTable = t;
                defaultUrl = startPage;
                isCustomUrl = false;
            }
            if (t.TblDefault && defaultUrl == "") { // Default table
                defaultTable = t;
                defaultUrl = url;
            }
            if (listUrl == "") { // First table
                defaultTable = t;
                listUrl = url;
            }
        }
    } // Table

    if (defaultUrl == "")
        defaultUrl = listUrl;
#>
<?php

namespace <#= ProjectNamespace #>;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

/**
 * Class others controller
 */
class OthersController extends ControllerBase
{

<#
    for (let action of controllerActions.keys()) {
        let pageAction = GetRouteUrl(action),
            pageName = PascalCase(action);
#>
    // <#= pageAction #>
    public function <#= pageAction #>(Request $request, Response $response, array $args): Response
    {
        <# if (["error", "login"].includes(action)) { #>
        global $Error;
        $Error = $this->container->get("flash")->getFirstMessage("error");
        <# } #>
        return $this->runPage($request, $response, $args, "<#= pageName #>");
    }
<#
    }
#>
    <# if (PROJ.UseSwaggerUI) { #>
    // Swagger
    public function swagger(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        $lang = $this->container->get("language");
        $title = $lang->phrase("ApiTitle");
        if (!$title || $title == "ApiTitle") {
            $title = "REST API"; // Default
        }
        $data = [
            "title" => $title,
            "version" => Config("API_VERSION"),
            "basePath" => $basePath
        ];
        $view = $this->container->get("view");
        return $view->render($response, "swagger.php", $data);
    }
    <# } #>

    // Index
    public function index(Request $request, Response $response, array $args): Response
    {
        <# if (isCustomUrl) { #>
        $url = "<#= startPage #>";
        $fn = PROJECT_NAMESPACE . $url;
        if (is_callable($fn)) {
            $url = $fn();
        }
        <# } else if (!isSecurityEnabled) { #>
        $url = "<#= defaultUrl #>";
        <# } else { #>
        global $Security, $USER_LEVEL_TABLES;
        $url = "";
        foreach ($USER_LEVEL_TABLES as $t) {
            if ($t[0] == "<#= Quote(defaultTable.TblName) #>") { // Check default table
                if ($Security->allowList($t[4] . $t[0])) {
                    $url = $t[5];
                    break;
                }
            } elseif ($url == "") {
                if ($t[5] && $Security->allowList($t[4] . $t[0])) {
                    $url = $t[5];
                }
            }
        }
        if ($url === "" && !$Security->isLoggedIn()) {
            $url = "<#= loginPage #>";
        }
        <# } #>
        if ($url == "") {
            $error = [
                "statusCode" => "200",
                "error" => [
                    "class" => "text-warning",
                    "type" => Container("language")->phrase("Error"),
                    "description" => DeniedMessage()
                ],
            ];
            Container("flash")->addMessage("error", $error);
            return $response->withHeader("Location", GetUrl("<#= GetRouteUrl("error") #>"))->withStatus(302); // Redirect to error page
        }
        return $response->withHeader("Location", $url)->withStatus(302);
    }

}
