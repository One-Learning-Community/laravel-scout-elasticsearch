<?php

declare(strict_types=1);

namespace Matchish\ScoutElasticSearch\ElasticSearch;

/**
 * Makes a user-typed term safe to use as the `query` of a query_string
 * clause. Backslashes the Lucene reserved set, drops the two characters that
 * cannot be escaped (`<` `>`), and lowercases the standalone boolean
 * operators so `black OR white` is two words, not a disjunction. A run of
 * bare `*` characters (`*`, `**`, ...) is left alone — it is the
 * conventional match-all sentinel several server-side callers hand in on
 * purpose (e.g. `SearchService::search('*', ...)`, `SystemRenderer::getFeatured()`'s
 * `'**'`) — never something a caller received from a low-trust input field.
 *
 * A `*` embedded in anything else IS escaped (security-audit 04-04, P0
 * follow-up): unescaped it is a Lucene wildcard operator, and a leading `*`
 * in particular forces an expensive backward index scan (wildcard-injection
 * DoS) — live because `config/elasticsearch.php` sets no
 * `allow_leading_wildcard: false` to block it at the cluster level. As of
 * the P0-P5 escaping work (`337f500822`, `f3d4a2f8e1`) no production caller
 * builds a `RawSearchQuery`/wildcard query through this plain-string path
 * any more — every deliberate wildcard use (TermClause, WildcardFilter,
 * StandardPickerQuery-style pickers) already escapes its own `*` before
 * building a structured `wildcard` clause — so there is nothing left
 * depending on a live embedded `*` here.
 */
final class QueryStringEscaper
{
    private const RESERVED = ['\\', '+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '?', ':', '/', '*'];

    public static function escape(string $query): string
    {
        if ($query !== '' && preg_match('/^\*+$/', $query) === 1) {
            return $query;
        }

        $query = str_replace(['<', '>'], '', $query);

        foreach (self::RESERVED as $char) {
            $query = str_replace($char, '\\'.$char, $query);
        }

        return (string) preg_replace_callback('/\b(AND|OR|NOT)\b/', fn (array $m) => strtolower($m[1]), $query);
    }
}
