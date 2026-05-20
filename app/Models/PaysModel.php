<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Données pays + lieux d'un pays.
 */
class PaysModel extends Modele
{
    public static function parId(int $idPays): ?array
    {
        $st = self::pdo()->prepare('SELECT * FROM pays WHERE id_pays = :id LIMIT 1');
        $st->execute([':id' => $idPays]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /**
     * @return list<array{id_pays:int|string,nom:string}>
     */
    public static function listePourFiltre(): array
    {
        $q = self::pdo()->query('SELECT id_pays, nom FROM pays ORDER BY nom ASC');
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Cherche un pays par son code ISO 3 lettres (BDD), sinon par nom, sinon le crée.
     * Mapbox renvoie un code ISO 2 lettres ("fr") : on le convertit via la table
     * ISO-3166 ci-dessous. Si le code ISO-2 n'est pas dans la table, on tente une
     * recherche par nom et, en dernier recours, on génère un code ISO-3 dégradé
     * (impossible normalement — Mapbox couvre les 250 pays standards).
     */
    public static function trouverOuCreerParCodeIso(?string $codeIso2, string $nom): int
    {
        $nom = trim($nom);
        if ($nom === '') {
            throw new \InvalidArgumentException('Nom de pays vide.');
        }

        $iso3 = null;
        if (is_string($codeIso2) && strlen($codeIso2) === 2) {
            $iso3 = self::ISO2_VERS_ISO3[strtolower($codeIso2)] ?? null;
        }

        // 1) Recherche par code ISO-3 si on l'a déduit (le plus fiable)
        if ($iso3 !== null) {
            $st = self::pdo()->prepare(
                'SELECT id_pays FROM pays WHERE code_iso = :iso LIMIT 1'
            );
            $st->execute([':iso' => $iso3]);
            $id = $st->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        }

        // 2) Repli : recherche par nom exact (insensible à la casse)
        $st = self::pdo()->prepare(
            'SELECT id_pays FROM pays WHERE LOWER(nom) = LOWER(:nom) LIMIT 1'
        );
        $st->execute([':nom' => $nom]);
        $id = $st->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        // 3) Création : il faut un code ISO-3 valide (CHAR(3) NOT NULL UNIQUE en BDD)
        if ($iso3 === null) {
            throw new \RuntimeException(
                'Impossible de créer le pays "' . $nom . '" : code ISO inconnu.'
            );
        }

        $st = self::pdo()->prepare(
            'INSERT INTO pays (nom, code_iso, continent) VALUES (:nom, :iso, :cont)'
        );
        $st->execute([
            ':nom'  => $nom,
            ':iso'  => $iso3,
            ':cont' => self::ISO3_VERS_CONTINENT[$iso3] ?? null,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Lieux du pays avec note moyenne (avis publics sur lieu uniquement).
     *
     * @return list<array<string, mixed>>
     */
    public static function lieuxAvecNotes(int $idPays): array
    {
        $st = self::pdo()->prepare(
            "SELECT l.id_lieu, l.nom, l.image_url, vi.nom AS ville_nom,
                COUNT(a.id_avis) AS nb_avis,
                COALESCE(ROUND(AVG(a.note), 2), NULL) AS avg_rating
             FROM lieu l
             JOIN ville vi ON vi.id_ville = l.id_ville
             LEFT JOIN avis a ON a.id_lieu = l.id_lieu AND a.visibility = 'public'
             WHERE vi.id_pays = :p
             GROUP BY l.id_lieu, l.nom, l.image_url, vi.nom
             ORDER BY avg_rating IS NULL, avg_rating DESC, l.nom ASC"
        );
        $st->execute([':p' => $idPays]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Table ISO 3166-1 alpha-2 → alpha-3 (Mapbox renvoie l'alpha-2 minuscule).
     * Couverture des 250 codes officiels — embarquée plutôt qu'un appel API
     * externe (zero dépendance, deterministe, hors-ligne).
     */
    private const ISO2_VERS_ISO3 = [
        'af' => 'AFG', 'ax' => 'ALA', 'al' => 'ALB', 'dz' => 'DZA', 'as' => 'ASM',
        'ad' => 'AND', 'ao' => 'AGO', 'ai' => 'AIA', 'aq' => 'ATA', 'ag' => 'ATG',
        'ar' => 'ARG', 'am' => 'ARM', 'aw' => 'ABW', 'au' => 'AUS', 'at' => 'AUT',
        'az' => 'AZE', 'bs' => 'BHS', 'bh' => 'BHR', 'bd' => 'BGD', 'bb' => 'BRB',
        'by' => 'BLR', 'be' => 'BEL', 'bz' => 'BLZ', 'bj' => 'BEN', 'bm' => 'BMU',
        'bt' => 'BTN', 'bo' => 'BOL', 'bq' => 'BES', 'ba' => 'BIH', 'bw' => 'BWA',
        'bv' => 'BVT', 'br' => 'BRA', 'io' => 'IOT', 'bn' => 'BRN', 'bg' => 'BGR',
        'bf' => 'BFA', 'bi' => 'BDI', 'cv' => 'CPV', 'kh' => 'KHM', 'cm' => 'CMR',
        'ca' => 'CAN', 'ky' => 'CYM', 'cf' => 'CAF', 'td' => 'TCD', 'cl' => 'CHL',
        'cn' => 'CHN', 'cx' => 'CXR', 'cc' => 'CCK', 'co' => 'COL', 'km' => 'COM',
        'cd' => 'COD', 'cg' => 'COG', 'ck' => 'COK', 'cr' => 'CRI', 'ci' => 'CIV',
        'hr' => 'HRV', 'cu' => 'CUB', 'cw' => 'CUW', 'cy' => 'CYP', 'cz' => 'CZE',
        'dk' => 'DNK', 'dj' => 'DJI', 'dm' => 'DMA', 'do' => 'DOM', 'ec' => 'ECU',
        'eg' => 'EGY', 'sv' => 'SLV', 'gq' => 'GNQ', 'er' => 'ERI', 'ee' => 'EST',
        'sz' => 'SWZ', 'et' => 'ETH', 'fk' => 'FLK', 'fo' => 'FRO', 'fj' => 'FJI',
        'fi' => 'FIN', 'fr' => 'FRA', 'gf' => 'GUF', 'pf' => 'PYF', 'tf' => 'ATF',
        'ga' => 'GAB', 'gm' => 'GMB', 'ge' => 'GEO', 'de' => 'DEU', 'gh' => 'GHA',
        'gi' => 'GIB', 'gr' => 'GRC', 'gl' => 'GRL', 'gd' => 'GRD', 'gp' => 'GLP',
        'gu' => 'GUM', 'gt' => 'GTM', 'gg' => 'GGY', 'gn' => 'GIN', 'gw' => 'GNB',
        'gy' => 'GUY', 'ht' => 'HTI', 'hm' => 'HMD', 'va' => 'VAT', 'hn' => 'HND',
        'hk' => 'HKG', 'hu' => 'HUN', 'is' => 'ISL', 'in' => 'IND', 'id' => 'IDN',
        'ir' => 'IRN', 'iq' => 'IRQ', 'ie' => 'IRL', 'im' => 'IMN', 'il' => 'ISR',
        'it' => 'ITA', 'jm' => 'JAM', 'jp' => 'JPN', 'je' => 'JEY', 'jo' => 'JOR',
        'kz' => 'KAZ', 'ke' => 'KEN', 'ki' => 'KIR', 'kp' => 'PRK', 'kr' => 'KOR',
        'kw' => 'KWT', 'kg' => 'KGZ', 'la' => 'LAO', 'lv' => 'LVA', 'lb' => 'LBN',
        'ls' => 'LSO', 'lr' => 'LBR', 'ly' => 'LBY', 'li' => 'LIE', 'lt' => 'LTU',
        'lu' => 'LUX', 'mo' => 'MAC', 'mg' => 'MDG', 'mw' => 'MWI', 'my' => 'MYS',
        'mv' => 'MDV', 'ml' => 'MLI', 'mt' => 'MLT', 'mh' => 'MHL', 'mq' => 'MTQ',
        'mr' => 'MRT', 'mu' => 'MUS', 'yt' => 'MYT', 'mx' => 'MEX', 'fm' => 'FSM',
        'md' => 'MDA', 'mc' => 'MCO', 'mn' => 'MNG', 'me' => 'MNE', 'ms' => 'MSR',
        'ma' => 'MAR', 'mz' => 'MOZ', 'mm' => 'MMR', 'na' => 'NAM', 'nr' => 'NRU',
        'np' => 'NPL', 'nl' => 'NLD', 'nc' => 'NCL', 'nz' => 'NZL', 'ni' => 'NIC',
        'ne' => 'NER', 'ng' => 'NGA', 'nu' => 'NIU', 'nf' => 'NFK', 'mk' => 'MKD',
        'mp' => 'MNP', 'no' => 'NOR', 'om' => 'OMN', 'pk' => 'PAK', 'pw' => 'PLW',
        'ps' => 'PSE', 'pa' => 'PAN', 'pg' => 'PNG', 'py' => 'PRY', 'pe' => 'PER',
        'ph' => 'PHL', 'pn' => 'PCN', 'pl' => 'POL', 'pt' => 'PRT', 'pr' => 'PRI',
        'qa' => 'QAT', 're' => 'REU', 'ro' => 'ROU', 'ru' => 'RUS', 'rw' => 'RWA',
        'bl' => 'BLM', 'sh' => 'SHN', 'kn' => 'KNA', 'lc' => 'LCA', 'mf' => 'MAF',
        'pm' => 'SPM', 'vc' => 'VCT', 'ws' => 'WSM', 'sm' => 'SMR', 'st' => 'STP',
        'sa' => 'SAU', 'sn' => 'SEN', 'rs' => 'SRB', 'sc' => 'SYC', 'sl' => 'SLE',
        'sg' => 'SGP', 'sx' => 'SXM', 'sk' => 'SVK', 'si' => 'SVN', 'sb' => 'SLB',
        'so' => 'SOM', 'za' => 'ZAF', 'gs' => 'SGS', 'ss' => 'SSD', 'es' => 'ESP',
        'lk' => 'LKA', 'sd' => 'SDN', 'sr' => 'SUR', 'sj' => 'SJM', 'se' => 'SWE',
        'ch' => 'CHE', 'sy' => 'SYR', 'tw' => 'TWN', 'tj' => 'TJK', 'tz' => 'TZA',
        'th' => 'THA', 'tl' => 'TLS', 'tg' => 'TGO', 'tk' => 'TKL', 'to' => 'TON',
        'tt' => 'TTO', 'tn' => 'TUN', 'tr' => 'TUR', 'tm' => 'TKM', 'tc' => 'TCA',
        'tv' => 'TUV', 'ug' => 'UGA', 'ua' => 'UKR', 'ae' => 'ARE', 'gb' => 'GBR',
        'us' => 'USA', 'um' => 'UMI', 'uy' => 'URY', 'uz' => 'UZB', 'vu' => 'VUT',
        've' => 'VEN', 'vn' => 'VNM', 'vg' => 'VGB', 'vi' => 'VIR', 'wf' => 'WLF',
        'eh' => 'ESH', 'ye' => 'YEM', 'zm' => 'ZMB', 'zw' => 'ZWE',
    ];

    /**
     * Continent par défaut pour les nouveaux pays auto-créés (libellé FR cohérent
     * avec le seed `seed_map_test.sql` : Europe, Asie, Afrique, Amériques, Océanie).
     * Liste non-exhaustive : pour les pays non-listés, on insère `NULL`.
     */
    private const ISO3_VERS_CONTINENT = [
        // Europe
        'FRA' => 'Europe', 'DEU' => 'Europe', 'ITA' => 'Europe', 'ESP' => 'Europe',
        'PRT' => 'Europe', 'GBR' => 'Europe', 'IRL' => 'Europe', 'NLD' => 'Europe',
        'BEL' => 'Europe', 'LUX' => 'Europe', 'CHE' => 'Europe', 'AUT' => 'Europe',
        'POL' => 'Europe', 'CZE' => 'Europe', 'SVK' => 'Europe', 'HUN' => 'Europe',
        'ROU' => 'Europe', 'BGR' => 'Europe', 'GRC' => 'Europe', 'HRV' => 'Europe',
        'SVN' => 'Europe', 'SRB' => 'Europe', 'BIH' => 'Europe', 'MNE' => 'Europe',
        'MKD' => 'Europe', 'ALB' => 'Europe', 'NOR' => 'Europe', 'SWE' => 'Europe',
        'FIN' => 'Europe', 'DNK' => 'Europe', 'ISL' => 'Europe', 'EST' => 'Europe',
        'LVA' => 'Europe', 'LTU' => 'Europe', 'RUS' => 'Europe', 'UKR' => 'Europe',
        'BLR' => 'Europe', 'MDA' => 'Europe', 'MLT' => 'Europe', 'CYP' => 'Europe',
        // Asie
        'JPN' => 'Asie', 'CHN' => 'Asie', 'KOR' => 'Asie', 'PRK' => 'Asie',
        'IND' => 'Asie', 'PAK' => 'Asie', 'BGD' => 'Asie', 'IDN' => 'Asie',
        'THA' => 'Asie', 'VNM' => 'Asie', 'PHL' => 'Asie', 'MYS' => 'Asie',
        'SGP' => 'Asie', 'KHM' => 'Asie', 'LAO' => 'Asie', 'MMR' => 'Asie',
        'TUR' => 'Asie', 'IRN' => 'Asie', 'IRQ' => 'Asie', 'SAU' => 'Asie',
        'ARE' => 'Asie', 'ISR' => 'Asie', 'JOR' => 'Asie', 'LBN' => 'Asie',
        'SYR' => 'Asie', 'YEM' => 'Asie', 'AFG' => 'Asie', 'KAZ' => 'Asie',
        'UZB' => 'Asie', 'TKM' => 'Asie', 'TJK' => 'Asie', 'KGZ' => 'Asie',
        'MNG' => 'Asie', 'NPL' => 'Asie', 'LKA' => 'Asie', 'TWN' => 'Asie',
        'HKG' => 'Asie', 'MAC' => 'Asie', 'QAT' => 'Asie', 'KWT' => 'Asie',
        'OMN' => 'Asie', 'BHR' => 'Asie',
        // Afrique
        'KEN' => 'Afrique', 'MAR' => 'Afrique', 'EGY' => 'Afrique', 'ZAF' => 'Afrique',
        'NGA' => 'Afrique', 'ETH' => 'Afrique', 'GHA' => 'Afrique', 'CIV' => 'Afrique',
        'SEN' => 'Afrique', 'CMR' => 'Afrique', 'TUN' => 'Afrique', 'DZA' => 'Afrique',
        'LBY' => 'Afrique', 'SDN' => 'Afrique', 'TZA' => 'Afrique', 'UGA' => 'Afrique',
        'RWA' => 'Afrique', 'AGO' => 'Afrique', 'MOZ' => 'Afrique', 'MDG' => 'Afrique',
        'ZWE' => 'Afrique', 'ZMB' => 'Afrique', 'NAM' => 'Afrique', 'BWA' => 'Afrique',
        'MLI' => 'Afrique', 'BFA' => 'Afrique', 'NER' => 'Afrique', 'TCD' => 'Afrique',
        'COD' => 'Afrique', 'COG' => 'Afrique', 'GAB' => 'Afrique', 'BEN' => 'Afrique',
        'TGO' => 'Afrique',
        // Amériques
        'USA' => 'Amériques', 'CAN' => 'Amériques', 'MEX' => 'Amériques',
        'BRA' => 'Amériques', 'ARG' => 'Amériques', 'CHL' => 'Amériques',
        'PER' => 'Amériques', 'COL' => 'Amériques', 'VEN' => 'Amériques',
        'URY' => 'Amériques', 'PRY' => 'Amériques', 'BOL' => 'Amériques',
        'ECU' => 'Amériques', 'CUB' => 'Amériques', 'DOM' => 'Amériques',
        'HTI' => 'Amériques', 'JAM' => 'Amériques', 'GTM' => 'Amériques',
        'PAN' => 'Amériques', 'CRI' => 'Amériques', 'HND' => 'Amériques',
        'NIC' => 'Amériques', 'SLV' => 'Amériques', 'BLZ' => 'Amériques',
        'TTO' => 'Amériques',
        // Océanie
        'AUS' => 'Océanie', 'NZL' => 'Océanie', 'FJI' => 'Océanie',
        'PNG' => 'Océanie', 'SLB' => 'Océanie', 'VUT' => 'Océanie',
        'NCL' => 'Océanie', 'PYF' => 'Océanie', 'WSM' => 'Océanie',
        'TON' => 'Océanie',
    ];
}
