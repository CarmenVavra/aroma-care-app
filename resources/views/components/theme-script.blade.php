{{-- Apply the stored theme (or the device setting) before paint to avoid a flash. --}}
<script>
    (function () {
        var mode = null;
        try { mode = localStorage.getItem('aromacare-theme'); } catch (e) {}
        var dark = mode === 'dark' || (mode !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
