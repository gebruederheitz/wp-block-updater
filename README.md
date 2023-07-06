# Wordpress Block Updater

_Update dynamic blocks with innerBlocks_

---

Moving from a React-rendered "static" Gutenberg block _(where the block editor's
`save()` method returns the markup for the block which gets rendered once and
written as static HTML to the post content)_ to a "dynamic" block _(which
is rendered by PHP)_ is trivial. Instead of having save() return the markup,
we make it return `null` and let PHP templates handle the block's frontend
output.
But there's once exception: When the affected block contains "innerBlocks",
their rendering will often still have to be handled by Gutenberg & React,
so we simply return `<InnerBlocks.Content />` from save() – which will result
in invalid blocks, as all existing blocks still contain the full markup, not
just the innerBlocks.
This package offers a REST interface to update such blocks across posts along
with an admin page allowing to run batched updates for all published posts.

> **Warning**
> 
> This is not a generic block updater; it will only work correctly
> for exactly the task described above: Performing a migration of a statically
> rendered block with innerBlocks to a dynamic block.
> 
> Running this tool on a static block will make that block's markup disappear
> (partially or completely) from your Wordpress frontend!

Running the tool against a dynamic block with no innerBlocks will have no
effect.

## Installation

via composer:
```shell
> composer require gebruederheitz/wp-theme-docs
```

Make sure you have Composer autoload or an alternative class loader present.

## Usage

Instantiate the BlockUpdater, for example in your `functions.php`, passing it
an array of qualifying blocks (i.e. those that have moved from static to 
dynamic rendering):

```php
new \Gebruederheitz\Wordpress\BlockUpdater\BlockUpdater(
    ['mynamespace/myblock']
);
```

Now navigate to wordpress/wp-admin/tools.php?page=ghwp-block-updater, select
a block and click "Start" in order to loop over all published posts of types
`page` and `post` and re-render the block content of the selected block if it
exists.

## Example Scenario

### Before

```jsx
registerBlockType('my/block', {
    title: 'My Block',
    icon: 'someicon',
    description: '...',
    category: 'layout',
    styles: [/*...*/],
    attributes: {/*...*/},
    edit(props) {
        /* ... */
        return <MyEditComponent {...props} />;
    },
    /* A React-rendered (static) block with innerBlocks */
    save(props) {
        return (
            <div className="my-component">
                <h1>{props.attributes.title}</h1>
                <InnerBlocks.Content />
            </div>
        );
    },
});
```

### Now

```jsx
registerBlockType('my/block', {
    title: 'My Block',
    icon: 'someicon',
    description: '...',
    category: 'layout',
    styles: [/*...*/],
    attributes: {/*...*/},
    edit(props) {
        /* ... */
        return <MyEditComponent {...props} />;
    },
    /* Only innerBlocks are returned, the rest left to PHP */
    save() {
        return <InnerBlocks.Content />;
    },
});
```

```php
register_block_type('my/block', [
    'editor_script' => 'myblock.js',
    'render_callback' => function(array $attributes = [], string $content = '') {
        foreach ($attributes as $name => $datum) {
            set_query_var($name, $datum);
        }
        if (!empty($content)) {
            set_query_var('innerBlocks', $content);
        }
        if (!empty($attributes['className'])) {
            set_query_var('className', $attributes['className']);
        }
        
        ob_start();
        load_template('template-parts/blocks/my-block.php', false, $data);
        return ob_get_clean();
    },
    'attributes' => [
        'title' => [
            'type' => 'string',
            'default' => 'Hello World',
        ],       
    ],
]);
```

```php
# template-parts/blocks/my-block.php
<?php
    $innerBlocks = get_query_var('innerBlocks');
    $title = get_query_var('title');
?>
<div class="my-block">
    <h1><?= $title ?></h1>
    <?= $innerBlocks ?>
</div>
```

For any page that is not migrated, this will result in the block being duplicated
inside itself, as `$content` still contains the entire rendered block from before
the change.


### After

`$content` will be re-parsed and re-rendered, so it contains only the actual
innerBlocks, making the block work in both front- and backend.


## Development

### Dependencies

- PHP >= 7.4
- [Composer 2.x](https://getcomposer.org)
- nodeJS LTS (v18.x); [asdf](https://asdf-vm.com/guide/getting-started.html) is recommended
- Nice to have: GNU Make (or drop-in alternative)

