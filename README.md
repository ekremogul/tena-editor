## The Tena Editor Package acts as a parser for the EditorJS package.


### It was created by combining [editor-js/editorjs-php](https://github.com/editor-js/editorjs-php) and [alaminfirdows/laravel-editorjs](https://github.com/editor-js/editorjs-php) packages.

### Special thanks to the developers 


## Installation
You can install the package via composer:

```bash
composer require ekremogul/tena-editor
```

You can publish the config file with:
```bash
php artisan vendor:publish --tag="tena-editor-config"
```
Optionally, you can publish the views using
```bash
php artisan vendor:publish --tag="tena-editor-views"
```

# Usage
```php
use Ekremogul\TenaEditor\TenaEditor;
use App\Models\Post;

$post = Post::find(1);
echo TenaEditor::render($post->body);

// Or use in blade
{!! \Ekremogul\TenaEditor\TenaEditor::render($post->body) !!}
```

#### Defining An Accessor
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ekremogul\TenaEditor\TenaEditor;

class Post extends Model
{
    public function getBodyAttribute(){
        return TenaEditor::render($this->attributes['body']);
    }
}

// usage
$post = Post::find(1);
echo $post->body;
// or use in blade
{!! $post->body !!}
```
