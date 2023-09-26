<?php

declare(strict_types=1);

namespace Gebruederheitz\Wordpress\BlockUpdater;

use Gebruederheitz\Wordpress\AdminPage\AdminPage;
use Gebruederheitz\Wordpress\Rest\RestRoute;
use Gebruederheitz\Wordpress\Rest\Traits\withREST;
use WP_Error;
use WP_REST_Request;

use function get_posts;
use function has_block;
use function parse_blocks;
use function render_block;
use function serialize_block;
use function serialize_blocks;
use function wp_update_post;

/**
 * Moving from a React-rendered "static" Gutenberg block – where the block editor's
 * save() method returns the markup for the block which gets rendered once and
 * written as static HTML to the post content – to a "dynamic" block, which
 * is rendered by PHP, is trivial. Instead of having save() return the markup,
 * we make it return `null` and let PHP templates handle the block's frontend
 * output.
 * But there's once exception: When the affected block contains "innerBlocks",
 * their rendering will often still have to be handled by Gutenberg & React,
 * so we simply return `<InnerBlocks.Content />` from save() – which will result
 * in invalid blocks, as all existing blocks still contain the full markup, not
 * just the innerBlocks.
 * This class offers a REST interface to update such blocks across posts.
 */
class BlockUpdater
{
    use withREST;

    /** @var array<string, callable|true> */
    private array $allowedBlocks;

    public static function factory($allowedBlocks): self
    {
        return new BlockUpdater($allowedBlocks);
    }

    public static function make($allowedBlocks): self
    {
        return new BlockUpdater($allowedBlocks);
    }

    /**
     * @param array<string, callable|true> $allowedBlocks
     */
    public function __construct(array $allowedBlocks = [])
    {
        $this->allowedBlocks = $allowedBlocks;
        $this->initInstanceRestApi();
        AdminPage::factory(
            'ghwp-block-updater',
            'Block-Updater',
            'tools.php',
            null,
            'template-parts/meta/docs/block-updater.php',
        )->addSection(new BlockUpdaterMenuSection());
    }

    protected static function getRestRoutes(): array
    {
        return [];
    }

    protected function getInstanceRestRoutes(): array
    {
        return [
            RestRoute::create(
                'updateBlockForPost',
                '/block-updater/update-post',
            )
                ->allowOnlyEditors()
                ->setMethods('GET')
                ->setCallback([$this, 'updatePost'])
                ->addArgument('postId', 'ID of the post to update')
                ->addArgument('block', 'Name of the block to parse'),
            RestRoute::create(
                'getPublishedPostsAndPages',
                '/block-updater/posts',
            )
                ->allowOnlyEditors()
                ->setMethods('GET')
                ->setCallback([$this, 'getPublishedPostsAndPages']),
            RestRoute::create('getAllowedBlockTypes', '/block-updater/types')
                ->allowOnlyEditors()
                ->setMethods('GET')
                ->setCallback([$this, 'getAllowedBlockTypes']),
        ];
    }

    public function getAllowedBlockTypes(): array
    {
        return array_keys($this->allowedBlocks);
    }

    public function getPublishedPostsAndPages(): array
    {
        return get_posts([
            'numberposts' => -1,
            'nopaging' => true,
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);
    }

    public function updatePost(WP_REST_Request $request)
    {
        $postId = $request->get_param('postId');
        $blockName = $request->get_param('block');

        if (!array_key_exists($blockName, $this->allowedBlocks)) {
            return new WP_Error(403, 'Not an allowed block type');
        }

        $post = get_post($postId);

        if (!$post) {
            return new WP_Error(404, 'No such post');
        }

        $_REQUEST['bulk_edit'] = true;

        if (has_block($blockName, $post)) {
            $blocks = parse_blocks($post->post_content);
            $callback =
                $this->allowedBlocks[$blockName] !== true
                    ? $this->allowedBlocks[$blockName]
                    : [$this, 'defaultCallback'];

            foreach ($blocks as $i => $block) {
                if ($block['blockName'] === $blockName) {
                    $updatedBlock = call_user_func($callback, $block);
                    $blocks[$i] = $updatedBlock;
                }
            }

            $postContent = serialize_blocks($blocks);

            wp_update_post([
                'ID' => $postId,
                'post_content' => $postContent,
            ]);

            return true;
        }

        return false;
    }

    protected function defaultCallback(array $block)
    {
        $blockRendered = '';
        $blockContent = '';

        $updatedBlock = [...$block];
        $updatedBlock['attrs']['blockVersion'] = 2;

        foreach ($block['innerBlocks'] as $innerBlock) {
            $blockRendered .= render_block($innerBlock);
            $blockContent .= serialize_block($innerBlock);
        }

        $updatedBlock['innerContent'] = [$blockContent];
        $updatedBlock['innerHTML'] = $blockRendered;

        return $updatedBlock;
    }
}
