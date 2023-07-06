<script lang="ts">
    import { onMount } from 'svelte';
    import Progress from './component/progress.svelte';

    let postCount: number = 1;
    let postsProcessed: number | null = null;
    let selectedType: string | null = null;
    let allowedTypes;

    let percentage: number;
    let statusText: string;

    let errorCount: number = 0;
    let updatedCount: number = 0;
    let skippedCount: number = 0;

    async function getAllowedBlockTypes(): Promise<void> {
        const response = await fetch('/wp-json/ghwp/v1/block-updater/types');
        allowedTypes = await response.json();
    }

    async function processPost(postId): Promise<void> {
        const r = await fetch(`/wp-json/ghwp/v1/block-updater/update-post?postId=${postId}&block=${selectedType}`);
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

        const response = await fetch('/wp-json/ghwp/v1/block-updater/posts');
        const posts = await response.json();

        postCount = posts.length;

        for (let post of posts) {
            await processPost(post);
        }

        // for (let i = 0; i < posts.length; i++) {
        //   await processPost(posts[i], blockType, i);
        // }
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

    <div id="control">
        {#if allowedTypes}
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

    {#if postCount !== 1}
        <div id="display">
            <div id="status">{statusText}</div>
            <div id="status-details">
                Processed / Updated / Skipped / Errors:
                <span id="processed">{postsProcessed}</span> /
                <span id="updated">{updatedCount}</span> /
                <span id="skipped">{skippedCount}</span> /
                <span id="errors">{errorCount}</span> /
            </div>
        </div>
    {/if}
</main>

<style>
    #updated {
        color: #0a3;
    }

    #skipped {
        color: #04d;
    }

    #errors {
        color: #a00;
    }

    #status {
        font-weight: 600;
        font-size: 1.2em;
        line-height: 1.5;
    }
</style>
