<?php

/**
 * @file
 *
 * Pipeline functions for the index builder.
 */

require __DIR__ . '/../vendor/autoload.php';

use markfullmer\porter2\Porter2;

/**
 * Removes non-alphanumerica charactures from a string.
 *
 * @param string $token A string to be trimmed.
 *
 * @return string
 *   Cleaned string.
 */
function trimmer(string $token) {
  return preg_replace("/[^A-Za-z0-9 ]/", '', $token);
}

$stopWords = [
  'a',
  'able',
  'about',
  'across',
  'after',
  'all',
  'almost',
  'also',
  'am',
  'among',
  'an',
  'and',
  'any',
  'are',
  'as',
  'at',
  'be',
  'because',
  'been',
  'but',
  'by',
  'can',
  'cannot',
  'could',
  'dear',
  'did',
  'do',
  'does',
  'either',
  'else',
  'ever',
  'every',
  'for',
  'from',
  'get',
  'got',
  'had',
  'has',
  'have',
  'he',
  'her',
  'hers',
  'him',
  'his',
  'how',
  'however',
  'i',
  'if',
  'in',
  'into',
  'is',
  'it',
  'its',
  'just',
  'least',
  'let',
  'like',
  'likely',
  'may',
  'me',
  'might',
  'most',
  'must',
  'my',
  'neither',
  'no',
  'nor',
  'not',
  'of',
  'off',
  'often',
  'on',
  'only',
  'or',
  'other',
  'our',
  'own',
  'rather',
  'said',
  'say',
  'says',
  'she',
  'should',
  'since',
  'so',
  'some',
  'than',
  'that',
  'the',
  'their',
  'them',
  'then',
  'there',
  'these',
  'they',
  'this',
  'tis',
  'to',
  'too',
  'twas',
  'us',
  'wants',
  'was',
  'we',
  'were',
  'what',
  'when',
  'where',
  'which',
  'while',
  'who',
  'whom',
  'why',
  'will',
  'with',
  'would',
  'yet',
  'you',
  'your'
];

/**
 * Provides filtering mechanism for token strings by only return token if it
 * does not exist in a list of stopwords.
 *
 * @param string $token Token to filter against.
 * @return (string || bool)
 *   Returns string it if not in the list, FALSE if it is.
 */
function stop_word_filter(string $token) {
  global $stopWords;
  if (!in_array($token, $stopWords)) {
    return $token;
  }
  return FALSE;
}

/**
 * Stems strings using Porter2 algorithm. Wrapper around
 * https://github.com/markfullmer/porter2.
 *
 * @param string $token A string to be stemmed.
 *
 * @return string
 *   Stemmed string.
 */
function stemmer(string $token) {
  return Porter2::stem($token);
}
