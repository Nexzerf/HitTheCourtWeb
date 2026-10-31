<?php
// includes/court_svg.php
// ภาพสนามแบบมองจากด้านบน (SVG) ของแต่ละกีฬา ใช้ในแผนที่เลือกสนามหน้าจอง
// ทุกภาพใช้ viewBox 0 0 200 120 (แนวนอน) สีพื้นสนามกำหนดผ่าน CSS (--court-surface)

function courtSvg(string $slug): string {
    $line = 'stroke="rgba(255,255,255,.9)" stroke-width="1.6" fill="none"';
    $surface = '<rect x="0" y="0" width="200" height="120" rx="6" class="court-surface"/>';

    switch ($slug) {
        case 'badminton':
            $body = '<rect x="14" y="14" width="172" height="92" ' . $line . '/>'
                . '<line x1="14" y1="21" x2="186" y2="21" ' . $line . '/><line x1="14" y1="99" x2="186" y2="99" ' . $line . '/>'
                . '<line x1="24" y1="14" x2="24" y2="106" ' . $line . '/><line x1="176" y1="14" x2="176" y2="106" ' . $line . '/>'
                . '<line x1="76" y1="14" x2="76" y2="106" ' . $line . '/><line x1="124" y1="14" x2="124" y2="106" ' . $line . '/>'
                . '<line x1="14" y1="60" x2="76" y2="60" ' . $line . '/><line x1="124" y1="60" x2="186" y2="60" ' . $line . '/>'
                . '<line x1="100" y1="8" x2="100" y2="112" stroke="#0F172A" stroke-width="2.5" stroke-dasharray="2 2"/>'
                . '<circle cx="100" cy="8" r="2.5" fill="#0F172A"/><circle cx="100" cy="112" r="2.5" fill="#0F172A"/>';
            break;

        case 'football':
        case 'futsal':
            $stripes = '';
            if ($slug === 'football') {
                for ($x = 0; $x < 200; $x += 25) $stripes .= '<rect x="' . $x . '" y="0" width="12.5" height="120" fill="rgba(255,255,255,.06)"/>';
            }
            $box = $slug === 'football' ? 28 : 22;
            $body = $stripes
                . '<rect x="10" y="10" width="180" height="100" ' . $line . '/>'
                . '<line x1="100" y1="10" x2="100" y2="110" ' . $line . '/>'
                . '<circle cx="100" cy="60" r="16" ' . $line . '/><circle cx="100" cy="60" r="1.8" fill="#fff"/>'
                . '<rect x="10" y="' . (60 - $box) . '" width="' . ($slug === 'football' ? 28 : 0) . '" height="' . ($box * 2) . '" ' . $line . '/>'
                . '<rect x="' . (190 - ($slug === 'football' ? 28 : 0)) . '" y="' . (60 - $box) . '" width="' . ($slug === 'football' ? 28 : 0) . '" height="' . ($box * 2) . '" ' . $line . '/>'
                . '<rect x="10" y="48" width="11" height="24" ' . $line . '/><rect x="179" y="48" width="11" height="24" ' . $line . '/>'
                . ($slug === 'futsal'
                    ? '<path d="M10 34 A26 26 0 0 1 10 86" ' . $line . '/><path d="M190 34 A26 26 0 0 0 190 86" ' . $line . '/>'
                    : '<path d="M38 50 A12 12 0 0 1 38 70" ' . $line . '/><path d="M162 50 A12 12 0 0 0 162 70" ' . $line . '/>')
                . '<rect x="4" y="52" width="6" height="16" fill="none" stroke="#fff" stroke-width="1.6"/><rect x="190" y="52" width="6" height="16" fill="none" stroke="#fff" stroke-width="1.6"/>';
            break;

        case 'tennis':
            $body = '<rect x="12" y="12" width="176" height="96" ' . $line . '/>'
                . '<line x1="12" y1="24" x2="188" y2="24" ' . $line . '/><line x1="12" y1="96" x2="188" y2="96" ' . $line . '/>'
                . '<line x1="58" y1="24" x2="58" y2="96" ' . $line . '/><line x1="142" y1="24" x2="142" y2="96" ' . $line . '/>'
                . '<line x1="58" y1="60" x2="142" y2="60" ' . $line . '/>'
                . '<line x1="12" y1="60" x2="17" y2="60" ' . $line . '/><line x1="183" y1="60" x2="188" y2="60" ' . $line . '/>'
                . '<line x1="100" y1="6" x2="100" y2="114" stroke="#0F172A" stroke-width="2.5" stroke-dasharray="2 2"/>';
            break;

        case 'volleyball':
            $body = '<rect x="22" y="14" width="156" height="92" ' . $line . '/>'
                . '<line x1="74" y1="14" x2="74" y2="106" ' . $line . '/><line x1="126" y1="14" x2="126" y2="106" ' . $line . '/>'
                . '<line x1="100" y1="6" x2="100" y2="114" stroke="#0F172A" stroke-width="2.5" stroke-dasharray="2 2"/>'
                . '<circle cx="100" cy="6" r="2.5" fill="#0F172A"/><circle cx="100" cy="114" r="2.5" fill="#0F172A"/>';
            break;

        case 'basketball':
            $body = '<rect x="10" y="10" width="180" height="100" ' . $line . '/>'
                . '<line x1="100" y1="10" x2="100" y2="110" ' . $line . '/><circle cx="100" cy="60" r="13" ' . $line . '/>'
                . '<rect x="10" y="44" width="34" height="32" ' . $line . '/><rect x="156" y="44" width="34" height="32" ' . $line . '/>'
                . '<path d="M44 48 A12 12 0 0 1 44 72" ' . $line . '/><path d="M156 48 A12 12 0 0 0 156 72" ' . $line . '/>'
                . '<path d="M10 18 L22 18 A44 44 0 0 1 22 102 L10 102" ' . $line . '/><path d="M190 18 L178 18 A44 44 0 0 0 178 102 L190 102" ' . $line . '/>'
                . '<circle cx="17" cy="60" r="3" fill="none" stroke="#F97316" stroke-width="1.8"/><circle cx="183" cy="60" r="3" fill="none" stroke="#F97316" stroke-width="1.8"/>';
            break;

        case 'table-tennis':
            // โต๊ะปิงปองบนพื้นห้อง
            $surface = '<rect x="0" y="0" width="200" height="120" rx="6" class="court-floor"/>'
                . '<rect x="30" y="22" width="140" height="76" rx="3" class="court-surface"/>';
            $body = '<rect x="30" y="22" width="140" height="76" rx="3" stroke="#fff" stroke-width="2" fill="none"/>'
                . '<line x1="30" y1="60" x2="170" y2="60" stroke="rgba(255,255,255,.85)" stroke-width="1"/>'
                . '<line x1="100" y1="16" x2="100" y2="104" stroke="#0F172A" stroke-width="2.5"/>'
                . '<circle cx="100" cy="16" r="2.5" fill="#0F172A"/><circle cx="100" cy="104" r="2.5" fill="#0F172A"/>';
            break;

        default:
            $body = '<rect x="12" y="12" width="176" height="96" ' . $line . '/><line x1="100" y1="12" x2="100" y2="108" ' . $line . '/>';
    }

    return '<svg class="court-drawing" viewBox="0 0 200 120" preserveAspectRatio="xMidYMid meet" aria-hidden="true">' . $surface . $body . '</svg>';
}
