{{--
    The site's layout. Pages @extends('cms.layout') and fill @section('content'); @push('head') adds to the <head>.
    Optional data from the page: $seoTitle (app pages and the search page), $seoSchema (JSON-LD nodes, see
    cms/collections/properties/show.blade.php), $seoAuthor (an article's author, see cms/collections/journal/show.blade.php).

    Reads the page shown from cms_page(), never from $entry or $term (a page's own loops would overwrite those).
    Everything visitors read comes from the CMS (menus "main", "footer", "legal"; globals "company", "social",
    "footer"; the "newsletter" form) or from lang/{locale}/site.php: nothing here is copy.

    Page transitions (Barba.js, resources/js/app.js): <body data-barba="wrapper"> turns them on (remove the attribute
    to turn them off; previews and drafts load in full), and <main data-barba="container"> is what's swapped. The
    header and footer stay: their [data-swap] parts, the <title>, <html lang>, the SEO tags and the body classes (the
    container's data-body-class) are brought up to the next page by the script. Code outside <main> (Site code
    included) runs on the first page only: listen for the "lumina:page" event (the starter's README).
--}}
@php
    $page = cms_page();
    $locale = app()->getLocale();
    $home = url(\Sunrice\Cms\Support\Locales::prefix($locale) ?: '/');
    $company = cms_global('company');
    $siteName = \Sunrice\Cms\Content::site()->name();
    $transitions = ! ($page?->preview || $page?->draft);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-sunrice-cms::seo :title="$seoTitle ?? null" :schema="$seoSchema ?? []" :author="$seoAuthor ?? null" />
    <link rel="icon" href="{{ asset('lumina/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('lumina/favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('lumina/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0f403d">
    {{-- The starter's built CSS, script and fonts, in public/lumina (sunrice/starter-real-estate's resources/ has their sources). --}}
    @php(\Sunrice\Starters\RealEstate\Assets::use())
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <x-sunrice-cms::code place="head" />
</head>
<body @bodyClass{!! $transitions ? ' data-barba="wrapper"' : '' !!}>
    <x-sunrice-cms::code place="body-start" />
    <a class="skip-link" href="#main">{{ __('site.skip_to_content') }}</a>

    @include('cms.partials.header', ['page' => $page, 'home' => $home, 'company' => $company, 'siteName' => $siteName])

    <main id="main" tabindex="-1" class="outline-none" data-barba="container" data-barba-namespace="{{ $page?->type ?? 'page' }}" data-body-class="{{ cms_body_class() }}">
        @yield('content')
    </main>
    <p class="visually-hidden" aria-live="polite" aria-atomic="true" data-page-announcer></p>

    @include('cms.partials.footer', ['home' => $home, 'company' => $company, 'siteName' => $siteName])

    <x-sunrice-cms::code place="body-end" />
</body>
</html>
