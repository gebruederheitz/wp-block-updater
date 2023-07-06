<style>
    <?= file_get_contents(__DIR__ . '/../templates/assets/block-updater.css') ?>
</style>
<div id="app"></div>
<script>
    window.ghwpRestNonce = '<?= wp_create_nonce('wp_rest'); ?>';

    <?= file_get_contents(__DIR__ . '/../templates/assets/block-updater.js') ?>
</script>
