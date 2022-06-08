<## Common config #>
<#= include('shared/config-common.php') #>
<#
    let userConnInfo = ParseJson(PROJ.ConnInfo);
    let getUserConnInfo = (info) => {
        if (ctrlId == "configprod" && userConnInfo) { // Production config
            let dbId = info.id;
            if (userConnInfo[dbId])
                info = Object.assign({}, info, userConnInfo[dbId]);
        }
        return info;
    }
    let connInfoToString = (info) => {
        let s = Object.entries(info).map(v => `"${Code.quote(v[0])}" => ${v[1] == null ? null : '"' + Code.quote(v[1]) + '"'}`).join(", ");
        return "[" + s + "]";
    }

    // Connections
    let connInfo = getUserConnInfo(DatabaseConnection(DB)),
        connections = new Map([["DB", connInfo]]);
    PROJ.LINKDBS.forEach(db => connections.set(db.DBID, getUserConnInfo(DatabaseConnection(db))));

    // SMTP settings
    let smtpServer = PROJ.SmtpServer || "localhost",
        smtpServerPort = PROJ.SmtpServerPort || 0,
        smtpServerUsername = PROJ.SMTPServerUsername || "",
        smtpServerPassword = PROJ.SMTPServerPassword || "";
    if (smtpServerPort <= 0)
        smtpServerPort = 25;

    // Encryption
    let encryptionKey = PROJ.EncryptionKey || "",
        encryptionEnabled = false;
    if (encryptionKey && PROJ.EncryptUsernamePassword) {
        if (CanPhpEncrypt) {
            smtpServerUsername = PhpEncrypt(smtpServerUsername, encryptionKey, "SMTP server username");
            smtpServerPassword = PhpEncrypt(smtpServerPassword, encryptionKey, "SMTP server password");
        } else {
            console.log("PHP encryption not available. Cannot encrypt user name and password.");
        }
    }

    // JWT
    let apiSecretKey = PROJ.ApiJwtSecretKey || RandomKey(),
        apiAlgorithm = PROJ.ApiJwtAlgorithm || "HS512",
        apiAuthHeader = PROJ.ApiJwtAuthHeader || "X-Authorization",
        apiAccessTimeAfterLogin = IsNumber(PROJ.ApiAccessTimeAfterLogin) ? PROJ.ApiAccessTimeAfterLogin : 0,
        apiExpireTimeAfterLogin = PROJ.ApiExpireTimeAfterLogin || 600;

    // Environment
    let environment = CONTROL.CtrlOFile.replace("config.", "");
#>
<?php

/**
 * PHPMaker 2021 configuration file (<#= PascalCase(environment) #>)
 */

return [
    "Databases" => [
        <#= Array.from(connections.keys()).map(key => JSON.stringify(key) + " => " + connInfoToString(connections.get(key))).join(",\n") #>
    ],
    "SMTP" => [
        "PHPMAILER_MAILER" => "<#= Code.quote(PROJ.PHPMailerMailer || "smtp") #>", // PHPMailer mailer
        "SERVER" => "<#= Code.quote(smtpServer) #>", // SMTP server
        "SERVER_PORT" => <#= smtpServerPort #>, // SMTP server port
        "SECURE_OPTION" => "<#= PROJ.SmtpSecureOption.toLowerCase() #>",
        "SERVER_USERNAME" => "<#= Code.quote(smtpServerUsername) #>", // SMTP server user name
        "SERVER_PASSWORD" => "<#= Code.quote(smtpServerPassword) #>", // SMTP server password
    ],
    "JWT" => [
        "SECRET_KEY" => "<#= Quote(apiSecretKey) #>", // API Secret Key
        "ALGORITHM" => "<#= Quote(apiAlgorithm) #>", // API Algorithm
        "AUTH_HEADER" => "<#= Quote(apiAuthHeader) #>", // API Auth Header (Note: The "Authorization" header is removed by IIS, use "X-Authorization" instead.)
        "NOT_BEFORE_TIME" => <#= apiAccessTimeAfterLogin #>, // API access time before login
        "EXPIRE_TIME" => <#= apiExpireTimeAfterLogin #> // API expire time
    ]
];
