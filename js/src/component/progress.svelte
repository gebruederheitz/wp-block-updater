<script lang="ts">
    export let value: number|null = null;
    export let max: number = 1;
    export let label: string = '0%';
</script>

<div>
    <progress
        id="progress"
        title="Posts processed"
        {max}
        {value}
    >
        {label}
    </progress>
</div>

<style>
    div {
        margin: 1rem 0;
        padding: .5rem .5rem 1rem 0;
        display: flex;
        width: 99%;
    }

    progress {
        width: 100%;
    }
</style>
