import App from './App.svelte';

declare global {
    interface Window {
        ghwpRestNonce: string;
    }
}

const app = new App({
    target: document.getElementById('app'),
    props: {
        ghwpNonce: window.ghwpRestNonce,
    },
});

export default app;
