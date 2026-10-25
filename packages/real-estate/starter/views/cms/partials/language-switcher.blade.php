{{--
    The page shown in each language, from the CMS's <x-sunrice-cms::language-switcher> (Cms::localeUrls(), no
    queries of its own), as Lumina's EN/ID pills. A language the page isn't in at all (the main-language fallback
    off) links to that language's homepage (missing="home", the default), so the switcher never disappears.
    hreflang and lang on each link tell search engines and screen readers which language it is; aria-current
    marks the one shown.
--}}
<x-sunrice-cms::language-switcher>
    <nav class="lang-switch" aria-label="{{ __('site.nav.languages') }}">
        @foreach ($component->languages as $language)
            <a href="{{ $language->url }}" hreflang="{{ $language->tag }}" lang="{{ $language->tag }}"{!! ($language->current ? ' aria-current="true"' : '').($language->fallback ? ' data-fallback' : '').($language->home ? ' data-home' : '') !!}>
                <span aria-hidden="true">{{ mb_strtoupper($language->tag) }}</span>
                <span class="visually-hidden">{{ $language->name }}</span>
            </a>
        @endforeach
    </nav>
</x-sunrice-cms::language-switcher>
