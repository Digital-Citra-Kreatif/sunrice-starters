<?php

declare(strict_types=1);

namespace Sunrice\Starters\RealEstate\Support;

use Illuminate\Support\Collection;
use Sunrice\Admin\Media\Media;
use Sunrice\Cms\Fields\Block;
use Sunrice\Cms\Models\EntryTranslation;
use Sunrice\Cms\Models\Term;
use Sunrice\Cms\Support\Locales;

/**
 * Structured data (schema.org) nodes the templates add to the seo component's graph with :schema.
 * The CMS already prints WebSite, Organization, WebPage/Article and BreadcrumbList.
 */
final class Schema
{
    /**
     * A FAQPage from the page's "faq" blocks.
     *
     * @param  iterable<Block>  $sections
     * @return list<array<string, mixed>>
     */
    public static function faq(iterable $sections): array
    {
        $questions = [];
        foreach ($sections as $block) {
            if ($block->type !== 'faq') {
                continue;
            }
            $items = $block->get('items');
            foreach (is_iterable($items) ? $items : [] as $item) {
                $question = is_array($item) && is_string($item['question'] ?? null) ? $item['question'] : '';
                $answer = is_array($item) && is_string($item['answer'] ?? null) ? $item['answer'] : '';
                if (trim($question) !== '' && trim($answer) !== '') {
                    $questions[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($answer))],
                    ];
                }
            }
        }

        return $questions === [] ? [] : [['@type' => 'FAQPage', 'mainEntity' => $questions]];
    }

    /**
     * A property as a RealEstateListing with its Offer (price, currency, availability), its place and agent.
     *
     * @param  array<string, mixed>  $values
     * @return list<array<string, mixed>>
     */
    public static function property(EntryTranslation $entry, string $title, array $values): array
    {
        $images = $values['gallery'] ?? collect();
        $images = $images instanceof Collection ? $images : collect([$images]);
        // Its place and type are the entry's own terms (one each).
        $location = $entry->termsOf('locations')->first();
        $type = $entry->termsOf('property_types')->first();
        $agent = $values['agent'] ?? null;
        $status = $values['property_status'] ?? 'available';

        $listing = array_filter([
            '@type' => 'RealEstateListing',
            '@id' => $entry->url().'#listing',
            'name' => $title,
            'url' => $entry->url(),
            'description' => $values['summary'] ?? null,
            'datePosted' => $entry->published_at?->toAtomString(),
            'image' => $images->filter(fn ($m) => $m instanceof Media)->map(fn (Media $m) => $m->url('large'))->values()->all() ?: null,
            'about' => array_filter([
                '@type' => self::accommodation($type),
                'numberOfRooms' => $values['bedrooms'] ?? null,
                'numberOfBathroomsTotal' => $values['bathrooms'] ?? null,
                'floorSize' => is_numeric($values['building_area'] ?? null) ? ['@type' => 'QuantitativeValue', 'value' => $values['building_area'], 'unitCode' => 'MTK'] : null,
                'yearBuilt' => $values['year_built'] ?? null,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $values['address'] ?? null,
                    'addressLocality' => $location?->title(),
                    'addressCountry' => 'ID',
                ]),
            ], fn ($v) => $v !== null && $v !== []),
            'offers' => is_numeric($values['price'] ?? null) ? array_filter([
                '@type' => 'Offer',
                'price' => (float) $values['price'],
                'priceCurrency' => 'IDR',
                // A rent is per month or year: say so.
                'priceSpecification' => match ($values['price_suffix'] ?? null) {
                    'per_month' => ['@type' => 'UnitPriceSpecification', 'price' => (float) $values['price'], 'priceCurrency' => 'IDR', 'unitCode' => 'MON', 'referenceQuantity' => ['@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => 'MON']],
                    'per_year' => ['@type' => 'UnitPriceSpecification', 'price' => (float) $values['price'], 'priceCurrency' => 'IDR', 'unitCode' => 'ANN', 'referenceQuantity' => ['@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => 'ANN']],
                    default => null,
                },
                'availability' => in_array($status, ['sold', 'rented'], true) ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
                'businessFunction' => in_array($values['price_suffix'] ?? null, ['per_month', 'per_year', 'month', 'year'], true)
                    ? 'http://purl.org/goodrelations/v1#LeaseOut' : 'http://purl.org/goodrelations/v1#Sell',
                'offeredBy' => $agent instanceof EntryTranslation ? ['@type' => 'RealEstateAgent', 'name' => $agent->title, 'url' => $agent->url()] : null,
            ]) : null,
        ], fn ($v) => $v !== null && $v !== []);

        return [$listing];
    }

    /** schema.org's type for what's on offer, from the property type's slug in the main language. */
    private static function accommodation(?Term $type): string
    {
        $slug = $type?->translation(Locales::default())?->slug;

        return match ($slug) {
            'apartment' => 'Apartment',
            'house', 'villa' => 'SingleFamilyResidence',
            'land' => 'Place',
            'commercial' => 'Place',
            default => 'Accommodation',
        };
    }

    /**
     * An agent as a Person who works for the agency.
     *
     * @param  array<string, mixed>  $values
     * @return list<array<string, mixed>>
     */
    public static function agent(EntryTranslation $entry, string $title, array $values): array
    {
        $photo = $values['photo'] ?? null;

        return [array_filter([
            '@type' => 'Person',
            'name' => $title,
            'jobTitle' => $values['role'] ?? null,
            'telephone' => $values['phone'] ?? null,
            'email' => $values['email'] ?? null,
            'image' => $photo instanceof Media ? $photo->url('medium') : null,
            'url' => $entry->url(),
            'knowsLanguage' => self::languages($values['languages'] ?? []) ?: null,
        ], fn ($v) => $v !== null && $v !== [])];
    }

    /**
     * The languages an agent speaks, as schema.org Languages with their IETF codes, from the select's values.
     *
     * @return list<array{'@type': string, name: string, alternateName: string}>
     */
    private static function languages(mixed $values): array
    {
        $languages = ['english' => ['English', 'en'], 'indonesian' => ['Indonesian', 'id'], 'japanese' => ['Japanese', 'ja'], 'mandarin' => ['Mandarin Chinese', 'zh'], 'dutch' => ['Dutch', 'nl'], 'french' => ['French', 'fr'], 'german' => ['German', 'de']];
        $known = [];
        foreach (is_iterable($values) ? $values : [] as $value) {
            if (is_string($value) && isset($languages[$value])) {
                $known[] = ['@type' => 'Language', 'name' => $languages[$value][0], 'alternateName' => $languages[$value][1]];
            }
        }

        return $known;
    }
}
