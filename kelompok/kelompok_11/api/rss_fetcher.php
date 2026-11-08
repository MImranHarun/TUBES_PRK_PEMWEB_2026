<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

function get_rss_sources(): array {
    return [
        [
            'id' => 'antara-politik',
            'name' => 'Antara News',
            'category' => 'Politik',
            'badge_color' => 'bg-red-100 text-red-700 border-red-200',
            'url' => 'https://www.antaranews.com/rss/politik.xml'
        ],
        [
            'id' => 'antara-hukum',
            'name' => 'Antara News',
            'category' => 'Hukum & HAM',
            'badge_color' => 'bg-amber-100 text-amber-700 border-amber-200',
            'url' => 'https://www.antaranews.com/rss/hukum.xml'
        ],
        [
            'id' => 'cnn-nasional',
            'name' => 'CNN Indonesia',
            'category' => 'Nasional',
            'badge_color' => 'bg-blue-100 text-blue-700 border-blue-200',
            'url' => 'https://www.cnnindonesia.com/nasional/rss'
        ],
        [
            'id' => 'cnbc-news',
            'name' => 'CNBC Indonesia',
            'category' => 'Kebijakan Publik',
            'badge_color' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'url' => 'https://www.cnbcindonesia.com/news/rss'
        ]
    ];
}

function extract_rss_image(SimpleXMLElement $item): string {
    if (isset($item->enclosure) && isset($item->enclosure['url'])) {
        return (string)$item->enclosure['url'];
    }

    $namespaces = $item->getNamespaces(true);
    if (isset($namespaces['media'])) {
        $media = $item->children($namespaces['media']);
        if (isset($media->content) && isset($media->content->attributes()['url'])) {
            return (string)$media->content->attributes()['url'];
        }
        if (isset($media->thumbnail) && isset($media->thumbnail->attributes()['url'])) {
            return (string)$media->thumbnail->attributes()['url'];
        }
    }

    $desc = (string)$item->description;
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $desc, $matches)) {
        return $matches[1];
    }

    if (isset($namespaces['content'])) {
        $content = (string)$item->children($namespaces['content']);
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
            return $matches[1];
        }
    }

    return 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=600&q=80';
}

function clean_rss_description(string $desc): string {
    $clean = strip_tags($desc);
    $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);

    if (mb_strlen($clean) > 160) {
        $clean = mb_substr($clean, 0, 157) . '...';
    }

    return $clean ?: 'Klik tautan untuk membaca liputan lengkap dinamika regulasi ini.';
}

function format_indo_date(string $dateString): string {
    $timestamp = strtotime($dateString);
    if (!$timestamp) return 'Baru saja';

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit yang lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam yang lalu';
    } elseif ($diff < 172800) {
        return 'Kemarin';
    } else {
        return date('d M Y, H:i', $timestamp) . ' WIB';
    }
}

function get_fallback_news(): array {
    return [
        [
            'title' => 'Pemerintah Dorong Digitalisasi Layanan Publik Melalui SPBE Terpadu',
            'link' => 'https://www.antaranews.com/berita/politik',
            'description' => 'Kementerian terkait mengoptimalkan sistem satu data untuk memastikan efisiensi pelayanan birokrasi dan transparansi.',
            'pubDate' => date('r', time() - 3600),
            'formattedDate' => '1 jam yang lalu',
            'timestamp' => time() - 3600,
            'source' => 'Antara News',
            'category' => 'Kebijakan Publik',
            'badge_color' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'image' => 'https://images.unsplash.com/photo-1577495508048-b635879837f1?auto=format&fit=crop&w=600&q=80'
        ],
        [
            'title' => 'DPR Bahas Efektivitas Penegakan Hukum dan Perlindungan Hak Warga',
            'link' => 'https://www.antaranews.com/berita/hukum',
            'description' => 'Evaluasi regulasi peradilan digelar untuk memperkuat kepastian hukum dan keterbukaan akses bagi publik sipil.',
            'pubDate' => date('r', time() - 7200),
            'formattedDate' => '2 jam yang lalu',
            'timestamp' => time() - 7200,
            'source' => 'Antara News',
            'category' => 'Hukum & HAM',
            'badge_color' => 'bg-amber-100 text-amber-700 border-amber-200',
            'image' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80'
        ]
    ];
}

$filterCategory = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$filterSource   = isset($_GET['source']) ? trim($_GET['source']) : 'all';
$searchKeyword  = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit          = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

$sources = get_rss_sources();
$allNews = [];

libxml_use_internal_errors(true);

foreach ($sources as $source) {
    if ($filterSource !== 'all' && strcasecmp($source['name'], $filterSource) !== 0) {
        continue;
    }

    try {
        $xml = @simplexml_load_file($source['url'], 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml && isset($xml->channel->item)) {
            $count = 0;
            foreach ($xml->channel->item as $item) {
                if ($count >= 15) break;

                $title = trim((string)$item->title);
                $link  = trim((string)$item->link);
                $desc  = (string)$item->description;
                $pubDate = (string)$item->pubDate;
                $timestamp = strtotime($pubDate) ?: time();

                if (empty($title) || empty($link)) continue;

                $allNews[] = [
                    'title' => $title,
                    'link'  => $link,
                    'description' => clean_rss_description($desc),
                    'pubDate' => $pubDate,
                    'formattedDate' => format_indo_date($pubDate),
                    'timestamp' => $timestamp,
                    'source' => $source['name'],
                    'category' => $source['category'],
                    'badge_color' => $source['badge_color'],
                    'image' => extract_rss_image($item)
                ];
                $count++;
            }
        }
    } catch (\Throwable $e) {
        continue;
    }
}

libxml_clear_errors();

if (empty($allNews)) {
    $allNews = get_fallback_news();
}

usort($allNews, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

if ($filterCategory !== 'all' && !empty($filterCategory)) {
    $allNews = array_filter($allNews, fn($item) => strcasecmp($item['category'], $filterCategory) === 0);
}

if (!empty($searchKeyword)) {
    $q = mb_strtolower($searchKeyword);
    $allNews = array_filter($allNews, fn($item) => 
        mb_stripos($item['title'], $q) !== false || mb_stripos($item['description'], $q) !== false
    );
}

$finalData = array_slice(array_values($allNews), 0, $limit);
$availableSources = array_values(array_unique(array_map(fn($s) => $s['name'], $sources)));

http_response_code(200);
echo json_encode([
    'status'            => 'success',
    'message'           => 'Data RSS Berita berhasil diambil',
    'total'             => count($finalData),
    'total_sources'     => count($sources),
    'available_sources' => $availableSources,
    'data'              => $finalData
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;