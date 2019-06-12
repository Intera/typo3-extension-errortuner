# TYPO3 errortuner

This Extension improves the Frontend error handling for TYPO3.

## Features

* Redirect to login form if user is not logged in and tries to access an access protected page
* Consistent error handling between TYPO3 and webserver using PHP includes

## How to use

### TypoScript

Include the TypoScript files at `Configuration/TypoScript/LoginRedirect` to enable redirection to
the login form if user is not authenticated and tries to access a protected page.

### Site configuration

```
errorHandling:
  - errorCode: 403
    errorHandler: PHP
    errorPhpClassFQCN: Int\Errortuner\PageErrorHandler\AccessDeniedErrorHandler
  - errorCode: 404
    errorHandler: PHP
    errorPhpClassFQCN: Int\Errortuner\PageErrorHandler\PhpIncludeErrorHandler
  - errorCode: 503
    errorHandler: PHP
    errorPhpClassFQCN: Int\Errortuner\PageErrorHandler\PhpIncludeErrorHandler
```

### TYPO3_CONF_VARS

Configure the files that should be included for the different error types:

```yaml
EXTCONF:
    errortuner:
        errorIncludes:
            403: EXT:mysite/Resources/Public/Errors/403-forbidden.php
            404: EXT:mysite/Resources/Public/Errors/404-not-found.php
            503: EXT:mysite/Resources/Public/Errors/503-service-temporarily-unavailable.php
```

This should be configured as a fallback in case no site can be detected:

```yaml
FE:
    pageNotFound_handling: "USER_FUNCTION: Int\\Errortuner\\PageErrorHandler\\PhpIncludeUserFunc->handleError404"
    pageUnavailable_handling: "USER_FUNCTION: Int\\Errortuner\\PageErrorHandler\\PhpIncludeUserFunc->handleError503"
```

### .htaccess

```
ErrorDocument 403 /typo3conf/ext/mysite/Resources/Public/Errors/403-forbidden.php
ErrorDocument 404 /typo3conf/ext/mysite/Resources/Public/Errors/404-not-found.php
ErrorDocument 500 /typo3conf/ext/mysite/Resources/Public/Errors/500-internal-server-error.php
ErrorDocument 503 /typo3conf/ext/mysite/Resources/Public/Errors/503-service-temporarily-unavailable.php
```
