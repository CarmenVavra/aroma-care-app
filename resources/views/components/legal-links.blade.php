<nav aria-label="Rechtliches" {{ $attributes->class(['flex justify-center gap-5 text-[13px]']) }}>
    <a href="{{ route('legal.imprint') }}" class="text-muted underline-offset-2 hover:underline dark:text-chalk/70">Impressum</a>
    <a href="{{ route('legal.privacy') }}" class="text-muted underline-offset-2 hover:underline dark:text-chalk/70">Datenschutz</a>
</nav>
