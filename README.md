## Way-End
#### Install package
```bash
    composer require yabafinet/way-end
```
##### - ___Requeriments: php >= 7.2___

#### 1. Configuring route and load your components.
- Configuring your endpoint file.
- [x] Create a file in your public folder project `way-end.php` and put this code:

```php
    use Yabafinet\WayEnd\WayEndService;
    
    $componentBuild = new WayEndService($_REQUEST);
    
    // route/url to your components
    $componentBuild->route('/way-end');
    
    // path to your components folder
    $componentBuild->loadComponents('resources/components');
    
    $componentBuild->catchActions();
    
    $componentBuild->compileJs();
```

#### 2. Create your first component.
- Create a file in your `resources/components` folder. Example: `resources/components/MyComponent.php` and put this code:

```php
    use Yabafinet\WayEnd\Vue\VueComponent;
    
    class MyComponent extends VueComponent
    {
        public $user_id = 1;
        public $post_comment = 'My comment';
        
        public function like($post_id)
        {
            DB::table('likes')->insert([
                'user_id' => $this->user_id,
                'post_id' => $post_id
            ]);
        }
        
        public function template()
        {
            return <<<HTML
                <div>
                    <h1>My Component</h1>
                    <p>Post Comment: {{ post_comment }}</p>
                    <button v-on:click="like(1)">Like</button>
                </div>
            HTML;
        }
    }
```

#### 3. Load your component in your view.
