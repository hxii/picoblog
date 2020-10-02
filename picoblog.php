<?php

/**
 * picoblog
 * 
 * made by hxii (https://0xff.nu/picoblog)
 * 
 * Picoblog is a very simple front-end for a twtxt (https://github.com/prologic/twtxt) format microblog with support for:
 * - Limited Markdown (strong, em, marked, deleted, links, images, inline code).
 * - Tags (#tags are automatically converted to links).
 * - Unique IDs (I use them, but they are optional).
 */

namespace hxii;

class PicoBlog
{

    private $sourcefile, $format;
    public $rawentries, $blog;

    /**
     * Constructor.
     *
     * @param string $sourcefile Source file in twtxt format (or PicoBlog format).
     */
    public function __construct(string $sourcefile, string $format = 'picoblog')
    {
        $this->sourcefile = $sourcefile;
        $this->format = $format;
        $this->readSource();
    }

    /**
     * Check for and parse query string from $_SERVER['QUERY_STRING']).
     * Used to get entries by ID or tag.
     *
     * @return array|boolean
     */
    public function parseQuery()
    {
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $return);
            return $return;
        }
        return false;
    }

    /**
     * Read source file
     *
     * @return boolean true if successful, false if not
     */
    private function readSource()
    {
        if (is_file($this->sourcefile) && is_readable($this->sourcefile)) {
            $this->rawentries = file($this->sourcefile);
            if (!empty($this->rawentries)) {
                return true;
            }
        }
        throw new \Exception("{$this->sourcefile} is empty! Aborting.");
        return false;
    }

    /**
     * Parse entries from source file and replace tags with links
     *
     * @param array $entries array of raw entries
     * @return void
     */
    private function parseEntries(array $entries, bool $parseTags = true)
    {
        switch ($this->format) {
            case 'twtxt':
                $pattern = '/^(?<date>[0-9-T:Z]+)\t(?<entry>.*)/';
                break;
            case 'picoblog':
                $pattern = '/^(?<date>[0-9-T:Z]+)\t(?<id>[a-zA-Z0-9]{6})\t(?<entry>.*)/';
                break;
        }
        foreach ($entries as $i => $entry) {
            preg_match($pattern, $entry, $matches);
            if (!$matches) continue;
            $id = (!empty($matches['id'])) ? $matches['id'] : $i;
            $parsedEntries[$id] = [
                'date' => $matches['date'],
                'entry' => ($parseTags) ? preg_replace('/#(\w+)?/', '<a href="?tag=$1">#${1}</a>', $matches['entry']) : $matches['entry'],
            ];
        }
        return $parsedEntries;
    }

    /**
     * Returns a filtered list of raw entries
     *
     * @param string|array $search entry filter. can be 'all', 'newest', 'oldest', 'random' or an ID/Tag.
     * For ID, we're looking for ['id'=>'IDHERE']. For tag, we're looking for ['tag'=>'tagname']
     * @return boolean|array
     */
    public function getEntries($search)
    {
        switch ($search) {
            case '':
                return false;
            case 'all':
                return $this->rawentries;
            case 'newest':
                return [reset($this->rawentries)];
            case 'oldest':
                return [end($this->rawentries)];
            case 'random':
                return [$this->rawentries[array_rand($this->rawentries, 1)]];
            default:
                if (isset($search['id'])) {
                    $filter =  array_filter($this->rawentries, function ($entry) use ($search) {
                        preg_match("/\b$search[id]\b/i", $entry, $match);
                        return $match;
                    });
                    return $filter;
                } elseif (isset($search['tag'])) {
                    $filter =  array_filter($this->rawentries, function ($entry) use ($search) {
                        preg_match("/#\b$search[tag]\b/i", $entry, $match);
                        return $match;
                    });
                    return $filter;
                }
                return false;
        }
    }

    /**
     * Render Markdown in given entries and output as HTML
     *
     * @param array $entries array of parsed entries to render
     * @param string $entryWrap tne entry wrapper, e.g. <li>{entry}</li>
     * @param bool $parseTags should #tags be parsed to links?
     * @return string entries in HTML
     */
    public function renderEntries(array $entries, string $entryWrap = '<li>{entry}</li>', bool $parseTags = true)
    {
        if (!$entries) return false;
        $entries = $this->parseEntries($entries, $parseTags);
        require_once('Slimdown.php');
        $html = '';
        foreach ($entries as $id => $entry) {
            $text = \Slimdown::render($entry['entry']);
            $date = $entry['date'];
            $text = "<a href='?id={$id}' title='{$date}'>[{$id}]</a> " . $text;
            $html .= str_replace('{entry}', $text, $entryWrap);
        }
        return $html;
    }
}