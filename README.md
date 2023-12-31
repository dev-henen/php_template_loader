# Template Loader Library

The Template Loader library provides a simple and flexible way to parse and render templates in PHP. It allows you to include templates within templates, loop through arrays and objects, and easily set and retrieve parameters. The library is designed to be lightweight and efficient, making it a valuable tool for dynamic content generation.

## Getting Started

### Including the Template Parser File

Include the `template_loader.php` file in your project:

```php
require_once 'template_loader.php';
```

### Loading the Parser Class

Create an instance of the `template\Loader` class:

```php
$template = new template\Loader(
    string "template_name",
    string "templates_folder (optional)",
    int "max_template_includes_per_template (optional)",
    array $template_caching = ['allow' => true|false, 'max_store_age' => int:hours|default:24] optional
);
```

- `template_name`: The name of the template file to load.
- `templates_folder`: Optional. The folder where templates are located. Default is the 'tmpl' folder at the root of your project.
- `max_template_includes_per_template`: Optional. The maximum number of template includes allowed per template.
- `template_caching`: Optional. Configuration for template caching.

### Setting Template Parameters

Set parameters in the template using the `set` method:

```php
$template->set("parameter_name", "parameter_value");
```

In the template, retrieve the parameter using:

```html
<!--[parameter_name]-->
```

### Controlling Error and Warning Display

Specify whether to show errors and warnings using the following properties:

```php
$template->show_errors = true|false; // Default is false
$template->show_warnings = true|false; // Default is true
```

### Including Other Templates

To include a template file in another template file, use the following syntax:

```html
<!--include[template_file_name]-->
```

### Looping Through Data

#### Associative Array, Multidimensional Associative Array, Objects

```php
$template->forEach("identifier", $array_or_object);
```

In the template, use the following syntax to loop through and render:

```html
<!--forEach[identifier]-->
    <!--{array_key}-->
<!--end[identifier]-->
```

#### Index Array

```php
$template->for("array_identifier", $index_array);
```

In the template, use the following syntax to loop through and render:

```html
<!--for[array_identifier]-->
    <!--{value}-->
<!--end[array_identifier]-->
```

### Rendering the Template

After setting parameters and defining loops, render the template:

```php
$template->render(true|false); // Default is true (keep HTML comments)
```

## Examples

### Parsing Parameters in a Template

**parsing_parameter.php**

```php
<?php
$template = new henen_template\Loader("parsing_parameter");
$text = 'Hello World!';
$template->set('text_param', $text);
$template->render();
?>
```

**parsing_parameter.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Parsing Parameter in templates</title>
</head>
<body>
    <h1>Parsed parameter is: <!--[text_param]--> </h1>
</body>
</html>
```

### Including Other Templates in a Template

**include_template.php**

```php
<?php
$template = new henen_template\Loader("include_template");
$template->render();
?>
```

**include_template.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Including templates in other template</title>
</head>
<body>
    <!--include[header]-->
</body>
</html>
```

**header.tpl**

```html
<h1>Hello World!</h1>
<h2>I am a header file</h2>
```

### Looping Through Index Array Values in a Template

**index_array.php**

```php
<?php
$template = new henen_template\Loader("index_array");
$week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$template->for("week_days", $week_days);
$template->render();
?>
```

**index_array.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and rendering index array values</title>
</head>
<body>
    <p>The days of the week are:</p>
    <ol>
        <!--for[week_days]-->
        <li><!--{value}--></li>
        <!--end[week_days]-->
    </ol>
</body>
</html>
```

### Looping and Parsing Associative Array Values

**associative_array.php**

```php
<?php
$template = new henen_template\Loader("associative_array");
$user = [
    'username' => 'John Doe',
    'email' => 'johndoe@email.com',
    'location' => 'Big City',
    'about' => 'Hi! I am John Doe, a passionate fullstack web developer.'
];
$template->forEach("user", $user);
$template->render();
?>
```

**associative_array.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and rendering associative array values</title>
</head>
<body>
    <p>User Profile:</p>
    <div>
        <!--forEach[user]-->
        <div style="width:200px;text-align:center;">
            <p> <b> <!--{username}--> </b> </p>
        </div>
        <small>
            <ul>
                <li> <b>Email:</b> <!--{email}--> </li>
                <li> <b>Home:</b> <!--{location}--> </li>
            </ul>
            <p> <b>About me:</b> <br/> <!--{about}--> </p>
        </small>
        <!--end[user]-->
    </div>
</body>
</html>
```

### Looping and Rendering Multidimensional Associative Array Values

**associative_multidimensional_array.php**

```php
<?php
$template = new henen_template\Loader("associative_multidimensional_array");
$students = [
    ['id' => 1, 'name' => 'John Doe', 'age' => 2 ,'class' => 'Basic 1'],
    ['id' => 2, 'name' => 'Mr Henen', 'age' => 4 ,'class' => 'Basic 5'],
    ['id' => 3, 'name' => 'James Gosling', 'age' => 1 ,'class' => 'Basic 1'],
    ['id' => 4, 'name' => 'Jane Doe', 'age' => 3 ,'class' => 'Basic 2']
];
$template->forEach("students", $students);
$template->render();
?>
```

**associative_multidimensional_array.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and rendering associative multidimensional array values</title>
</head>
<body>
    <p>List of our students:</p>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Age</th>
            <th>

Class</th>
        </tr>

        <!--forEach[students]-->
        <tr>
            <td> <!--{id}--> </td>
            <td> <!--{name}--> </td>
            <td> <!--{age}--> </td>
            <td> <!--{class}--> </td>
        </tr>
        <!--end[students]-->

    </table>
</body>
</html>
```

### Looping and Parsing Object Property/Values

**object.php**

```php
<?php
$template = new henen_template\Loader("object");
class User {
    public $username;
    public $email;
    public $location;
    function __construct($username, $email, $location) {
        $this->username = $username;
        $this->email = $email;
        $this->location = $location;
    }
}
$user = new User('Jane Doe', 'janedoe@email.com', 'Small City');
$template->forEach("user", $user);
$template->render();
?>
```

**object.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and rendering associative array values</title>
</head>
<body>
    <p>User Profile:</p>
    <div>
        <!--forEach[user]-->
        <div style="width:200px;text-align:center;">
            <p> <b> <!--{username}--> </b> </p>
        </div>
        <small>
            <ul>
                <li> <b>Email:</b> <!--{email}--> </li>
                <li> <b>Home:</b> <!--{location}--> </li>
            </ul>
        </small>
        <!--end[user]-->
    </div>
</body>
</html>
```

Feel free to use and extend this Template Loader library to suit your project's needs. Happy coding!