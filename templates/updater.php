<style>
    <?= file_get_contents(__DIR__ . '/../templates/assets/block-updater.css') ?>
</style>
<div id="app"></div>
<script>
    ghwpNonce = '<?= wp_create_nonce('block-updater'); ?>';

    <?= file_get_contents(__DIR__ . '/../templates/assets/block-updater.js') ?>
</script>
