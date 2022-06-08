<## Common config #>
<#= include('shared/config-common.php') #>
<?php

namespace <#= ProjectNamespace #>;

use Slim\Views\PhpRenderer;
use Slim\Csrf\Guard;
use Psr\Container\ContainerInterface;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Doctrine\DBAL\Logging\LoggerChain;
use Doctrine\DBAL\Logging\DebugStack;

return [
    "cache" => function (ContainerInterface $c) {
        return new \Slim\HttpCache\CacheProvider();
    },
    "view" => function (ContainerInterface $c) {
        return new PhpRenderer("<#= FolderPath("_views", true) #>");
    },
    "flash" => function (ContainerInterface $c) {
        return new \Slim\Flash\Messages();
    },
    "audit" => function (ContainerInterface $c) {
        $logger = new Logger("audit"); // For audit trail
        $logger->pushHandler(new AuditTrailHandler("<#= IncludeTrailingSlash(PROJ.AuditTrailPath) #>audit.log"));
        return $logger;
    },
    "log" => function (ContainerInterface $c) {
        global $RELATIVE_PATH;
        $logger = new Logger("log");
        $logger->pushHandler(new RotatingFileHandler($RELATIVE_PATH . "<#= IncludeTrailingSlash(PROJ.AuditTrailPath) #>log.log"));
        return $logger;
    },
    "sqllogger" => function (ContainerInterface $c) {
        $loggers = [];
        if (Config("DEBUG")) {
            $loggers[] = $c->get("debugstack");
        }
        <# if (PROJ.LogSqlToFile) { #>
        $loggers[] = $c->get("debugsqllogger");
        <# } #>
        return (count($loggers) > 0) ? new LoggerChain($loggers) : null;
    },
    "csrf" => function (ContainerInterface $c) {
        global $ResponseFactory;
        return new Guard($ResponseFactory, Config("CSRF_PREFIX"));
    },
    "debugstack" => \DI\create(DebugStack::class),
    "debugsqllogger" => \DI\create(DebugSqlLogger::class),
    "security" => \DI\create(AdvancedSecurity::class),
    "profile" => \DI\create(UserProfile::class),
    "language" => \DI\create(Language::class),
    "timer" => \DI\create(Timer::class),
    "session" => \DI\create(HttpSession::class),

    // Tables
    <#
        for (let t of TABLES) {
            if (!(t.TblType == "REPORT" && t.TblReportType == "custom" && !(EndsText(t.TblName, ".php") && t.IncludeFiles))) {
    #>
    "<#= t.TblVar #>" => \DI\create(<#= PascalCase(t.TblVar) #>::class),
    <#
            }
        }
    #>

    <# if (hasUserTable) { #>
    // User table
    "usertable" => \DI\get("<#= secTblVar #>"),
    <# } #>
];
