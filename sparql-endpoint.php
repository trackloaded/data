<?php
require_once __DIR__ . '/wp-load.php';

$accepts_jsonld = (
    (isset($_GET['sparql_jsonld']) && $_GET['sparql_jsonld'] == '1') ||
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/ld+json') !== false)
);

header('Content-Type: ' . ($accepts_jsonld ? 'application/ld+json' : 'application/sparql-results+json'));

$query = isset($_GET['query']) ? strtolower(trim($_GET['query'])) : '';

$defaultVars = ['name', 'url', 'sameAs', 'dob', 'location', 'image', 'aka', 'bio'];

if (empty($query)) {
    echo json_encode([
        'head' => ['vars' => $defaultVars],
        'results' => ['bindings' => []]
    ]);
    exit;
}

// Load artist profiles
$tags = get_terms([
    'taxonomy'   => 'post_tag',
    'hide_empty' => false,
    'meta_query' => [[
        'key'     => 'is_artist_profile',
        'value'   => '1',
        'compare' => '='
    ]]
]);

$artists = [];

foreach ($tags as $tag) {
    $id        = $tag->term_id;
    $term_key  = 'post_tag_' . $id;
    $name      = $tag->name;
    $url       = get_term_link($tag);
    $dob       = get_field('date_of_birth', $term_key);
    $aka       = get_field('nickname', $term_key);
    $bio       = wp_strip_all_tags(get_field('biography', $term_key));
    $location  = get_field('location', $term_key);
    $image     = get_field('hero_background_override', $term_key) ?: get_field('featured_image', $term_key);
    $image_url = is_array($image) && isset($image['url']) ? $image['url'] : null;

    $sameAs = array_values(array_filter([
        get_field('instagram_url', $term_key),
        get_field('wikidata_link', $term_key)
    ]));

    $artists[] = compact('name', 'url', 'dob', 'aka', 'location', 'image_url', 'sameAs', 'bio');
}

// Detect valid SELECT query
$is_basic_query = (
    strpos($query, 'select') !== false &&
    strpos($query, '?name') !== false &&
    strpos($query, '?url') !== false &&
    (
        strpos($query, 'foaf:name') !== false ||
        strpos($query, '<http://xmlns.com/foaf/0.1/name>') !== false
    ) &&
    (
        strpos($query, 'foaf:homepage') !== false ||
        strpos($query, '<http://xmlns.com/foaf/0.1/homepage>') !== false
    )
);

if ($is_basic_query) {
    // Extract requested vars
    $vars = ['name', 'url'];
    foreach ($defaultVars as $v) {
        if (!in_array($v, $vars) && strpos($query, "?$v") !== false) {
            $vars[] = $v;
        }
    }

    $results = [];
    $jsonld  = [];

    foreach ($artists as $artist) {
        $base = [
            'name' => ['type' => 'literal', 'value' => $artist['name']],
            'url'  => ['type' => 'uri',     'value' => $artist['url']],
        ];

        $jsonEntry = [
            '@type' => 'Person',
            'name'  => $artist['name'],
            'url'   => $artist['url'],
        ];

        // Repeating sameAs if multiple
        if (in_array('sameAs', $vars) && !empty($artist['sameAs'])) {
            foreach ($artist['sameAs'] as $same) {
                $row = $base + ['sameAs' => ['type' => 'uri', 'value' => $same]];
                if (in_array('dob', $vars) && $artist['dob']) $row['dob'] = ['type' => 'literal', 'value' => $artist['dob']];
                if (in_array('location', $vars) && $artist['location']) $row['location'] = ['type' => 'literal', 'value' => $artist['location']];
                if (in_array('image', $vars) && $artist['image_url']) $row['image'] = ['type' => 'uri', 'value' => $artist['image_url']];
                if (in_array('aka', $vars) && $artist['aka']) $row['aka'] = ['type' => 'literal', 'value' => $artist['aka']];
                if (in_array('bio', $vars) && $artist['bio']) $row['bio'] = ['type' => 'literal', 'value' => $artist['bio']];
                $results[] = $row;
            }
        } else {
            $row = $base;
            if (in_array('dob', $vars) && $artist['dob']) $row['dob'] = ['type' => 'literal', 'value' => $artist['dob']];
            if (in_array('location', $vars) && $artist['location']) $row['location'] = ['type' => 'literal', 'value' => $artist['location']];
            if (in_array('image', $vars) && $artist['image_url']) $row['image'] = ['type' => 'uri', 'value' => $artist['image_url']];
            if (in_array('aka', $vars) && $artist['aka']) $row['aka'] = ['type' => 'literal', 'value' => $artist['aka']];
            if (in_array('bio', $vars) && $artist['bio']) $row['bio'] = ['type' => 'literal', 'value' => $artist['bio']];
            $results[] = $row;
        }

        // Build JSON-LD entry
        if (in_array('dob', $vars) && $artist['dob']) $jsonEntry['birthDate'] = $artist['dob'];
        if (in_array('location', $vars) && $artist['location']) $jsonEntry['birthPlace'] = $artist['location'];
        if (in_array('image', $vars) && $artist['image_url']) $jsonEntry['image'] = $artist['image_url'];
        if (in_array('aka', $vars) && $artist['aka']) $jsonEntry['alternateName'] = $artist['aka'];
        if (in_array('bio', $vars) && $artist['bio']) $jsonEntry['description'] = $artist['bio'];
        if (in_array('sameAs', $vars) && !empty($artist['sameAs'])) $jsonEntry['sameAs'] = $artist['sameAs'];

        $jsonld[] = $jsonEntry;
    }

    if ($accepts_jsonld) {
        echo json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => $jsonld
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'head' => ['vars' => $vars],
            'results' => ['bindings' => $results]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    exit;
}

// If not valid
echo json_encode([
    'head' => ['vars' => $defaultVars],
    'results' => ['bindings' => []],
    'error' => 'Only SELECT queries with basic foaf:name, foaf:homepage, and optional variables are supported.'
]);
exit;