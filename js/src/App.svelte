<script lang="ts">
    import { onMount } from 'svelte';
    import Progress from './component/progress.svelte';

    export let ghwpNonce: string;

    let postCount: number = 1;
    let postsProcessed: number | null = null;
    let selectedType: string | null = null;
    let allowedTypes;

    let percentage: number;
    let statusText: string;

    let errorCount: number = 0;
    let updatedCount: number = 0;
    let skippedCount: number = 0;

    let isRunning: boolean = false;

    async function getAllowedBlockTypes(): Promise<void> {
        const response = await fetch('/wp-json/ghwp/v1/block-updater/types', {
            credentials: "same-origin",
            headers: {
                'X-WP-Nonce': ghwpNonce,
            },
        });
        allowedTypes = await response.json();
    }

    async function processPost(postId): Promise<void> {
        const r = await fetch(
            `/wp-json/ghwp/v1/block-updater/update-post?postId=${postId}&block=${selectedType}`,
            {
                credentials: "same-origin",
                headers: {
                    'X-WP-Nonce': ghwpNonce,
                },
            }
        );
        postsProcessed = postsProcessed + 1;

        if (!r.ok) {
            errorCount = errorCount + 1;
            return;
        }

        const response = await r.json();
        if (response === true) {
            updatedCount = updatedCount + 1;
        } else {
            skippedCount = skippedCount + 1;
        }
    }

    async function run(): Promise<void> {
        if (!selectedType) {
            return;
        }

        isRunning = true;
        const response = await fetch('/wp-json/ghwp/v1/block-updater/posts', {
            credentials: "same-origin",
            headers: {
                'X-WP-Nonce': ghwpNonce,
            },
        });
        const posts = await response.json();

        postCount = posts.length;

        for (let post of posts) {
            await processPost(post);
        }

        isRunning = false;
    }

    onMount(async () => {
        await getAllowedBlockTypes();
        postsProcessed = 0;
    });

    $: percentage = postsProcessed / (postCount / 100);
    $: statusText = `${Math.floor(percentage)}% (${postsProcessed}/${postCount})`;
</script>

<main>
    <Progress
        max="{postCount}"
        value="{postsProcessed}"
        label="{statusText}"
    />

    {#if !isRunning}
        <div class="ghbu-controls">
            {#if allowedTypes?.length}
                <select name="allowed-types" bind:value={selectedType}>
                    {#each allowedTypes as type}
                        <option value="{type}">{type}</option>
                    {/each}
                </select>

                <button
                    type="button"
                    class="btn button button-primary"
                    disabled="{(!selectedType)}"
                    on:click={run}
                >
                    Start
                </button>
            {/if}
        </div>
    {/if}

    {#if isRunning}
        <div class="ghbu-display">
            <div class="ghbu-status">{statusText}</div>
            <div class="ghbu-status-details">
                <span class="ghbu-processed">Processed: {postsProcessed}</span> /
                <span class="ghbu-updated">Updated: {updatedCount}</span> /
                <span class="ghbu-skipped">Skipped: {skippedCount}</span> /
                <span class="ghbu-errors">Errors: {errorCount}</span>
            </div>
        </div>
    {/if}
</main>

<style>
    .ghbu-display {
        display: flex;
        flex-direction: column;
        padding: 2rem 0;
    }

    .ghbu-updated {
        color: #0a3;
    }

    .ghbu-skipped {
        color: #04d;
    }

    .ghbu-errors {
        color: #a00;
    }

    .ghbu-status {
        font-weight: 600;
        font-size: 1.2em;
        line-height: 1.5;
    }
</style>
