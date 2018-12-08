# Helium Documentation

## Table of Contents

* [He2App](#he2app)
    * [init](#init)
    * [loadNamespacedComponents](#loadnamespacedcomponents)
    * [loadNormalComponents](#loadnormalcomponents)
* [He2Registry](#he2registry)
    * [__set](#__set)
    * [__get](#__get)
* [He2Router](#he2router)
    * [__construct](#__construct)
    * [setPath](#setpath)
    * [loader](#loader)
    * [executeControllerAction](#executecontrolleraction)
    * [renderTemplate](#rendertemplate)
* [He2Template](#he2template)
    * [__construct](#__construct-1)
    * [templateExtensionLoader](#templateextensionloader)
    * [loadTemplateExtensions](#loadtemplateextensions)
    * [__set](#__set-1)
    * [__get](#__get-1)
    * [show](#show)
    * [content](#content)
    * [header](#header)
    * [cleanup](#cleanup)
* [HeliumConsole](#heliumconsole)
    * [init](#init-1)
    * [loadNamespacedComponents](#loadnamespacedcomponents-1)
    * [loadNormalComponents](#loadnormalcomponents-1)
    * [loadCommandLine](#loadcommandline)
* [Redirect](#redirect)
    * [__construct](#__construct-2)
    * [getUrl](#geturl)
    * [executeRedirect](#executeredirect)

## He2App

The main application for instantiaing the He2MVC Framework and bringing
together the parts required for the system to work.

The application is what is called with Helium is first initiliaed in the frontend controller. It will autoload the components,
set the registry and then send the application into the router. The boostrap of the framework should be called sometime during
this point.

* Full name: \prodigyview\helium\He2App
* Parent class: 


### init

Initializes the application to start Helium

```php
He2App::init(  )
```



* This method is **static**.



---

### loadNamespacedComponents

With autoload the components that are namespaced

```php
He2App::loadNamespacedComponents( string $class ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** | The name of the class to be loaded |




---

### loadNormalComponents

Will autoload the components that do not have a namespace

```php
He2App::loadNormalComponents( string $class ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** | The name of the class to be loaded |




---

## He2Registry

The registry acts a way to share resources across the different components through the apps execution.

For example, in the registry we can assign a variable when app is initialized, and then call the variables at
a different point of execution such as the controller.

* Full name: \prodigyview\helium\He2Registry
* Parent class: 


### __set

Sets a value to be stored in the registry

```php
He2Registry::__set( string $index, mixed $value ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$index` | **string** | The key for the storing an item in the registry |
| `$value` | **mixed** | An value to store. |




---

### __get

Retrieves an item from the registry.

```php
He2Registry::__get( string $index ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$index` | **string** | The key where the item is stored |


**Return Value:**

The returned item



---

## He2Router

The router class is where the applications primary execution occurs. The class will take information
from the router, call the correct controller and also call the correct view.



* Full name: \prodigyview\helium\He2Router
* Parent class: 


### __construct

Called with the Router is initalized.

```php
He2Router::__construct( object $registry ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$registry` | **object** | Passes in the global registry |




---

### setPath

Sets the path to controller folder to tell where the controllers
should be found when being instantiated.

```php
He2Router::setPath( string $path ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **string** | The local path to the controllers folder |




---

### loader

Calls a controller based on the route and then passes the variables retrieved from the controller
to a view.

```php
He2Router::loader(  ): void
```







---

### executeControllerAction

Based on the parameters from the route, this function will execute the action method
in the controller

```php
He2Router::executeControllerAction( object $controller, string $action ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$controller` | **object** | an instance of the controller object |
| `$action` | **string** | The action to call |


**Return Value:**

Either should return an array of elements from the controller, Redirect function or void



---

### renderTemplate

Passses the variable from the controller into the view and then renders the
view.

```php
He2Router::renderTemplate( object $controller, array $vars = array() ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$controller` | **object** | Instantiated object of the current controller |
| `$vars` | **array** | Variables to be passed to the template |




---

## He2Template

This class is designed to act as the template parser that will render html found in the templates folders and the views
folder of each site.



* Full name: \prodigyview\helium\He2Template
* Parent class: 


### __construct

The constrcutor for the template.

```php
He2Template::__construct( object $registry = null, \prodigyview\helium\PVRequests $request = null ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$registry` | **object** | The global registry object |
| `$request` | **\prodigyview\helium\PVRequests** | A requests object |




---

### templateExtensionLoader

Loads classes in the extensions/template folder. The files become helpers in the view.

```php
He2Template::templateExtensionLoader( string $class ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** | Name of the class to include |




---

### loadTemplateExtensions

Register an spl auto loader for helper files that will become part of the template

```php
He2Template::loadTemplateExtensions(  ): void
```







---

### __set

Magig function, Set an instance or string of a class to be called in the view

```php
He2Template::__set( string $index, string $value ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$index` | **string** | The key to reference when calling the object |
| `$value` | **string** | The name of the class to call |




---

### __get

Magic Function, calls the object to be used in the view or template

```php
He2Template::__get( string $index ): Object
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$index` | **string** | The key of the object being called. |


**Return Value:**

Will return an instance of an object



---

### show

Includes the view that will be displayed.

```php
He2Template::show( array $view, array $template )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$view` | **array** | Contains the arguements that define the view that will be displayed.
			-'view' _string_: The folder that the view will reside in
			-'prefix' _string_: The first part of the view. If the view is add.html.php, the add would be the prefix. Default value is index
			-'type' _string_: The format for the view. The default is html.
			-'exenstion' _string_: The extension of the view. The default extension is .php |
| `$template` | **array** | Contains the arguements that define the template that will be displayed
			-'prefix' _string_: The first part of the template. The default value for the prefix is 'default'
			-'type' _string_: The |




---

### content

Displays the content in a view that will render in a template. Call this function once in the template folder.

```php
He2Template::content(  ): void
```







---

### header

Returns the header to be placed at the top of a template. The header contains tags that will be replaced
at the end of output buffering with the site's title, meta descriptiong, keywords, and additional javascript
libraries. This method should be called between the <head></head> tags in your template file.

```php
He2Template::header(  ): string
```





**Return Value:**

$tags Returns a string of tags that will be placed at the top of the header



---

### cleanup

After the template class is no longer application, we can call a clean up to reduce
reduce resource utilization created from the template.

```php
He2Template::cleanup(  )
```







---

## HeliumConsole

The main application for instantiaing the He2MVC Framework and bringing
together the parts required for the system to work.

The application is what is called with Helium is first initiliaed in the frontend controller. It will autoload the components,
set the registry and then send the application into the router. The boostrap of the framework should be called sometime during
this point.

* Full name: \prodigyview\helium\HeliumConsole
* Parent class: \prodigyview\helium\He2App


### init

Initializes the console and looks for commands to run.

```php
HeliumConsole::init(  )
```



* This method is **static**.



---

### loadNamespacedComponents

With autoload the components that are namespaced

```php
HeliumConsole::loadNamespacedComponents( string $class ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** | The name of the class to be loaded |




---

### loadNormalComponents

Will autoload the components that do not have a namespace

```php
HeliumConsole::loadNormalComponents( string $class ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** | The name of the class to be loaded |




---

### loadCommandLine

The autoload for finding a class in the cli folder
and executing it based on the arguements passed.

```php
HeliumConsole::loadCommandLine( string $class ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** | The name of the class |




---

## Redirect

A specialized class for exectuing the redirects of a user.



* Full name: \prodigyview\helium\Redirect
* Parent class: 


### __construct

Constructor for the redirect object

```php
Redirect::__construct( string $url, array $options = array() ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$url` | **string** | The url to be redirected too |
| `$options` | **array** | An array of options for the redirection |




---

### getUrl

Gets the current url that has been set in the redirect

```php
Redirect::getUrl(  ): string
```





**Return Value:**

$url The set url



---

### executeRedirect

Executes the redirection request to be redirected to the appropiate url

```php
Redirect::executeRedirect(  $response = 302 ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$response` | **** |  |




---



--------
> This document was automatically generated from source code comments on 2018-12-08 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
