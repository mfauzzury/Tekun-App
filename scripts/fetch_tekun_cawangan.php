<?php

/**
 * One-off script: fetch all TEKUN branch entries from senarai-pejabat-cawangan.
 * Usage: php scripts/fetch_tekun_cawangan.php
 */

$htmlToText = static function (?string $html): string {
    if ($html === null || $html === '') {
        return '';
    }
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return trim($text);
};

$fetchPage = static function (int $page) use ($htmlToText): array {
    $url = $page === 1
        ? 'https://www.tekun.gov.my/senarai-pejabat-cawangan/'
        : "https://www.tekun.gov.my/senarai-pejabat-cawangan/?&cn-pg={$page}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SPPT-POC/1.0)',
    ]);
    $html = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($html === false || $status >= 400) {
        throw new RuntimeException("Failed to fetch page {$page} (HTTP {$status})");
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $items = $xpath->query("//div[contains(@class,'cn-list-item')]");

    $rows = [];
    foreach ($items as $item) {
        if (! $item instanceof DOMElement) {
            continue;
        }

        $slug = $item->getAttribute('data-entry-slug');
        $externalId = $item->getAttribute('data-entry-id');
        $classes = explode(' ', $item->getAttribute('class'));
        $negeriClass = '';
        foreach ($classes as $class) {
            if (! in_array($class, ['cn-list-row', 'cn-list-row-alternate', 'cn-list-item', 'vcard', 'organization', 'col-lg-6', 'col-md-6', 'col-sm-12', 'col-xs-12'], true)) {
                $negeriClass = $class;
                break;
            }
        }

        $titleNode = $xpath->query(".//div[contains(@class,'cn-tekun-title')]//strong", $item)->item(0);
        $title = $htmlToText($titleNode?->textContent ?? '');

        $streetParts = [];
        foreach ($xpath->query(".//span[contains(@class,'street-address')]", $item) as $street) {
            $streetParts[] = trim($street->textContent ?? '');
        }
        $postal = trim($xpath->evaluate("string(.//span[contains(@class,'postal-code')])", $item));
        $locality = trim($xpath->evaluate("string(.//span[contains(@class,'locality')])", $item));
        $region = trim($xpath->evaluate("string(.//span[contains(@class,'region')])", $item));

        $addressParts = array_filter([implode(', ', $streetParts), trim("{$postal} {$locality}"), $region]);
        $address = implode(', ', $addressParts);

        $phone = '';
        $fax = '';
        foreach ($xpath->query(".//div[contains(@class,'cn-tekun-contact')]//div", $item) as $line) {
            $text = trim($line->textContent ?? '');
            if (str_contains($line->C14N(), 'fa-phone')) {
                $phone = ltrim(str_replace(':', '', preg_replace('/^.*?:\s*/', '', $text) ?? $text));
            }
            if (str_contains($line->C14N(), 'fa-fax')) {
                $fax = ltrim(str_replace(':', '', preg_replace('/^.*?:\s*/', '', $text) ?? $text));
            }
        }

        $given = trim($xpath->evaluate("string(.//span[contains(@class,'contact-given-name')])", $item));
        $family = trim($xpath->evaluate("string(.//span[contains(@class,'contact-family-name')])", $item));
        $contact = trim("{$given} {$family}");

        $branchType = 'cawangan';
        if (stripos($title, 'Pejabat Negeri') !== false) {
            $branchType = 'negeri';
        } elseif (stripos($title, 'Ibu Pejabat') !== false) {
            $branchType = 'ibu_pejabat';
        }

        $rows[] = [
            'code' => $slug ?: ('cawangan-'.$externalId),
            'name' => $title,
            'branch_type' => $branchType,
            'negeri' => $region ?: ucwords(str_replace('-', ' ', $negeriClass)),
            'locality' => $locality,
            'postal_code' => $postal,
            'address' => $address,
            'phone' => $phone,
            'fax' => $fax,
            'contact_person' => $contact,
            'external_id' => $externalId,
            'is_active' => true,
        ];
    }

    return $rows;
};

$all = [];
$seen = [];
for ($page = 1; $page <= 20; $page++) {
    echo "Fetching page {$page}...\n";
    $rows = $fetchPage($page);
    if ($rows === []) {
        break;
    }
    foreach ($rows as $row) {
        $key = $row['code'];
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $row['sort_order'] = count($all) + 1;
        $all[] = $row;
    }
}

// HQ footer block
$all[] = [
    'code' => 'ibu-pejabat',
    'name' => 'Ibu Pejabat TEKUN Nasional',
    'branch_type' => 'ibu_pejabat',
    'negeri' => 'Kuala Lumpur',
    'locality' => 'Bandar Tasik Selatan',
    'postal_code' => '57000',
    'address' => 'Menara TEKUN, T5-01-01, Maju Link, Jalan Lingkaran Tengah 2, 57000 Bandar Tasik Selatan, Kuala Lumpur',
    'phone' => '03-9059 8888',
    'fax' => null,
    'contact_person' => null,
    'external_id' => null,
    'is_active' => true,
    'sort_order' => count($all) + 1,
];

$out = __DIR__.'/../database/seeders/data/tekun_cawangan.json';
if (! is_dir(dirname($out))) {
    mkdir(dirname($out), 0777, true);
}
file_put_contents($out, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Saved '.count($all)." branches to {$out}\n";
