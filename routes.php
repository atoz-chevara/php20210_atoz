<## Common config #>
<#= include('shared/config-common.php') #>
<?php

namespace <#= ProjectNamespace #>;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

// Handle Routes
return function (App $app) {

    <# if (ctrlId == "apiroutes") { #>

    // API
    $app->any('/' . Config("API_LOGIN_ACTION"), ApiController::class . ':login')->add(JwtMiddleware::class . ':create')->setName('api/' . Config("API_LOGIN_ACTION")); // login
    $app->any('/' . Config("API_LIST_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_LIST_ACTION")); // list
    $app->any('/' . Config("API_VIEW_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_VIEW_ACTION")); // view
    $app->any('/' . Config("API_ADD_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_ADD_ACTION")); // add
    $app->any('/' . Config("API_EDIT_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_EDIT_ACTION")); // edit
    $app->any('/' . Config("API_DELETE_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_DELETE_ACTION")); // delete
    $app->any('/' . Config("API_FILE_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_FILE_ACTION")); // file
    $app->any('/' . Config("API_LOOKUP_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_LOOKUP_ACTION")); // lookup
    $app->any('/' . Config("API_UPLOAD_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_UPLOAD_ACTION")); // upload
    $app->any('/' . Config("API_JQUERY_UPLOAD_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->setName('api/' . Config("API_JQUERY_UPLOAD_ACTION")); // jupload
    $app->any('/' . Config("API_SESSION_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->setName('api/' . Config("API_SESSION_ACTION")); // session
    $app->any('/' . Config("API_PROGRESS_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->setName('api/' . Config("API_PROGRESS_ACTION")); // session
    $app->any('/' . Config("API_EXPORT_CHART_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->setName('api/' . Config("API_EXPORT_CHART_ACTION")); // chart
<# if (hasUserTable && PROJ.SecRegisterPage) { #>
    $app->any('/' . Config("API_REGISTER_ACTION"), ApiController::class)->add(ApiPermissionMiddleware::class)->setName('api/' . Config("API_REGISTER_ACTION")); // register
<# } #>
    $app->any('/' . Config("API_PERMISSIONS_ACTION") . '[/{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->add(new JwtMiddleware())->setName('api/' . Config("API_PERMISSIONS_ACTION")); // permissions

    // User API actions
    if (function_exists(PROJECT_NAMESPACE . "Api_Action")) {
        Api_Action($app);
    }

    // Other API actions
    $app->any('/[{params:.*}]', ApiController::class)->add(ApiPermissionMiddleware::class)->setName('custom');

    <# } else { #>

<#
    for (let table of controllerTables) {
        let tblVar = table.TblVar,
            tblClassName = PascalCase(tblVar),
            groupAction = tblVar;
        if (PROJ.OutputNameLCase)
            groupAction = groupAction.toLowerCase();
#>
    // <#= tblVar #>
<#
        if (table.TblType == "REPORT") { // Report
            let id = table.TblReportType;
            if (["crosstab", "dashboard", "summary", "custom"].includes(id)) {
                let pageAction = GetRouteUrl(id, table);
                let params = (id == "custom") ? "[/{params:.*}]" : "";
#>
    $app->any('/<#= pageAction #><#= params #>', <#= tblClassName #>Controller::class)->add(PermissionMiddleware::class)->setName('<#= pageAction #>-<#= tblVar #>-<#= id #>'); // <#= id #>
<#
            }
        } else { // Table
            let actions = ["list", "add", "addopt", "view", "edit", "update", "delete", "search", "preview"],
                previewExt = GetExtensionObject("Preview");
            for (let id of actions) {
                let generate =
                    table.TblGen &&
                    (id == "list" || // always generate for list
                    id == "preview" && previewExt && previewExt.Enabled && table.TblIsDetail ||
                    id == "add" && table.TblAdd ||
                    id == "view" && table.TblView ||
                    id == "edit" && table.TblEdit ||
                    id == "update" && table.TblMultiUpdate ||
                    id == "delete" && table.TblDelete ||
                    id == "search" && table.TblSearch) ||
                    id == "addopt" && table.TblAddOpt;
                if (generate) {
                    let pageAction = GetRouteUrl(id, table),
                        keyFields = table.Fields.filter(f => f.FldIsPrimaryKey),
                        params = "";
                    if (["view", "edit", "add", "list", "delete"].includes(id)) { // Supports parameters
                        params = keyFields.map(kf => "{" + kf.FldParm + "}").join("/"); // Parameter is optional
                        if (params) {
                            params = "[/" + params + "]";
                        }
                    }
#>
    $app->any('/<#= pageAction #><#= params #>', <#= tblClassName #>Controller::class . ':<#= id #>')->add(PermissionMiddleware::class)->setName('<#= pageAction #>-<#= tblVar #>-<#= id #>'); // <#= id #>
<#
                }
            }
            // Generate groups
#>
    $app->group(
        '/<#= groupAction #>',
        function (RouteCollectorProxy $group) {
<#
            for (let id of actions) {
                let generate =
                table.TblGen &&
                (id == "list" || // always generate for list
                id == "preview" && previewExt && previewExt.Enabled && table.TblIsDetail ||
                id == "add" && table.TblAdd ||
                id == "view" && table.TblView ||
                id == "edit" && table.TblEdit ||
                id == "update" && table.TblMultiUpdate ||
                id == "delete" && table.TblDelete ||
                id == "search" && table.TblSearch) ||
                id == "addopt" && table.TblAddOpt;
                if (generate) {
                    let keyFields = table.Fields.filter(f => f.FldIsPrimaryKey),
                    params = "";
                    if (["view", "edit", "add", "list", "delete"].includes(id)) { // Supports parameters
                        params = keyFields.map(kf => "{" + kf.FldParm + "}").join("/"); // Parameter is optional
                        if (params) {
                            params = "[/" + params + "]";
                        }
                    }
#>
            $group->any('/' . Config("<#= id.toUpperCase() #>_ACTION") . '<#= params #>', <#= tblClassName #>Controller::class . ':<#= id #>')->add(PermissionMiddleware::class)->setName('<#= groupAction #>/<#= id #>-<#= tblVar #>-<#= id #>-2'); // <#= id #>
<#
                }
            }
#>
        }
    );
<#
        }
    }
#>
<#
    for (let [action, params] of controllerActions.entries()) {
        let pageAction = GetRouteUrl(action);
#>
    // <#= action #>
    $app->any('/<#= pageAction #><#= params #>', OthersController::class . ':<#= pageAction #>')->add(PermissionMiddleware::class)->setName('<#= pageAction #>');
<#
    }
#>
    <# if (PROJ.UseSwaggerUI) { #>
    // Swagger
    $app->get('/' . Config("SWAGGER_ACTION"), OthersController::class . ':swagger')->setName(Config("SWAGGER_ACTION")); // Swagger
    <# } #>

    // Index
    $app->any('/[<#= indexPage #>]', OthersController::class . ':index')->add(PermissionMiddleware::class)->setName('index');

    // Route Action event
    if (function_exists(PROJECT_NAMESPACE . "Route_Action")) {
        Route_Action($app);
    }

    /**
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: Make sure this route is defined last.
     */
    $app->map(
        ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        '/{routes:.+}',
        function ($request, $response, $params) {
            $error = [
                "statusCode" => "404",
                "error" => [
                    "class" => "text-warning",
                    "type" => Container("language")->phrase("Error"),
                    "description" => str_replace("%p", $params["routes"], Container("language")->phrase("PageNotFound")),
                ],
            ];
            Container("flash")->addMessage("error", $error);
            return $response->withStatus(302)->withHeader("Location", GetUrl("<#= GetRouteUrl("error") #>")); // Redirect to error page
        }
    );

    <# } #>
};
