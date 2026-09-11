<?php

declare(strict_types=1);

namespace Matchish\ScoutElasticSearch\ElasticSearch;

/**
 * Makes a user-typed term safe to use as the `query` of a query_string
 * clause. Backslashes the Lucene reserved set, drops the two characters that
 * cannot be escaped (`<` `>`), and lowercases the standalone boolean
 * operators so `black OR white` is two words, not a disjunction. A lone `*`
 * is left alone — it is the conventional match-all.
 *
 * `*` inside a term is deliberately left live for now; callers that build
 * wildcards still depend on it.
 */
final class QueryStringEscaper
{
    private const RESERVED = ['\\', '+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '?', ':', '/'];

    public static function escape(string $query): string
    {
        if ($query === '*') {
            return $query;
        }

        $query = str_replace(['<', '>'], '', $query);

        foreach (self::RESERVED as $char) {
            $query = str_replace($char, '\\'.$char, $query);
        }

        return (string) preg_replace_callback('/\b(AND|OR|NOT)\b/', fn (array $m) => strtolower($m[1]), $query);
    }
}
